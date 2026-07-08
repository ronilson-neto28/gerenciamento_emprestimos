<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Emprestimos\StoreEmprestimoRequest;
use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Services\Loans\LoanScheduleService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class StoreController extends Controller
{
    public function __invoke(StoreEmprestimoRequest $request, LoanScheduleService $service): JsonResponse
    {
        Gate::authorize('create-emprestimos');

        $data = $request->validated();
        $authUser = $request->user();
        $authUserId = (string) ($authUser?->id ?? $authUser?->getKey() ?? '');

        $principalCents = $service->parseMoneyToCents((string) $data['valor_emprestimo']);
        $taxaPercent = $service->parsePercent((string) ($data['taxa_juros'] ?? ''));

        $scheduleResult = $service->buildSchedule([
            'principal_cents' => $principalCents,
            'numero_parcelas' => (int) $data['numero_parcelas'],
            'tipo_juros' => (string) $data['tipo_juros'],
            'taxa_juros_percent' => $taxaPercent,
            'intervalo' => (string) $data['intervalo'],
            'data_emprestimo' => (string) $data['data_emprestimo'],
            'excecoes_dia' => (array) ($data['excecoes_dia'] ?? []),
        ]);

        $schedule = $scheduleResult['schedule'];
        $totalCents = (int) ($scheduleResult['total_cents'] ?? 0);

        if (!$schedule) {
            return response()->json(['ok' => false, 'message' => 'Não foi possível gerar o cronograma.'], 422);
        }

        $clienteName = trim((string) $data['cliente']);
        $clienteModel = AdminAccess::visibleClientQuery($authUser)->where('nome', $clienteName)->first();

        if (!$clienteModel) {
            return response()->json(['ok' => false, 'message' => 'Cliente inválido ou sem permissão de acesso.'], 422);
        }

        $assignedCobrador = AdminAccess::isAdmin($authUser)
            ? AdminAccess::findAssignedCobrador((string) ($data['cobrador'] ?? ''))
            : $authUser;
        $cobradorName = AdminAccess::isAdmin($authUser)
            ? trim((string) ($assignedCobrador?->name ?? ($data['cobrador'] ?? '')))
            : trim((string) ($authUser?->name ?? ''));
        $cobradorUserId = (string) ($assignedCobrador?->id ?? $assignedCobrador?->getKey() ?? '');

        $firstInstallmentCents = (int) ($schedule[0]['total_cents'] ?? 0);
        $lastInstallment = end($schedule);
        $lastDueIso = (string) ($lastInstallment['vencimento'] ?? '');

        $loan = new Emprestimo([
            'cliente_id' => $clienteModel?->getKey(),
            'created_by' => $authUserId,
            'cliente' => $clienteName,
            'valor' => $service->formatMoney($principalCents),
            'valor_cents' => $principalCents,
            'parcelas' => ((int) $data['numero_parcelas']) . 'x ' . $service->formatMoney($firstInstallmentCents),
            'numero_parcelas' => (int) $data['numero_parcelas'],
            'vencimento' => $lastDueIso,
            'tipo' => ucfirst((string) $data['tipo_juros']),
            'status' => 'em_dia',
            'data_emprestimo' => (string) $data['data_emprestimo'],
            'taxa_juros' => trim((string) ($data['taxa_juros'] ?? '')),
            'taxa_juros_percent' => $taxaPercent,
            'taxa_juros_rate' => $service->percentToRate($taxaPercent),
            'tipo_juros' => (string) $data['tipo_juros'],
            'intervalo' => (string) $data['intervalo'],
            'tipo_multa' => (string) $data['tipo_multa'],
            'valor_multa' => trim((string) ($data['valor_multa'] ?? '')),
            'cobranca_multa' => (string) $data['cobranca_multa'],
            'cobrador' => $cobradorName,
            'cobrador_user_id' => $cobradorUserId,
            'excecoes_dia' => array_values(array_filter((array) ($data['excecoes_dia'] ?? []))),
            'observacoes' => trim((string) ($data['observacoes'] ?? '')),
            'parcela_cents' => $firstInstallmentCents,
            'total_cents' => $totalCents,
        ]);
        $loan->setAttribute('_id', new ObjectId());
        $loan->save();

        $loanId = (string) ($loan->id ?? $loan->getAttribute('_id') ?? $loan->getKey() ?? '');
        if ($loanId === '') {
            return response()->json(['ok' => false, 'message' => 'Falha ao gerar o ID do empréstimo.'], 500);
        }

        foreach ($schedule as $installment) {
            $numero = (int) ($installment['numero'] ?? 0);
            if ($numero <= 0) {
                continue;
            }

            Parcela::updateOrCreate([
                'emprestimo_id' => $loanId,
                'numero' => $numero,
            ], [
                'vencimento' => (string) $installment['vencimento'],
                'amortizacao' => (string) $installment['amortizacao'],
                'amortizacao_cents' => (int) $installment['amortizacao_cents'],
                'juros' => (string) $installment['juros'],
                'juros_cents' => (int) $installment['juros_cents'],
                'multa' => (string) $installment['multa'],
                'multa_cents' => 0,
                'total' => (string) $installment['total'],
                'total_cents' => (int) $installment['total_cents'],
                'principal_pago_cents' => 0,
                'status' => 'pendente',
            ]);
        }

        return response()->json([
            'ok' => true,
            'id' => $loanId,
        ]);
    }
}
