<?php

namespace App\Http\Requests\Admin\Clientes;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'cpf' => ['nullable', 'string', 'max:30'],
            'endereco' => ['nullable', 'string', 'max:200'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'chave_pix' => ['nullable', 'string', 'max:120'],
            'banco' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:ativo,inativo,pendente'],
        ];
    }
}

