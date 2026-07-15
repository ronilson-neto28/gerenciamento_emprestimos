<?php

namespace App\Http\Controllers\Admin\Api\Clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clientes\StoreClienteRequest;
use App\Models\Cliente;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use App\Support\AdminAccess;

class StoreController extends Controller
{
    public function __invoke(StoreClienteRequest $request): JsonResponse
    {
        Gate::authorize('create-clientes');

        $data = $this->sanitize($request->validated());

        if (!isset($data['status']) || $data['status'] === null || $data['status'] === '') {
            $data['status'] = 'ativo';
        }

        $data['owner_id'] = AdminAccess::resolveOwnerId($request->user());
        $data['created_by'] = (string) ($request->user()?->id ?? $request->user()?->getKey() ?? '');

        $cliente = Cliente::create($data);

        return response()->json([
            'data' => $this->toPayload($cliente),
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

    private function toPayload(Cliente $cliente): array
    {
        return [
            'id' => (string) $cliente->getKey(),
            'nome' => (string) ($cliente->nome ?? ''),
            'telefone' => (string) ($cliente->telefone ?? ''),
            'cpf' => (string) ($cliente->cpf ?? ''),
            'endereco' => (string) ($cliente->endereco ?? ''),
            'cidade' => (string) ($cliente->cidade ?? ''),
            'chave_pix' => (string) ($cliente->chave_pix ?? ''),
            'banco' => (string) ($cliente->banco ?? ''),
            'status' => (string) ($cliente->status ?? ''),
        ];
    }
}
