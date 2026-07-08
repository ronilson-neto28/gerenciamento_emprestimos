<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

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
        'created_by',
    ];
}
