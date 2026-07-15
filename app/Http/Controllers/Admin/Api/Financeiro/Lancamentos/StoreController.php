<?php

namespace App\Http\Controllers\Admin\Api\Financeiro\Lancamentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Financeiro\StoreLancamentoFinanceiroRequest;
use App\Models\LancamentoFinanceiro;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    public function __invoke(StoreLancamentoFinanceiroRequest $request): JsonResponse
    {
        Gate::authorize('manage-financeiro');

        $data = $this->sanitize($request->validated());

        $data['value'] = $this->parseMoneyToFloat($data['value'] ?? null);

        $lancamento = LancamentoFinanceiro::create([
            'type' => $data['type'],
            'value' => $data['value'],
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'owner_id' => AdminAccess::resolveOwnerId($request->user()),
        ]);

        return response()->json([
            'data' => [
                'id' => (string) $lancamento->getKey(),
                'type' => (string) ($lancamento->type ?? ''),
                'value' => (float) ($lancamento->value ?? 0),
                'date' => (string) ($lancamento->date ?? ''),
                'description' => (string) ($lancamento->description ?? ''),
                'category' => (string) ($lancamento->category ?? ''),
            ],
        ], 201);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    private function parseMoneyToFloat(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = (string) $value;

        $normalized = preg_replace('/[^\d,.-]/', '', $raw) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        $parsed = (float) $normalized;

        return $parsed;
    }
}
