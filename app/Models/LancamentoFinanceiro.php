<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class LancamentoFinanceiro extends Model
{
    use BelongsToTenant, HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'lancamentos_financeiro';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'type',
        'value',
        'date',
        'description',
        'category',
        'owner_id',
    ];
}
