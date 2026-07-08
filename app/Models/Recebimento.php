<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Recebimento extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'recebimentos';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'parcela_id',
        'emprestimo_id',
        'cliente_id',
        'user_id',
        'valor_recebido',
        'valor_recebido_cents',
        'recebido_em',
        'tipo_baixa',
        'somente_juros',
        'observacao',
    ];
}
