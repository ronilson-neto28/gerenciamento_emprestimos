<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos\Parcelas;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Services\Loans\LoanScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class SyncController extends Controller
{
    public function __invoke(string $id, LoanScheduleService $service): JsonResponse
    {
        $loan = Emprestimo::query()->findOrFail($id);
        Gate::authorize('access-emprestimo', $loan);
        $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
        $loanIdValues = [$loanId];

        if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
            $legacyId = new ObjectId($loanId);
            $loanIdValues[] = $legacyId;

            $hasString = Parcela::query()->where('emprestimo_id', $loanId)->exists();
            if ($hasString) {
                Parcela::query()->where('emprestimo_id', $legacyId)->where('status', '!=', 'pago')->delete();
                Parcela::query()->where('emprestimo_id', $legacyId)->where('status', 'pago')->update(['emprestimo_id' => $loanId]);
            } else {
                Parcela::query()->where('emprestimo_id', $legacyId)->update(['emprestimo_id' => $loanId]);
            }
        }

        $existingByNumero = Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->get()
            ->groupBy('numero');

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

        $schedule = (array) ($scheduleResult['schedule'] ?? []);
        if (!$schedule) {
            return response()->json(['ok' => false, 'message' => 'Não foi possível sincronizar as parcelas.'], 422);
        }

        $paidInstallments = Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->get()
            ->keyBy('numero');

        $scheduleNumbers = collect($schedule)
            ->map(fn ($item) => (int) ($item['numero'] ?? 0))
            ->filter(fn ($numero) => $numero > 0)
            ->values()
            ->all();

        Parcela::query()
            ->where('emprestimo_id', $loanId)
            ->whereNotIn('status', ['pago', 'pago_parcial'])
            ->whereNotIn('numero', $scheduleNumbers)
            ->delete();

        foreach ($schedule as $installment) {
            $numero = (int) ($installment['numero'] ?? 0);
            if ($numero <= 0 || $paidInstallments->has($numero)) {
                continue;
            }

            Parcela::updateOrCreate([
                'emprestimo_id' => $loanId,
                'numero' => $numero,
            ], [
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
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
