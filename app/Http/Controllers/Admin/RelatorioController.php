<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\Loans\LoanScheduleService;
use App\Support\AdminAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use MongoDB\BSON\ObjectId;

class RelatorioController extends Controller
{
    public function __invoke(Request $request, LoanScheduleService $service): View
    {
        Gate::authorize('view-relatorios');

        $authUser = $request->user();
        $today = CarbonImmutable::now()->startOfDay();
        $from = trim((string) $request->query('de', $today->startOfMonth()->format('Y-m-d')));
        $to = trim((string) $request->query('ate', $today->format('Y-m-d')));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = $today->startOfMonth()->format('Y-m-d');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $today->format('Y-m-d');
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $isAdmin = AdminAccess::isAdmin($authUser);
        $ownerId = AdminAccess::resolveOwnerId($authUser);
        $filterCobradorId = $isAdmin
            ? trim((string) $request->query('cobrador', ''))
            : (string) ($authUser?->id ?? $authUser?->getKey() ?? '');

        if ($ownerId === '') {
            return view('admin.relatorios', [
                'isAdmin' => $isAdmin,
                'rows' => collect(),
                'activities' => collect(),
                'cobradores' => collect(),
                'filters' => [
                    'de' => $from,
                    'ate' => $to,
                    'cobrador' => $filterCobradorId,
                ],
                'summary' => [
                    'operadores' => 0,
                    'total_recebido' => $service->formatMoney(0),
                    'recebimentos' => 0,
                    'emprestimos' => 0,
                ],
            ]);
        }

        $cobradores = User::query()
            ->where('role', 'cobrador')
            ->where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        $receiptsQuery = Recebimento::query()
            ->where('owner_id', $ownerId)
            ->where('recebido_em', '>=', $from)
            ->where('recebido_em', '<=', $to)
            ->orderBy('recebido_em', 'desc');

        if ($filterCobradorId !== '') {
            $receiptsQuery->where(function ($query) use ($filterCobradorId) {
                $query->orWhere('cobrador_user_id', $filterCobradorId)
                    ->orWhere('user_id', $filterCobradorId);
            });
        }

        $receipts = $receiptsQuery->get();
        $loanIds = $receipts
            ->pluck('emprestimo_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->unique()
            ->all();

        $loanIdValues = $loanIds;
        foreach ($loanIds as $loanId) {
            if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
                $loanIdValues[] = new ObjectId($loanId);
            }
        }

        $loansById = Emprestimo::query()
            ->where('owner_id', $ownerId)
            ->when($loanIdValues !== [], fn ($query) => $query->whereIn('_id', $loanIdValues))
            ->get()
            ->keyBy(fn ($loan) => (string) ($loan->id ?? $loan->getKey() ?? ''));

        $resolvedReceipts = $receipts->map(function ($receipt) use ($loansById) {
            $cobradorUserId = trim((string) ($receipt['cobrador_user_id'] ?? ''));
            $cobradorName = trim((string) ($receipt['cobrador'] ?? ''));

            if ($cobradorUserId === '' || $cobradorName === '') {
                $loanId = trim((string) ($receipt['emprestimo_id'] ?? ''));
                $loan = $loanId !== '' ? $loansById->get($loanId) : null;
                if ($loan) {
                    if ($cobradorUserId === '') {
                        $cobradorUserId = trim((string) ($loan['cobrador_user_id'] ?? ''));
                    }
                    if ($cobradorName === '') {
                        $cobradorName = trim((string) ($loan['cobrador'] ?? ''));
                    }
                }
            }

            $receipt->setAttribute('resolved_cobrador_user_id', $cobradorUserId);
            $receipt->setAttribute('resolved_cobrador', $cobradorName);

            return $receipt;
        });

        $cobradorIds = $resolvedReceipts
            ->pluck('resolved_cobrador_user_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->unique()
            ->all();

        if ($filterCobradorId !== '' && !in_array($filterCobradorId, $cobradorIds, true)) {
            $cobradorIds[] = $filterCobradorId;
        }

        $cobradorIdValues = $cobradorIds;
        foreach ($cobradorIds as $cobradorId) {
            if (preg_match('/^[a-f0-9]{24}$/i', $cobradorId)) {
                $cobradorIdValues[] = new ObjectId($cobradorId);
            }
        }

        $usersById = User::query()
            ->where('owner_id', $ownerId)
            ->whereIn('_id', $cobradorIdValues)
            ->get()
            ->keyBy(fn ($user) => (string) ($user->id ?? $user->getKey() ?? ''));

        $loans = Emprestimo::query()
            ->where('owner_id', $ownerId)
            ->when($cobradorIdValues !== [], fn ($query) => $query->whereIn('cobrador_user_id', $cobradorIds))
            ->get()
            ->filter(function ($loan) use ($from, $to) {
                $createdAt = $loan->created_at;
                if (!$createdAt) {
                    return false;
                }
                $date = $createdAt->format('Y-m-d');
                return $date >= $from && $date <= $to;
            });

        $rows = collect($cobradorIds)
            ->map(function (string $cobradorId) use ($resolvedReceipts, $usersById, $loans, $service) {
                $cobradorReceipts = $resolvedReceipts
                    ->filter(fn ($item) => (string) ($item['resolved_cobrador_user_id'] ?? '') === $cobradorId)
                    ->values();
                $cobradorLoans = $loans->filter(fn ($item) => (string) ($item['cobrador_user_id'] ?? '') === $cobradorId);
                $operator = $usersById->get($cobradorId);

                $fallbackName = trim((string) ($cobradorReceipts->first()['resolved_cobrador'] ?? ''));
                if ($fallbackName === '') {
                    $fallbackName = 'Cobrador não identificado';
                }

                $totalCents = (int) $cobradorReceipts->sum(function ($receipt) use ($service) {
                    $cents = (int) ($receipt['valor_recebido_cents'] ?? 0);
                    return $cents > 0 ? $cents : $service->parseMoneyToCents((string) ($receipt['valor_recebido'] ?? ''));
                });

                return [
                    'user_id' => $cobradorId,
                    'nome' => (string) ($operator['name'] ?? $fallbackName),
                    'email' => (string) ($operator['email'] ?? ''),
                    'telefone' => (string) ($operator['phone'] ?? ''),
                    'total_recebido_cents' => $totalCents,
                    'total_recebido' => $service->formatMoney($totalCents),
                    'recebimentos' => $cobradorReceipts->count(),
                    'emprestimos_criados' => $cobradorLoans->count(),
                ];
            })
            ->filter(fn (array $row) => $row['nome'] !== '' || $row['recebimentos'] > 0 || $row['emprestimos_criados'] > 0)
            ->sortByDesc('total_recebido_cents')
            ->values();

        $activities = $resolvedReceipts
            ->take(25)
            ->map(function ($receipt) use ($usersById, $service) {
                $cobradorId = (string) ($receipt['resolved_cobrador_user_id'] ?? '');
                $operator = $cobradorId !== '' ? $usersById->get($cobradorId) : null;
                $cents = (int) ($receipt['valor_recebido_cents'] ?? 0);
                if ($cents <= 0) {
                    $cents = $service->parseMoneyToCents((string) ($receipt['valor_recebido'] ?? ''));
                }
                $fallbackName = trim((string) ($receipt['resolved_cobrador'] ?? ''));
                if ($fallbackName === '') {
                    $fallbackName = 'Cobrador não identificado';
                }

                return [
                    'operador' => (string) ($operator['name'] ?? $fallbackName),
                    'data' => (string) ($receipt['recebido_em'] ?? ''),
                    'tipo' => (string) ($receipt['tipo_baixa'] ?? (($receipt['somente_juros'] ?? false) ? 'juros' : 'total')),
                    'valor' => $service->formatMoney($cents),
                ];
            });

        $totalCollectedCents = (int) $rows->sum('total_recebido_cents');

        return view('admin.relatorios', [
            'isAdmin' => $isAdmin,
            'rows' => $rows,
            'activities' => $activities,
            'cobradores' => $cobradores,
            'filters' => [
                'de' => $from,
                'ate' => $to,
                'cobrador' => $filterCobradorId,
            ],
            'summary' => [
                'operadores' => $rows->count(),
                'total_recebido' => $service->formatMoney($totalCollectedCents),
                'recebimentos' => $receipts->count(),
                'emprestimos' => (int) $rows->sum('emprestimos_criados'),
            ],
        ]);
    }
}
