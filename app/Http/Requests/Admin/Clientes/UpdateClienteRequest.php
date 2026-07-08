<?php

namespace App\Http\Requests\Admin\Clientes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:150'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'cpf' => ['sometimes', 'nullable', 'string', 'max:30'],
            'endereco' => ['sometimes', 'nullable', 'string', 'max:200'],
            'cidade' => ['sometimes', 'nullable', 'string', 'max:100'],
            'chave_pix' => ['sometimes', 'nullable', 'string', 'max:120'],
            'banco' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'string', 'in:ativo,inativo,pendente'],
        ];
    }
}

