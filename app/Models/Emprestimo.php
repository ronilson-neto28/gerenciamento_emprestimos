<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Emprestimo extends Model
{
    use BelongsToTenant, HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'emprestimos';
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cliente_id',
        'owner_id',
        'created_by',
        'cliente',
        'valor',
        'valor_cents',
        'parcelas',
        'numero_parcelas',
        'vencimento',
        'tipo',
        'status',
        'data_emprestimo',
        'taxa_juros',
        'taxa_juros_percent',
        'taxa_juros_rate',
        'tipo_juros',
        'intervalo',
        'tipo_multa',
        'valor_multa',
        'cobranca_multa',
        'cobrador',
        'cobrador_user_id',
        'excecoes_dia',
        'observacoes',
        'parcela_cents',
        'total_cents',
    ];
}
