<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\LancamentoFinanceiro;
use App\Models\Parcela;
use Illuminate\View\View;

class FinanceiroController extends Controller
{
    public function __invoke(): View
    {
        $entradas = (float) LancamentoFinanceiro::where('type', 'entrada')->sum('value');
        $saidas = (float) LancamentoFinanceiro::where('type', 'saida')->sum('value');
        $principalEmprestadoCents = (int) Emprestimo::query()->sum('valor_cents');
        $principalPagoCents = (int) Parcela::query()->sum('principal_pago_cents');
        $principalPagoLegacyCents = (int) Parcela::query()
            ->where('status', 'pago')
            ->whereNull('principal_pago_cents')
            ->sum('amortizacao_cents');
        $principalPagoCents += $principalPagoLegacyCents;
        $emprestimosAbertosCents = max($principalEmprestadoCents - $principalPagoCents, 0);
        $emprestimosAbertos = $emprestimosAbertosCents / 100;
        $capital = ($entradas - $saidas) - $emprestimosAbertos;
        $lancamentos = LancamentoFinanceiro::query()
            ->orderBy('date', 'desc')
            ->orderBy('_id', 'desc')
            ->limit(50)
            ->get();

        return view('admin.financeiro', [
            'summary' => [
                'entradas' => $entradas,
                'saidas' => $saidas,
                'investimentos' => $emprestimosAbertos,
                'resultado' => $capital,
            ],
            'lancamentos' => $lancamentos,
        ]);
    }
}
