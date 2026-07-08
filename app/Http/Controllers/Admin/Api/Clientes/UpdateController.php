<?php

namespace App\Http\Controllers\Admin\Api\Clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clientes\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class UpdateController extends Controller
{
    public function __invoke(UpdateClienteRequest $request, string $id): JsonResponse
    {
        $cliente = $this->findCliente($id);
        Gate::authorize('access-cliente', $cliente);
        $data = $this->sanitize($request->validated());

        $cliente->fill($data);
        $cliente->save();

        return response()->json([
            'data' => [
                'id' => (string) $cliente->getKey(),
                'nome' => (string) ($cliente->nome ?? ''),
                'telefone' => (string) ($cliente->telefone ?? ''),
                'cpf' => (string) ($cliente->cpf ?? ''),
                'endereco' => (string) ($cliente->endereco ?? ''),
                'cidade' => (string) ($cliente->cidade ?? ''),
                'chave_pix' => (string) ($cliente->chave_pix ?? ''),
                'banco' => (string) ($cliente->banco ?? ''),
                'status' => (string) ($cliente->status ?? ''),
            ],
        ]);
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

    private function findCliente(string $id): Cliente
    {
        $cliente = null;

        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $cliente = Cliente::where('_id', new ObjectId($id))->first();
        }

        return $cliente ?: Cliente::findOrFail($id);
    }
}
