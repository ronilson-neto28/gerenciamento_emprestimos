<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Cliente extends Model
{
    use BelongsToTenant, HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'clientes';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nome',
        'telefone',
        'cpf',
        'endereco',
        'cidade',
        'chave_pix',
        'banco',
        'status',
        'owner_id',
        'created_by',
    ];
}
