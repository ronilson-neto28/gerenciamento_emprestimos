<?php

namespace App\Http\Requests\Admin\Financeiro;

use Illuminate\Foundation\Http\FormRequest;

class StoreLancamentoFinanceiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:entrada,saida'],
            'value' => ['required'],
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:300'],
            'category' => ['nullable', 'string', 'max:60'],
        ];
    }
}

