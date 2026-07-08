<?php

namespace App\Http\Requests\Admin\Emprestimos;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveParcelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receive_date' => ['required', 'date_format:Y-m-d'],
            'receive_amount' => ['required', 'string', 'max:40'],
            'only_interest' => ['nullable', 'boolean'],
        ];
    }
}
