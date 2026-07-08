<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos\Parcelas;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Services\Loans\LoanScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class IndexController extends Controller
{
    public function __invoke(string $id, LoanScheduleService $service): JsonResponse
    {
        $loan = Emprestimo::query()->findOrFail($id);
        Gate::authorize('access-emprestimo', $loan);
        $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
        $loanIdValues = [$loanId];
        if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
            $loanIdValues[] = new ObjectId($loanId);
        }

        $installmentsCollection = Parcela::query()
            ->whereIn('emprestimo_id', $loanIdValues)
            ->orderBy('numero')
            ->get();

        $grouped = $installmentsCollection->groupBy('numero');
        $hasDuplicates = $grouped->contains(fn ($items) => $items->count() > 1);

        $installmentsCollection = $grouped
            ->map(function ($items) {
                return $items->firstWhere('status', 'pago') ?? $items->first();
            })
            ->values();

        if ($installmentsCollection->isEmpty()) {
            $principalCents = (int) ($loan['valor_cents'] ?? 0);
            if ($principalCents <= 0) {
                $principalCents = $service->parseMoneyToCents((string) ($loan['valor'] ?? ''));
            }

            $taxaPercent = (float) ($loan['taxa_juros_percent'] ?? 0.0);
            if ($taxaPercent <= 0) {
                $taxaPercent = $service->parsePercent((string) ($loan['taxa_juros'] ?? ''));
            }

            $scheduleResult = $service->buildSchedule([
                'principal_cents' => $principalCents,
                'numero_parcelas' => (int) ($loan['numero_parcelas'] ?? 0),
                'tipo_juros' => (string) ($loan['tipo_juros'] ?? 'simples'),
                'taxa_juros_percent' => $taxaPercent,
                'intervalo' => (string) ($loan['intervalo'] ?? 'mensal'),
                'data_emprestimo' => (string) ($loan['data_emprestimo'] ?? ''),
                'excecoes_dia' => (array) ($loan['excecoes_dia'] ?? []),
            ]);

            $installments = collect((array) ($scheduleResult['schedule'] ?? []))
                ->map(function ($installment) {
                    return [
                        'id' => '',
                        'numero' => (int) ($installment['numero'] ?? 0),
                        'vencimento' => (string) ($installment['vencimento'] ?? ''),
                        'amortizacao' => (string) ($installment['amortizacao'] ?? ''),
                        'amortizacao_cents' => (int) ($installment['amortizacao_cents'] ?? 0),
                        'juros' => (string) ($installment['juros'] ?? ''),
                        'juros_cents' => (int) ($installment['juros_cents'] ?? 0),
                        'multa' => (string) ($installment['multa'] ?? 'R$ 0,00'),
                        'multa_cents' => 0,
                        'total' => (string) ($installment['total'] ?? ''),
                        'total_cents' => (int) ($installment['total_cents'] ?? 0),
                        'principal_pago_cents' => 0,
                        'status' => 'pendente',
                        'recebida_em' => '',
                    ];
                })
                ->all();

            $decorated = $service->decorateInstallments($installments, $loan->toArray());

            return response()->json([
                'ok' => true,
                'needs_repair' => true,
                'loan' => [
                    'id' => $loanId,
                    'cliente' => (string) ($loan['cliente'] ?? ''),
                    'numero_parcelas' => (int) ($loan['numero_parcelas'] ?? 0),
                    'tipo_juros' => (string) ($loan['tipo_juros'] ?? 'simples'),
                    'intervalo' => (string) ($loan['intervalo'] ?? 'mensal'),
                    'taxa_juros' => (string) ($loan['taxa_juros'] ?? ''),
                    'taxa_juros_percent' => (float) ($loan['taxa_juros_percent'] ?? 0.0),
                    'tipo_multa' => (string) ($loan['tipo_multa'] ?? 'percentual'),
                    'valor_multa' => (string) ($loan['valor_multa'] ?? ''),
                    'cobranca_multa' => (string) ($loan['cobranca_multa'] ?? 'automatica'),
                ],
                'installments' => $decorated,
            ]);
        }

        $installments = $installmentsCollection
            ->map(function ($parcela) {
                return [
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
                    'principal_pago_cents' => (int) ($parcela['principal_pago_cents'] ?? 0),
                    'status' => (string) ($parcela['status'] ?? 'pendente'),
                    'recebida_em' => (string) ($parcela['recebida_em'] ?? ''),
                ];
            })
            ->all();

        $decorated = $service->decorateInstallments($installments, $loan->toArray());

        return response()->json([
            'ok' => true,
            'needs_repair' => $hasDuplicates,
            'loan' => [
                'id' => $loanId,
                'cliente' => (string) ($loan['cliente'] ?? ''),
                'numero_parcelas' => (int) ($loan['numero_parcelas'] ?? 0),
                'tipo_juros' => (string) ($loan['tipo_juros'] ?? 'simples'),
                'intervalo' => (string) ($loan['intervalo'] ?? 'mensal'),
                'taxa_juros' => (string) ($loan['taxa_juros'] ?? ''),
                'taxa_juros_percent' => (float) ($loan['taxa_juros_percent'] ?? 0.0),
                'tipo_multa' => (string) ($loan['tipo_multa'] ?? 'percentual'),
                'valor_multa' => (string) ($loan['valor_multa'] ?? ''),
                'cobranca_multa' => (string) ($loan['cobranca_multa'] ?? 'automatica'),
            ],
            'installments' => $decorated,
        ]);
    }
}
