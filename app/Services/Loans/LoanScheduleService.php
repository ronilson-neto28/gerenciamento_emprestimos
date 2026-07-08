<?php

namespace App\Services\Loans;

use App\Models\Feriado;
use Carbon\CarbonImmutable;

class LoanScheduleService
{
    public function parseMoneyToCents(string $value): int
    {
        $raw = trim($value);
        if ($raw === '') {
            return 0;
        }

        if (preg_match('/[.,]\d{1,2}\s*$/', $raw)) {
            $normalized = str_replace(['.', ' '], '', $raw);
            $normalized = str_replace(',', '.', $normalized);
            $floatValue = (float) preg_replace('/[^0-9.]/', '', $normalized);
            return (int) round($floatValue * 100);
        }

        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return 0;
        }

        return ((int) $digits) * 100;
    }

    public function parsePercent(string $value): float
    {
        $raw = trim($value);
        if ($raw === '') {
            return 0.0;
        }

        $normalized = str_replace(['%', ' '], '', $raw);
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
        $number = (float) preg_replace('/[^0-9.]/', '', $normalized);
        if ($number <= 0) {
            return 0.0;
        }

        return $number;
    }

    public function percentToRate(float $percent): float
    {
        if ($percent <= 0) {
            return 0.0;
        }

        if ($percent <= 1) {
            return $percent;
        }

        return $percent / 100;
    }

    public function formatMoney(int $cents): string
    {
        $value = $cents / 100;
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public function isoToDisplay(string $iso): string
    {
        $value = trim($iso);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        [$y, $m, $d] = explode('-', $value);
        return $d . '/' . $m . '/' . $y;
    }

    public function buildSchedule(array $payload): array
    {
        $principalCents = (int) ($payload['principal_cents'] ?? 0);
        $numeroParcelas = (int) ($payload['numero_parcelas'] ?? 0);
        $tipoJuros = (string) ($payload['tipo_juros'] ?? 'simples');
        $taxaPercent = (float) ($payload['taxa_juros_percent'] ?? 0.0);
        $intervalo = (string) ($payload['intervalo'] ?? 'mensal');
        $baseIso = (string) ($payload['data_emprestimo'] ?? '');
        $excecoes = array_values(array_filter((array) ($payload['excecoes_dia'] ?? [])));

        if ($principalCents <= 0 || $numeroParcelas <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $baseIso)) {
            return [
                'schedule' => [],
                'total_cents' => 0,
            ];
        }

        $rate = $this->percentToRate($taxaPercent);
        $baseDate = CarbonImmutable::createFromFormat('Y-m-d', $baseIso)->startOfDay();
        $holidayDates = $this->loadHolidayDates($baseDate, $numeroParcelas, $intervalo, $excecoes);

        if ($tipoJuros === 'composto') {
            return $this->buildCompoundSchedule($principalCents, $rate, $numeroParcelas, $intervalo, $baseDate, $excecoes, $holidayDates);
        }

        if ($tipoJuros === 'fixo') {
            return $this->buildFixedSchedule($principalCents, $rate, $numeroParcelas, $intervalo, $baseDate, $excecoes, $holidayDates);
        }

        return $this->buildSimpleSchedule($principalCents, $rate, $numeroParcelas, $intervalo, $baseDate, $excecoes, $holidayDates);
    }

    public function decorateInstallments(array $installments, array $loan): array
    {
        $tipoMulta = (string) ($loan['tipo_multa'] ?? 'percentual');
        $cobrancaMulta = (string) ($loan['cobranca_multa'] ?? 'automatica');
        $valorMultaRaw = (string) ($loan['valor_multa'] ?? '');
        $taxaPercent = (float) ($loan['taxa_juros_percent'] ?? 0.0);
        $principalCents = (int) ($loan['valor_cents'] ?? 0);
        if ($principalCents <= 0) {
            $principalCents = $this->parseMoneyToCents((string) ($loan['valor'] ?? ''));
        }

        $multaRate = 0.0;
        $multaCents = 0;

        if ($cobrancaMulta !== 'desativada') {
            if ($tipoMulta === 'percentual') {
                $multaRate = $this->percentToRate($this->parsePercent($valorMultaRaw));
            } else {
                $multaCents = $this->parseMoneyToCents($valorMultaRaw);
            }
        }

        $today = CarbonImmutable::now()->startOfDay();

        return array_map(function ($item) use ($tipoMulta, $cobrancaMulta, $multaRate, $multaCents, $today, $taxaPercent, $principalCents) {
            $status = (string) ($item['status'] ?? 'pendente');
            $vencimentoIso = (string) ($item['vencimento'] ?? '');
            $baseTotalCents = (int) ($item['total_cents'] ?? 0);
            $baseTotalMoney = (string) ($item['total'] ?? '');

            $overdueDays = 0;
            $multaAplicada = 0;

            if (!in_array($status, ['pago', 'pago_parcial'], true) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimentoIso)) {
                $due = CarbonImmutable::createFromFormat('Y-m-d', $vencimentoIso)->startOfDay();
                if ($today->greaterThan($due)) {
                    $overdueDays = $due->diffInDays($today);
                    if ($cobrancaMulta === 'automatica' && $overdueDays > 0) {
                        $penaltyBaseCents = $principalCents > 0 ? $principalCents : $baseTotalCents;
                        if ($tipoMulta === 'percentual') {
                            $multaAplicada = (int) round($penaltyBaseCents * $multaRate * $overdueDays);
                        } else {
                            $multaAplicada = $multaCents * $overdueDays;
                        }
                    }
                }
            }

            if (!in_array($status, ['pago', 'pago_parcial'], true) && $overdueDays > 0) {
                $status = 'vencida';
            }

            $totalComMulta = $baseTotalCents + $multaAplicada;

            return array_merge($item, [
                'status' => $status,
                'multa_cents' => $multaAplicada,
                'multa' => $multaAplicada > 0 ? $this->formatMoney($multaAplicada) : ($item['multa'] ?? 'R$ 0,00'),
                'total_cents' => $totalComMulta,
                'total' => $totalComMulta > 0 ? $this->formatMoney($totalComMulta) : $baseTotalMoney,
                'taxa_juros_percent' => $taxaPercent,
                'vencimento_display' => $this->isoToDisplay($vencimentoIso),
                'overdue_days' => $overdueDays,
            ]);
        }, $installments);
    }

    private function buildSimpleSchedule(int $principalCents, float $rate, int $n, string $intervalo, CarbonImmutable $baseDate, array $excecoes, array $holidayDates): array
    {
        $totalCents = (int) round($principalCents * (1 + $rate));
        $interestTotalCents = max($totalCents - $principalCents, 0);

        $amortizacaoBase = intdiv($principalCents, $n);
        $amortizacaoResto = $principalCents - ($amortizacaoBase * $n);

        $jurosBase = intdiv($interestTotalCents, $n);
        $jurosResto = $interestTotalCents - ($jurosBase * $n);

        $schedule = [];

        for ($i = 1; $i <= $n; $i++) {
            $amortizacao = $amortizacaoBase + ($i <= $amortizacaoResto ? 1 : 0);
            $juros = $jurosBase + ($i <= $jurosResto ? 1 : 0);
            $due = $this->adjustDate($this->addInterval($baseDate, $intervalo, $i), $excecoes, $holidayDates);
            $total = $amortizacao + $juros;

            $schedule[] = [
                'numero' => $i,
                'vencimento' => $due->format('Y-m-d'),
                'amortizacao_cents' => $amortizacao,
                'juros_cents' => $juros,
                'multa_cents' => 0,
                'total_cents' => $total,
                'amortizacao' => $this->formatMoney($amortizacao),
                'juros' => $this->formatMoney($juros),
                'multa' => 'R$ 0,00',
                'total' => $this->formatMoney($total),
                'status' => 'pendente',
            ];
        }

        return [
            'schedule' => $schedule,
            'total_cents' => $totalCents,
        ];
    }

    private function buildFixedSchedule(int $principalCents, float $rate, int $n, string $intervalo, CarbonImmutable $baseDate, array $excecoes, array $holidayDates): array
    {
        $totalCents = (int) round($principalCents * (1 + $rate));
        $interestTotalCents = max($totalCents - $principalCents, 0);

        $paymentBase = intdiv($totalCents, $n);
        $paymentResto = $totalCents - ($paymentBase * $n);

        $jurosBase = intdiv($interestTotalCents, $n);
        $jurosResto = $interestTotalCents - ($jurosBase * $n);

        $schedule = [];

        for ($i = 1; $i <= $n; $i++) {
            $payment = $paymentBase + ($i <= $paymentResto ? 1 : 0);
            $juros = $jurosBase + ($i <= $jurosResto ? 1 : 0);
            $amortizacao = max($payment - $juros, 0);
            $due = $this->adjustDate($this->addInterval($baseDate, $intervalo, $i), $excecoes, $holidayDates);

            $schedule[] = [
                'numero' => $i,
                'vencimento' => $due->format('Y-m-d'),
                'amortizacao_cents' => $amortizacao,
                'juros_cents' => $juros,
                'multa_cents' => 0,
                'total_cents' => $payment,
                'amortizacao' => $this->formatMoney($amortizacao),
                'juros' => $this->formatMoney($juros),
                'multa' => 'R$ 0,00',
                'total' => $this->formatMoney($payment),
                'status' => 'pendente',
            ];
        }

        return [
            'schedule' => $schedule,
            'total_cents' => $totalCents,
        ];
    }

    private function buildCompoundSchedule(int $principalCents, float $rate, int $n, string $intervalo, CarbonImmutable $baseDate, array $excecoes, array $holidayDates): array
    {
        if ($rate <= 0) {
            return $this->buildSimpleSchedule($principalCents, 0.0, $n, $intervalo, $baseDate, $excecoes, $holidayDates);
        }

        $r = $rate;
        $factor = pow(1 + $r, $n);
        $payment = (int) round($principalCents * (($r * $factor) / ($factor - 1)));

        $schedule = [];
        $balance = $principalCents;
        $totalCents = 0;

        for ($i = 1; $i <= $n; $i++) {
            $juros = (int) round($balance * $r);
            $amortizacao = $payment - $juros;
            if ($i === $n) {
                $amortizacao = $balance;
                $juros = max($payment - $amortizacao, 0);
            }

            $balance = max($balance - $amortizacao, 0);
            $due = $this->adjustDate($this->addInterval($baseDate, $intervalo, $i), $excecoes, $holidayDates);
            $totalCents += ($amortizacao + $juros);

            $schedule[] = [
                'numero' => $i,
                'vencimento' => $due->format('Y-m-d'),
                'amortizacao_cents' => $amortizacao,
                'juros_cents' => $juros,
                'multa_cents' => 0,
                'total_cents' => $amortizacao + $juros,
                'amortizacao' => $this->formatMoney($amortizacao),
                'juros' => $this->formatMoney($juros),
                'multa' => 'R$ 0,00',
                'total' => $this->formatMoney($amortizacao + $juros),
                'status' => 'pendente',
            ];
        }

        return [
            'schedule' => $schedule,
            'total_cents' => $totalCents,
        ];
    }

    private function addInterval(CarbonImmutable $baseDate, string $intervalo, int $step): CarbonImmutable
    {
        return match ($intervalo) {
            'diario' => $baseDate->addDays($step),
            'semanal' => $baseDate->addDays($step * 7),
            'quinzenal' => $baseDate->addDays($step * 15),
            default => $baseDate->addMonthsNoOverflow($step),
        };
    }

    private function adjustDate(CarbonImmutable $date, array $excecoes, array $holidayDates): CarbonImmutable
    {
        $skipSaturday = in_array('anular_sabados', $excecoes, true);
        $skipSunday = in_array('anular_domingos', $excecoes, true);
        $skipHoliday = in_array('anular_feriados', $excecoes, true);

        $current = $date;

        while (true) {
            $weekday = (int) $current->dayOfWeekIso;
            $isSaturday = $weekday === 6;
            $isSunday = $weekday === 7;
            $iso = $current->format('Y-m-d');
            $isHoliday = $skipHoliday && in_array($iso, $holidayDates, true);

            if (($skipSaturday && $isSaturday) || ($skipSunday && $isSunday) || $isHoliday) {
                $current = $current->addDay();
                continue;
            }

            return $current;
        }
    }

    private function loadHolidayDates(CarbonImmutable $baseDate, int $n, string $intervalo, array $excecoes): array
    {
        if (!in_array('anular_feriados', $excecoes, true)) {
            return [];
        }

        $end = $this->addInterval($baseDate, $intervalo, $n)->addDays(7);
        $startIso = $baseDate->format('Y-m-d');
        $endIso = $end->format('Y-m-d');

        return Feriado::query()
            ->where('date', '>=', $startIso)
            ->where('date', '<=', $endIso)
            ->pluck('date')
            ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
            ->values()
            ->all();
    }
}
