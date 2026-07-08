<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use MongoDB\BSON\Regex;

class AdminAccess
{
    public static function isAdmin(?User $user): bool
    {
        return $user instanceof User && $user->isAdmin();
    }

    public static function visibleLoanQuery(?User $user): Builder
    {
        $query = Emprestimo::query();

        if (self::isAdmin($user) || !$user instanceof User) {
            return $query;
        }

        $userId = (string) ($user->id ?? $user->getKey() ?? '');
        $name = trim((string) ($user->name ?? ''));

        return $query->where(function ($q) use ($userId, $name) {
            $q->orWhere('created_by', $userId)
                ->orWhere('cobrador_user_id', $userId);

            if ($name !== '') {
                $q->orWhere('cobrador', 'regex', new Regex('^' . preg_quote($name) . '$', 'i'));
            }
        });
    }

    public static function visibleClientQuery(?User $user): Builder
    {
        $query = Cliente::query();

        if (self::isAdmin($user) || !$user instanceof User) {
            return $query;
        }

        $userId = (string) ($user->id ?? $user->getKey() ?? '');
        $clientIds = self::visibleLoanQuery($user)
            ->pluck('cliente_id')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return $query->where(function ($q) use ($userId, $clientIds) {
            $q->orWhere('created_by', $userId);

            if ($clientIds !== []) {
                $q->orWhereIn('_id', $clientIds);
            }
        });
    }

    public static function canAccessLoan(?User $user, Emprestimo $loan): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (!$user instanceof User) {
            return false;
        }

        $userId = (string) ($user->id ?? $user->getKey() ?? '');
        $assignedId = (string) ($loan['cobrador_user_id'] ?? '');
        $createdBy = (string) ($loan['created_by'] ?? '');
        $cobrador = trim((string) ($loan['cobrador'] ?? ''));
        $userName = trim((string) ($user->name ?? ''));

        if ($createdBy !== '' && $createdBy === $userId) {
            return true;
        }

        if ($assignedId !== '' && $assignedId === $userId) {
            return true;
        }

        return $cobrador !== '' && $userName !== '' && mb_strtolower($cobrador) === mb_strtolower($userName);
    }

    public static function canAccessClient(?User $user, Cliente $cliente): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (!$user instanceof User) {
            return false;
        }

        $userId = (string) ($user->id ?? $user->getKey() ?? '');
        $clientId = (string) ($cliente->id ?? $cliente->getKey() ?? '');
        $createdBy = (string) ($cliente['created_by'] ?? '');

        if ($createdBy !== '' && $createdBy === $userId) {
            return true;
        }

        return self::visibleLoanQuery($user)
            ->where('cliente_id', $clientId)
            ->exists();
    }

    public static function findAssignedCobrador(string $value): ?User
    {
        $term = trim($value);
        if ($term === '') {
            return null;
        }

        return User::query()
            ->where('role', 'cobrador')
            ->where(function ($query) use ($term) {
                $query->orWhere('email', $term)
                    ->orWhere('phone', $term)
                    ->orWhere('name', 'regex', new Regex('^' . preg_quote($term) . '$', 'i'));
            })
            ->first();
    }

    public static function cobradoresCreatedBy(?User $user): Collection
    {
        $query = User::query()->where('role', 'cobrador')->orderBy('name');

        if (!self::isAdmin($user) && $user instanceof User) {
            $query->where('created_by', (string) ($user->id ?? $user->getKey() ?? ''));
        }

        return $query->get();
    }
}
