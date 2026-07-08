<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\LancamentoFinanceiro;
use App\Models\Parcela;
use App\Services\Loans\LoanScheduleService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(LoanScheduleService $loanScheduleService): View
    {
        $today = now();
        $todayLabel = $today->format('d/m/Y');
        $monthStart = $today->copy()->startOfMonth()->format('Y-m-d');
        $todayIso = $today->format('Y-m-d');

        $clientes = Cliente::count();
        $emprestimos = Emprestimo::count();
        $entradas = (float) LancamentoFinanceiro::where('type', 'entrada')->sum('value');
        $saidas = (float) LancamentoFinanceiro::where('type', 'saida')->sum('value');

        $resultMonthInstallments = Parcela::query()
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->where('recebida_em', '>=', $monthStart)
            ->where('recebida_em', '<=', $todayIso)
            ->get();

        $resultado = (int) $resultMonthInstallments->sum(fn ($parcela) => (int) ($parcela['total_cents'] ?? 0));
        $jurosRecebidos = (int) $resultMonthInstallments->sum(fn ($parcela) => (int) ($parcela['juros_cents'] ?? 0));
        $multasRecebidas = (int) $resultMonthInstallments->sum(fn ($parcela) => (int) ($parcela['multa_cents'] ?? 0));

        $openMonthInstallments = Parcela::query()
            ->where('vencimento', '>=', $monthStart)
            ->where('vencimento', '<=', $today->copy()->endOfMonth()->format('Y-m-d'))
            ->get()
            ->groupBy(fn ($parcela) => (string) ($parcela['emprestimo_id'] ?? '') . ':' . (int) ($parcela['numero'] ?? 0))
            ->map(fn ($items) => $items->firstWhere('status', 'pago') ?? $items->last())
            ->values();

        $totalEmAberto = (int) $openMonthInstallments
            ->reject(fn ($parcela) => in_array((string) ($parcela['status'] ?? ''), ['pago', 'pago_parcial'], true))
            ->sum(fn ($parcela) => (int) ($parcela['total_cents'] ?? 0));

        $yearStart = $today->copy()->startOfYear()->format('Y-m-d');
        $yearEnd = $today->copy()->endOfYear()->format('Y-m-d');
        $monthLabels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $monthlyRevenueMap = Parcela::query()
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->where('recebida_em', '>=', $yearStart)
            ->where('recebida_em', '<=', $yearEnd)
            ->get()
            ->groupBy(function ($parcela) {
                $receivedAt = (string) ($parcela['recebida_em'] ?? '');

                return preg_match('/^\d{4}-(\d{2})-\d{2}$/', $receivedAt, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->map(fn ($items) => (int) $items->sum(fn ($parcela) => (int) ($parcela['total_cents'] ?? 0)));

        $monthlyRevenue = collect(range(1, 12))
            ->map(fn (int $monthNumber) => [
                'month' => $monthLabels[$monthNumber - 1],
                'amount' => ((int) ($monthlyRevenueMap->get($monthNumber, 0))) / 100,
            ])
            ->all();

        $monthlyInvestedMap = Emprestimo::query()
            ->where('data_emprestimo', '>=', $yearStart)
            ->where('data_emprestimo', '<=', $yearEnd)
            ->get()
            ->groupBy(function ($emprestimo) {
                $loanDate = (string) ($emprestimo['data_emprestimo'] ?? '');

                return preg_match('/^\d{4}-(\d{2})-\d{2}$/', $loanDate, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->map(fn ($items) => (int) $items->sum(fn ($emprestimo) => (int) ($emprestimo['valor_cents'] ?? 0)));

        $monthlyInvested = collect(range(1, 12))
            ->map(fn (int $monthNumber) => [
                'month' => $monthLabels[$monthNumber - 1],
                'amount' => ((int) ($monthlyInvestedMap->get($monthNumber, 0))) / 100,
            ])
            ->all();

        $receivedInstallments = Parcela::query()
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->get()
            ->groupBy(fn ($parcela) => (string) ($parcela['emprestimo_id'] ?? '') . ':' . (int) ($parcela['numero'] ?? 0))
            ->map(fn ($items) => $items->first())
            ->values();

        $paidInstallmentsCount = (int) Parcela::query()
            ->where('status', 'pago')
            ->count();
        $todayCollectedCents = (int) $receivedInstallments
            ->where('recebida_em', $todayIso)
            ->sum(fn ($parcela) => (int) ($parcela['total_cents'] ?? 0));

        $latestUpdate = collect([
            Cliente::query()->orderBy('updated_at', 'desc')->first()?->updated_at,
            Emprestimo::query()->orderBy('updated_at', 'desc')->first()?->updated_at,
            Parcela::query()->orderBy('updated_at', 'desc')->first()?->updated_at,
            LancamentoFinanceiro::query()->orderBy('updated_at', 'desc')->first()?->updated_at,
        ])->filter()->sortDesc()->first();

        $lastUpdateLabel = $latestUpdate
            ? $latestUpdate->locale('pt_BR')->diffForHumans($today, [
                'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
                'parts' => 1,
            ])
            : 'Sem atualizacoes';

        $dueTodayLoans = $this->buildInstallmentActivityList(
            Parcela::query()
                ->whereNotIn('status', ['pago', 'pago_parcial'])
                ->where('vencimento', $todayIso)
                ->get(),
            $loanScheduleService,
            false
        );

        $overdueInstallments = $this->buildInstallmentActivityList(
            Parcela::query()
                ->whereNotIn('status', ['pago', 'pago_parcial'])
                ->where('vencimento', '<', $todayIso)
                ->get(),
            $loanScheduleService,
            true
        );

        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Clientes', 'value' => (string) $clientes, 'trend' => 'Cadastros'],
                ['label' => 'Empréstimos', 'value' => (string) $emprestimos, 'trend' => 'Registros'],
                ['label' => 'Entradas', 'value' => 'R$ ' . number_format($entradas, 2, ',', '.'), 'trend' => 'Financeiro'],
                ['label' => 'Saídas', 'value' => 'R$ ' . number_format($saidas, 2, ',', '.'), 'trend' => 'Financeiro'],
                ['label' => 'Resultado recebido', 'value' => $loanScheduleService->formatMoney($resultado), 'trend' => 'Recebido'],
                ['label' => 'Juros recebidos', 'value' => $loanScheduleService->formatMoney($jurosRecebidos), 'trend' => 'Recebido'],
                ['label' => 'Multas recebidas', 'value' => $loanScheduleService->formatMoney($multasRecebidas), 'trend' => 'Recebido'],
                ['label' => 'Total em aberto', 'value' => $loanScheduleService->formatMoney($totalEmAberto), 'trend' => 'Em aberto'],
            ],
            'activities' => [
                ['title' => 'Novo cadastro aprovado', 'time' => 'Ha 5 minutos'],
                ['title' => 'Relatorio mensal gerado', 'time' => 'Ha 20 minutos'],
                ['title' => 'Backup concluido', 'time' => 'Ha 1 hora'],
            ],
            'todayLabel' => $todayLabel,
            'dueTodayLoans' => $dueTodayLoans,
            'overdueInstallments' => $overdueInstallments,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyInvested' => $monthlyInvested,
            'miniStats' => [
                ['label' => 'Total de parcelas pagas', 'value' => $paidInstallmentsCount . ' parcelas'],
                ['label' => 'Total de dinheiro arrecadado hoje', 'value' => $loanScheduleService->formatMoney($todayCollectedCents)],
                ['label' => 'Ultima atualizacao', 'value' => $lastUpdateLabel],
            ],
        ]);
    }

    private function buildInstallmentActivityList($installments, LoanScheduleService $loanScheduleService, bool $includeDueDate): array
    {
        $loansById = Emprestimo::query()
            ->get()
            ->keyBy(fn ($loan) => (string) ($loan->id ?? $loan->getKey() ?? ''));

        return $installments
            ->groupBy(fn ($parcela) => (string) ($parcela['emprestimo_id'] ?? '') . ':' . (int) ($parcela['numero'] ?? 0))
            ->map(fn ($items) => $items->last())
            ->map(function ($parcela) use ($loansById, $loanScheduleService, $includeDueDate) {
                $loanId = (string) ($parcela['emprestimo_id'] ?? '');
                $loan = $loansById->get($loanId);

                if (!$loan) {
                    return null;
                }

                $decorated = $loanScheduleService->decorateInstallments([[
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
                ]], $loan->toArray())[0] ?? null;

                if (!$decorated) {
                    return null;
                }

                $numero = (int) ($decorated['numero'] ?? 0);
                $totalParcelas = max((int) ($loan['numero_parcelas'] ?? 0), $numero);
                $item = [
                    'cliente' => (string) ($loan['cliente'] ?? 'Cliente nao identificado'),
                    'valor' => (string) ($decorated['total'] ?? $loanScheduleService->formatMoney((int) ($decorated['total_cents'] ?? 0))),
                    'parcela' => 'Parcela ' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT) . '/' . str_pad((string) $totalParcelas, 2, '0', STR_PAD_LEFT),
                    'vencimento' => $loanScheduleService->isoToDisplay((string) ($decorated['vencimento'] ?? '')),
                    'vencimento_iso' => (string) ($decorated['vencimento'] ?? ''),
                    'numero' => $numero,
                ];

                return $item;
            })
            ->filter()
            ->sortBy(fn (array $item) => ($item['vencimento_iso'] ?? '') . '-' . str_pad((string) ($item['numero'] ?? 0), 4, '0', STR_PAD_LEFT))
            ->values()
            ->map(function (array $item) use ($includeDueDate) {
                unset($item['vencimento_iso'], $item['numero']);

                if (!$includeDueDate) {
                    unset($item['vencimento']);
                }

                return $item;
            })
            ->all();
    }
}
