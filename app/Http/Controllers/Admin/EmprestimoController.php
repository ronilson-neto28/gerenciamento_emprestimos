<?php

namespace App\Http\Controllers\Admin;

use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

class EmprestimoController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('busca', ''));
        $search = mb_substr($search, 0, 120);
        $cobrador = trim((string) $request->query('cobrador', ''));
        $cobrador = mb_substr($cobrador, 0, 120);

        $allowedStatus = ['todos', 'em_dia', 'atrasado', 'analise', 'quitado'];
        $status = (string) $request->query('status', 'todos');
        $status = in_array($status, $allowedStatus, true) ? $status : 'todos';

        $baseQuery = AdminAccess::visibleLoanQuery($request->user());
        $query = clone $baseQuery;

        if ($search !== '') {
            $regex = new Regex(preg_quote($search), 'i');

            $query->where(function ($q) use ($regex) {
                $q->orWhere('cliente', 'regex', $regex)
                    ->orWhere('cobrador', 'regex', $regex)
                    ->orWhere('valor', 'regex', $regex)
                    ->orWhere('parcelas', 'regex', $regex)
                    ->orWhere('vencimento', 'regex', $regex)
                    ->orWhere('tipo', 'regex', $regex)
                    ->orWhere('status', 'regex', $regex);
            });
        }

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if ($cobrador !== '') {
            $query->where('cobrador', 'regex', new Regex(preg_quote($cobrador), 'i'));
        }

        $filteredLoans = $query->orderBy('created_at', 'desc')->get();

        $ownerId = AdminAccess::resolveOwnerId($request->user());
        if ($ownerId !== '' && $filteredLoans->isNotEmpty()) {
            $loanIds = $filteredLoans
                ->map(fn (Emprestimo $loan) => (string) ($loan->id ?? $loan->getKey() ?? ''))
                ->filter(fn (string $value) => $value !== '')
                ->values()
                ->all();

            if ($loanIds !== []) {
                $loanIdValues = $loanIds;
                foreach ($loanIds as $loanId) {
                    if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
                        $loanIdValues[] = new ObjectId($loanId);
                    }
                }

                $todayIso = CarbonImmutable::now()->startOfDay()->format('Y-m-d');
                $overdueLoanIds = Parcela::query()
                    ->where('owner_id', $ownerId)
                    ->whereIn('emprestimo_id', $loanIdValues)
                    ->whereNotIn('status', ['pago', 'pago_parcial'])
                    ->where('vencimento', '<', $todayIso)
                    ->pluck('emprestimo_id')
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values()
                    ->all();

                $overdueLookup = array_flip($overdueLoanIds);

                $filteredLoans->each(function (Emprestimo $loan) use ($overdueLookup) {
                    $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
                    if ($loanId === '') {
                        return;
                    }

                    $currentStatus = (string) ($loan['status'] ?? '');
                    $isOverdue = isset($overdueLookup[$loanId]);

                    if ($currentStatus !== 'quitado' && $isOverdue) {
                        $loan->setAttribute('status', 'atrasado');
                        return;
                    }

                    if ($currentStatus === 'atrasado' && !$isOverdue) {
                        $loan->setAttribute('status', 'em_dia');
                    }
                });
            }
        }

        $summaryQuery = AdminAccess::visibleLoanQuery($request->user());
        $clientNames = AdminAccess::visibleClientQuery($request->user())
            ->orderBy('nome')
            ->pluck('nome')
            ->values();

        return view('admin.emprestimo', [
            'loans' => $filteredLoans,
            'clients' => $clientNames,
            'filters' => [
                'busca' => $search,
                'cobrador' => $cobrador,
                'status' => $status,
            ],
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'filtrados' => $filteredLoans->count(),
                'ativos' => (clone $summaryQuery)->whereIn('status', ['em_dia', 'atrasado', 'analise'])->count(),
            ],
        ]);
    }
}
