<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos\Parcelas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Emprestimos\ReceiveParcelaRequest;
use App\Models\Emprestimo;
use App\Models\Feriado;
use App\Models\Parcela;
use App\Models\Recebimento;
use App\Services\Loans\LoanScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class ReceiveController extends Controller
{
    public function __invoke(ReceiveParcelaRequest $request, string $id, LoanScheduleService $service): JsonResponse
    {
        $parcela = Parcela::query()->findOrFail($id);
        $loan = Emprestimo::query()->findOrFail((string) ($parcela['emprestimo_id'] ?? ''));
        Gate::authorize('access-emprestimo', $loan);
        $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
        $ownerId = trim((string) ($loan['owner_id'] ?? ''));
        $loanIdValues = [$loanId];
        if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
            $loanIdValues[] = new ObjectId($loanId);
        }

        if (in_array((string) ($parcela['status'] ?? ''), ['pago', 'pago_parcial'], true)) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validated();
        $onlyInterest = (bool) ($data['only_interest'] ?? false);

        $decorated = $service->decorateInstallments([[
            'id' => (string) ($parcela->id ?? $parcela->getKey() ?? ''),
            'numero' => (int) ($parcela['numero'] ?? 0),
            'vencimento' => (string) ($parcela['vencimento'] ?? ''),
            'amortizacao' => (string) ($parcela['amortizacao'] ?? ''),
            'amortizacao_cents' => (int) ($parcela['amortizacao_cents'] ?? 0),
            'juros' => (string) ($parcela['juros'] ?? ''),
            'juros_cents' => (int) ($parcela['juros_cents'] ?? 0),
            'multa' => (string) ($parcela['multa'] ?? 'R$ 0,00'),
            'multa_cents' => (int) ($parcela['multa_cents'] ?? 0),
            'total' => (string) ($parcela['total'] ?? ''),
            'total_cents' => (int) ($parcela['total_cents'] ?? 0),
            'status' => (string) ($parcela['status'] ?? 'pendente'),
            'recebida_em' => (string) ($parcela['recebida_em'] ?? ''),
        ]], $loan->toArray());

        $current = $decorated[0] ?? null;
        if (!$current) {
            return response()->json(['ok' => false], 422);
        }

        $jurosCents = (int) ($current['juros_cents'] ?? 0);
        $multaCents = (int) ($current['multa_cents'] ?? 0);
        $currentTotalCents = (int) ($current['total_cents'] ?? 0);
        $enteredAmountCents = $service->parseMoneyToCents((string) ($data['receive_amount'] ?? ''));
        $amountCents = $onlyInterest ? ($jurosCents + $multaCents) : $enteredAmountCents;
        $receiveDateIso = (string) $data['receive_date'];

        if ($amountCents <= 0) {
            return response()->json(['ok' => false, 'message' => 'Informe um valor de recebimento maior que zero.'], 422);
        }

        if (!$onlyInterest && $amountCents > $currentTotalCents) {
            return response()->json(['ok' => false, 'message' => 'O valor recebido não pode ser maior que o total da parcela.'], 422);
        }

        $receiptType = $onlyInterest ? 'juros' : ($amountCents < $currentTotalCents ? 'parcial' : 'total');
        $authUserId = (string) ($request->user()?->id ?? $request->user()?->getKey() ?? '');

        Recebimento::create([
            'parcela_id' => (string) ($parcela->id ?? $parcela->getKey() ?? ''),
            'emprestimo_id' => $loanId,
            'cliente_id' => (string) ($loan['cliente_id'] ?? ''),
            'owner_id' => $ownerId,
            'user_id' => $authUserId,
            'valor_recebido' => $service->formatMoney($amountCents),
            'valor_recebido_cents' => $amountCents,
            'recebido_em' => $receiveDateIso,
            'tipo_baixa' => $receiptType,
            'somente_juros' => $onlyInterest,
            'observacao' => '',
        ]);

        if ($onlyInterest) {
            $totalJurosCents = $jurosCents + $multaCents;

            $parcela->fill([
                'status' => 'pago',
                'recebida_em' => $receiveDateIso,
                'amortizacao' => $service->formatMoney(0),
                'amortizacao_cents' => 0,
                'juros' => $service->formatMoney($jurosCents),
                'juros_cents' => $jurosCents,
                'multa' => (string) ($current['multa'] ?? 'R$ 0,00'),
                'multa_cents' => $multaCents,
                'total' => $service->formatMoney($totalJurosCents),
                'total_cents' => $totalJurosCents,
                'valor_recebido' => $service->formatMoney($totalJurosCents),
                'valor_recebido_cents' => $totalJurosCents,
                'saldo_restante' => $service->formatMoney(0),
                'saldo_restante_cents' => 0,
                'principal_pago_cents' => 0,
            ]);
            $parcela->save();

            ['next_due' => $nextDue, 'next_number' => $nextNumber] = $this->resolveNextInstallmentData($loan, $loanIdValues);

            $newAmortCents = (int) ($current['amortizacao_cents'] ?? 0);
            $newTotalCents = $newAmortCents + $jurosCents;

            Parcela::create([
                'emprestimo_id' => $loanId,
                'owner_id' => $ownerId,
                'numero' => $nextNumber,
                'vencimento' => $nextDue->format('Y-m-d'),
                'amortizacao' => $service->formatMoney($newAmortCents),
                'amortizacao_cents' => $newAmortCents,
                'juros' => $service->formatMoney($jurosCents),
                'juros_cents' => $jurosCents,
                'multa' => 'R$ 0,00',
                'multa_cents' => 0,
                'total' => $service->formatMoney($newTotalCents),
                'total_cents' => $newTotalCents,
                'valor_recebido' => $service->formatMoney(0),
                'valor_recebido_cents' => 0,
                'saldo_restante' => $service->formatMoney(0),
                'saldo_restante_cents' => 0,
                'principal_pago_cents' => 0,
                'status' => 'pendente',
            ]);

            $newNumeroParcelas = (int) ($loan['numero_parcelas'] ?? 0) + 1;
            $parcelaCents = (int) ($loan['parcela_cents'] ?? 0);
            if ($parcelaCents <= 0) {
                $parcelaCents = $newTotalCents;
            }

            $loan->fill([
                'numero_parcelas' => $newNumeroParcelas,
                'parcelas' => $newNumeroParcelas . 'x ' . $service->formatMoney($parcelaCents),
                'vencimento' => $nextDue->format('Y-m-d'),
            ]);
            $loan->save();
        } elseif ($amountCents < $currentTotalCents) {
            $saldoRestanteCents = max($currentTotalCents - $amountCents, 0);

            $parcela->fill([
                'status' => 'pago_parcial',
                'recebida_em' => $receiveDateIso,
                'amortizacao' => $service->formatMoney($amountCents),
                'amortizacao_cents' => $amountCents,
                'juros' => $service->formatMoney(0),
                'juros_cents' => 0,
                'multa' => 'R$ 0,00',
                'multa_cents' => 0,
                'total' => $service->formatMoney($amountCents),
                'total_cents' => $amountCents,
                'valor_recebido' => $service->formatMoney($amountCents),
                'valor_recebido_cents' => $amountCents,
                'saldo_restante' => $service->formatMoney($saldoRestanteCents),
                'saldo_restante_cents' => $saldoRestanteCents,
                'principal_pago_cents' => $amountCents,
            ]);
            $parcela->save();

            ['next_due' => $nextDue, 'next_number' => $nextNumber] = $this->resolveNextInstallmentData($loan, $loanIdValues);

            Parcela::create([
                'emprestimo_id' => $loanId,
                'owner_id' => $ownerId,
                'numero' => $nextNumber,
                'vencimento' => $nextDue->format('Y-m-d'),
                'amortizacao' => $service->formatMoney($saldoRestanteCents),
                'amortizacao_cents' => $saldoRestanteCents,
                'juros' => $service->formatMoney(0),
                'juros_cents' => 0,
                'multa' => 'R$ 0,00',
                'multa_cents' => 0,
                'total' => $service->formatMoney($saldoRestanteCents),
                'total_cents' => $saldoRestanteCents,
                'valor_recebido' => $service->formatMoney(0),
                'valor_recebido_cents' => 0,
                'saldo_restante' => $service->formatMoney(0),
                'saldo_restante_cents' => 0,
                'principal_pago_cents' => 0,
                'status' => 'pendente',
            ]);

            $newNumeroParcelas = (int) ($loan['numero_parcelas'] ?? 0) + 1;
            $parcelaCents = (int) ($loan['parcela_cents'] ?? 0);
            if ($parcelaCents <= 0) {
                $parcelaCents = $saldoRestanteCents;
            }

            $loan->fill([
                'numero_parcelas' => $newNumeroParcelas,
                'parcelas' => $newNumeroParcelas . 'x ' . $service->formatMoney($parcelaCents),
                'vencimento' => $nextDue->format('Y-m-d'),
            ]);
            $loan->save();
        } else {
            $parcela->fill([
                'status' => 'pago',
                'recebida_em' => $receiveDateIso,
                'multa' => (string) ($current['multa'] ?? 'R$ 0,00'),
                'multa_cents' => $multaCents,
                'total' => (string) ($current['total'] ?? $parcela['total'] ?? ''),
                'total_cents' => (int) ($current['total_cents'] ?? $parcela['total_cents'] ?? 0),
                'valor_recebido' => $service->formatMoney($currentTotalCents),
                'valor_recebido_cents' => $currentTotalCents,
                'saldo_restante' => $service->formatMoney(0),
                'saldo_restante_cents' => 0,
                'principal_pago_cents' => (int) ($current['amortizacao_cents'] ?? 0),
            ]);
            $parcela->save();
        }

        $totalInstallments = Parcela::query()->whereIn('emprestimo_id', $loanIdValues)->count();
        $paidInstallments = Parcela::query()
            ->whereIn('emprestimo_id', $loanIdValues)
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->count();

        $todayIso = CarbonImmutable::now()->startOfDay()->format('Y-m-d');
        $hasOverdue = Parcela::query()
            ->whereIn('emprestimo_id', $loanIdValues)
            ->whereNotIn('status', ['pago', 'pago_parcial'])
            ->where('vencimento', '<', $todayIso)
            ->exists();

        $loanStatus = $paidInstallments >= $totalInstallments && $totalInstallments > 0
            ? 'quitado'
            : ($hasOverdue ? 'atrasado' : 'em_dia');

        $loan->fill(['status' => $loanStatus]);
        $loan->save();

        return response()->json(['ok' => true]);
    }

    private function resolveNextInstallmentData(Emprestimo $loan, array $loanIdValues): array
    {
        $lastDueIso = (string) Parcela::query()
            ->whereIn('emprestimo_id', $loanIdValues)
            ->max('vencimento');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDueIso)) {
            $lastDueIso = (string) ($loan['vencimento'] ?? '');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastDueIso)) {
            $lastDueIso = CarbonImmutable::now()->startOfDay()->format('Y-m-d');
        }

        $nextDue = $this->calculateNextDueDate($lastDueIso, $loan);
        $nextNumber = (int) Parcela::query()
            ->whereIn('emprestimo_id', $loanIdValues)
            ->max('numero') + 1;

        return [
            'next_due' => $nextDue,
            'next_number' => $nextNumber,
        ];
    }

    private function calculateNextDueDate(string $lastDueIso, Emprestimo $loan): CarbonImmutable
    {
        $intervalo = (string) ($loan['intervalo'] ?? 'mensal');
        $excecoes = array_values(array_filter((array) ($loan['excecoes_dia'] ?? [])));

        $nextDue = CarbonImmutable::createFromFormat('Y-m-d', $lastDueIso)->startOfDay();
        $nextDue = match ($intervalo) {
            'diario' => $nextDue->addDay(),
            'semanal' => $nextDue->addDays(7),
            'quinzenal' => $nextDue->addDays(15),
            default => $nextDue->addMonthsNoOverflow(1),
        };

        $holidayDates = [];
        if (in_array('anular_feriados', $excecoes, true)) {
            $holidayDates = Feriado::query()
                ->where('date', '>=', $nextDue->format('Y-m-d'))
                ->where('date', '<=', $nextDue->addDays(14)->format('Y-m-d'))
                ->pluck('date')
                ->values()
                ->all();
        }

        while (true) {
            if (in_array('anular_sabados', $excecoes, true) && $nextDue->isSaturday()) {
                $nextDue = $nextDue->addDay();
                continue;
            }

            if (in_array('anular_domingos', $excecoes, true) && $nextDue->isSunday()) {
                $nextDue = $nextDue->addDay();
                continue;
            }

            if (in_array('anular_feriados', $excecoes, true) && in_array($nextDue->format('Y-m-d'), $holidayDates, true)) {
                $nextDue = $nextDue->addDay();
                continue;
            }

            return $nextDue;
        }
    }
}
