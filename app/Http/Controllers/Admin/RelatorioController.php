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
        $filterUserId = $isAdmin
            ? trim((string) $request->query('cobrador', ''))
            : (string) ($authUser?->id ?? $authUser?->getKey() ?? '');

        $cobradores = User::query()
            ->where('role', 'cobrador')
            ->where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        $receiptsQuery = Recebimento::query()
            ->where('recebido_em', '>=', $from)
            ->where('recebido_em', '<=', $to)
            ->orderBy('recebido_em', 'desc');

        if ($filterUserId !== '') {
            $receiptsQuery->where('user_id', $filterUserId);
        }

        $receipts = $receiptsQuery->get();
        $userIds = $receipts->pluck('user_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->unique()
            ->all();

        if ($filterUserId !== '' && !in_array($filterUserId, $userIds, true)) {
            $userIds[] = $filterUserId;
        }

        $usersById = User::query()
            ->where('owner_id', $ownerId)
            ->whereIn('_id', $userIds)
            ->get()
            ->keyBy(fn ($user) => (string) ($user->id ?? $user->getKey() ?? ''));

        $loans = Emprestimo::query()
            ->when($userIds !== [], fn ($query) => $query->whereIn('created_by', $userIds))
            ->get()
            ->filter(function ($loan) use ($from, $to) {
                $createdAt = $loan->created_at;

                if (!$createdAt) {
                    return false;
                }

                $date = $createdAt->format('Y-m-d');

                return $date >= $from && $date <= $to;
            });

        $rows = collect($userIds)
            ->map(function (string $userId) use ($receipts, $usersById, $loans, $service) {
                $userReceipts = $receipts->filter(fn ($item) => (string) ($item['user_id'] ?? '') === $userId)->values();
                $userLoans = $loans->filter(fn ($item) => (string) ($item['created_by'] ?? '') === $userId);
                $operator = $usersById->get($userId);

                $totalCents = (int) $userReceipts->sum(function ($receipt) use ($service) {
                    $cents = (int) ($receipt['valor_recebido_cents'] ?? 0);

                    return $cents > 0 ? $cents : $service->parseMoneyToCents((string) ($receipt['valor_recebido'] ?? ''));
                });

                return [
                    'user_id' => $userId,
                    'nome' => (string) ($operator['name'] ?? 'Operador sem nome'),
                    'email' => (string) ($operator['email'] ?? ''),
                    'telefone' => (string) ($operator['phone'] ?? ''),
                    'total_recebido_cents' => $totalCents,
                    'total_recebido' => $service->formatMoney($totalCents),
                    'recebimentos' => $userReceipts->count(),
                    'emprestimos_criados' => $userLoans->count(),
                ];
            })
            ->filter(fn (array $row) => $row['nome'] !== '' || $row['recebimentos'] > 0 || $row['emprestimos_criados'] > 0)
            ->sortByDesc('total_recebido_cents')
            ->values();

        $activities = $receipts
            ->take(25)
            ->map(function ($receipt) use ($usersById, $service) {
                $userId = (string) ($receipt['user_id'] ?? '');
                $operator = $usersById->get($userId);
                $cents = (int) ($receipt['valor_recebido_cents'] ?? 0);
                if ($cents <= 0) {
                    $cents = $service->parseMoneyToCents((string) ($receipt['valor_recebido'] ?? ''));
                }

                return [
                    'operador' => (string) ($operator['name'] ?? 'Usuário não identificado'),
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
                'cobrador' => $filterUserId,
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
