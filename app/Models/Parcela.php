<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Parcela extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'parcelas';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'emprestimo_id',
        'numero',
        'vencimento',
        'amortizacao',
        'amortizacao_cents',
        'juros',
        'juros_cents',
        'multa',
        'multa_cents',
        'total',
        'total_cents',
        'valor_recebido',
        'valor_recebido_cents',
        'saldo_restante',
        'saldo_restante_cents',
        'principal_pago_cents',
        'status',
        'recebida_em',
    ];
}
