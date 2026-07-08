<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Services\Loans\LoanScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShowController extends Controller
{
    public function __invoke(string $id, LoanScheduleService $service): JsonResponse
    {
        $loan = Emprestimo::query()->findOrFail($id);
        Gate::authorize('access-emprestimo', $loan);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => (string) ($loan->id ?? $loan->getKey() ?? ''),
                'cliente' => (string) ($loan['cliente'] ?? ''),
                'data_emprestimo' => (string) ($loan['data_emprestimo'] ?? ''),
                'data_emprestimo_display' => $service->isoToDisplay((string) ($loan['data_emprestimo'] ?? '')),
                'valor' => (string) ($loan['valor'] ?? ''),
                'parcelas' => (string) ($loan['parcelas'] ?? ''),
                'numero_parcelas' => (int) ($loan['numero_parcelas'] ?? 0),
                'vencimento' => (string) ($loan['vencimento'] ?? ''),
                'vencimento_display' => $service->isoToDisplay((string) ($loan['vencimento'] ?? '')),
                'tipo' => (string) ($loan['tipo'] ?? ''),
                'taxa_juros' => (string) ($loan['taxa_juros'] ?? ''),
                'tipo_juros' => (string) ($loan['tipo_juros'] ?? 'simples'),
                'intervalo' => (string) ($loan['intervalo'] ?? 'mensal'),
                'tipo_multa' => (string) ($loan['tipo_multa'] ?? 'percentual'),
                'valor_multa' => (string) ($loan['valor_multa'] ?? ''),
                'cobranca_multa' => (string) ($loan['cobranca_multa'] ?? 'automatica'),
                'cobrador' => (string) ($loan['cobrador'] ?? ''),
                'excecoes_dia' => (array) ($loan['excecoes_dia'] ?? []),
                'observacoes' => (string) ($loan['observacoes'] ?? ''),
                'status' => (string) ($loan['status'] ?? ''),
            ],
        ]);
    }
}
