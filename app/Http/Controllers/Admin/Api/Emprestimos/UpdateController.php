<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Emprestimos\UpdateEmprestimoRequest;
use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Services\Loans\LoanScheduleService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class UpdateController extends Controller
{
    public function __invoke(UpdateEmprestimoRequest $request, string $id, LoanScheduleService $service): JsonResponse
    {
        $loan = Emprestimo::query()->findOrFail($id);
        Gate::authorize('access-emprestimo', $loan);
        $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
        $data = $request->validated();
        $authUser = $request->user();

        if ($loanId === '') {
            return response()->json(['ok' => false, 'message' => 'ID do empréstimo inválido.'], 500);
        }

        if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
            $legacyId = new ObjectId($loanId);
            $hasString = Parcela::query()->where('emprestimo_id', $loanId)->exists();
            if ($hasString) {
                Parcela::query()->where('emprestimo_id', $legacyId)->where('status', '!=', 'pago')->delete();
                Parcela::query()->where('emprestimo_id', $legacyId)->where('status', 'pago')->update(['emprestimo_id' => $loanId]);
            } else {
                Parcela::query()->where('emprestimo_id', $legacyId)->update(['emprestimo_id' => $loanId]);
            }
        }

        $existingCollection = Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->get();

        $existingByNumero = $existingCollection->groupBy('numero');
        foreach ($existingByNumero as $items) {
            if ($items->count() <= 1) {
                continue;
            }

            $keep = $items->firstWhere('status', 'pago') ?? $items->last();
            foreach ($items as $parcela) {
                if ((string) $parcela->getKey() === (string) $keep->getKey()) {
                    continue;
                }
                $parcela->delete();
            }
        }

        $existingInstallments = Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->get()
            ->keyBy('numero');

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

        $paidInstallments = Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->get()
            ->keyBy('numero');

        $maxPaid = (int) ($paidInstallments->keys()->max() ?? 0);
        $newTotalInstallments = (int) $data['numero_parcelas'];

        if ($maxPaid > $newTotalInstallments) {
            return response()->json(['ok' => false, 'message' => 'Já existem parcelas pagas acima do novo número de parcelas.'], 422);
        }

        $scheduleNumbers = collect($schedule)
            ->map(fn ($i) => (int) ($i['numero'] ?? 0))
            ->filter(fn ($n) => $n > 0)
            ->values()
            ->all();

        Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->whereNotIn('status', ['pago', 'pago_parcial'])
            ->whereNotIn('numero', $scheduleNumbers)
            ->delete();

        foreach ($schedule as $installment) {
            $numero = (int) ($installment['numero'] ?? 0);
            if ($numero <= 0) {
                continue;
            }
            if ($paidInstallments->has($numero)) {
                continue;
            }

            $payload = [
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
            ];

            $existing = $existingInstallments->get($numero);
            if ($existing) {
                $existing->fill($payload);
                $existing->save();
                continue;
            }

            Parcela::create(['emprestimo_id' => $loanId, 'numero' => $numero] + $payload);
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

        $loan->fill([
            'cliente_id' => $clienteModel?->getKey(),
            'cliente' => $clienteName,
            'valor' => $service->formatMoney($principalCents),
            'valor_cents' => $principalCents,
            'parcelas' => $newTotalInstallments . 'x ' . $service->formatMoney($firstInstallmentCents),
            'numero_parcelas' => $newTotalInstallments,
            'vencimento' => $lastDueIso,
            'tipo' => ucfirst((string) $data['tipo_juros']),
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

        $loan->save();

        return response()->json([
            'ok' => true,
            'id' => $loanId,
        ]);
    }
}
