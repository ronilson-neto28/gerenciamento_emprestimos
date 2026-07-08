<?php

namespace App\Http\Requests\Admin\Emprestimos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmprestimoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente' => ['required', 'string', 'max:160'],
            'data_emprestimo' => ['required', 'date_format:Y-m-d'],
            'valor_emprestimo' => ['required', 'string', 'max:40'],
            'taxa_juros' => ['nullable', 'string', 'max:20'],
            'tipo_juros' => ['required', 'in:simples,composto,fixo'],
            'numero_parcelas' => ['required', 'integer', 'min:1', 'max:360'],
            'intervalo' => ['required', 'in:diario,semanal,quinzenal,mensal'],
            'tipo_multa' => ['required', 'in:percentual,fixa'],
            'valor_multa' => ['nullable', 'string', 'max:30'],
            'cobranca_multa' => ['required', 'in:automatica,manual,desativada'],
            'cobrador' => ['nullable', 'string', 'max:160'],
            'excecoes_dia' => ['nullable', 'array'],
            'excecoes_dia.*' => ['in:anular_sabados,anular_domingos,anular_feriados'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
