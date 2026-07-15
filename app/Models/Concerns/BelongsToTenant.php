<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant_owner', function (Builder $builder) {
            $ownerId = static::resolveAuthenticatedOwnerId();

            if ($ownerId !== '') {
                $builder->where('owner_id', $ownerId);
            }
        });

        static::creating(function ($model) {
            if (!empty($model->owner_id)) {
                return;
            }

            $ownerId = static::resolveAuthenticatedOwnerId();

            if ($ownerId !== '') {
                $model->owner_id = $ownerId;
            }
        });
    }

    public function scopeForOwner(Builder $query, ?string $ownerId): Builder
    {
        $ownerId = trim((string) $ownerId);

        if ($ownerId === '') {
            return $query;
        }

        return $query->where('owner_id', $ownerId);
    }

    protected static function resolveAuthenticatedOwnerId(): string
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return '';
        }

        return trim((string) ($user->owner_id ?: $user->id ?: $user->getKey() ?: ''));
    }
}
