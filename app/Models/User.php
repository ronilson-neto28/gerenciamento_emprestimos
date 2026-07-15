<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'owner_id',
        'created_by',
        'email_verified_at',
        'verification_code',
        'verification_code_expires_at',
        'remember_token',
        'two_factor_code',
        'two_factor_channel',
        'two_factor_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verification_code_expires_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return (string) ($this->role ?? '') === 'admin';
    }

    public function isCobrador(): bool
    {
        return (string) ($this->role ?? '') === 'cobrador';
    }

    public function ownerId(): string
    {
        return trim((string) ($this->owner_id ?: $this->id ?: $this->getKey() ?: ''));
    }

    public function isActive(): bool
    {
        $status = trim((string) ($this->status ?? ''));

        return $status === '' || $status === 'ativo';
    }

    public function isPending(): bool
    {
        return trim((string) ($this->status ?? '')) === 'pendente';
    }
}
