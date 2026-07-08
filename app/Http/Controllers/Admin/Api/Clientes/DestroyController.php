<?php

namespace App\Http\Controllers\Admin\Api\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class DestroyController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        Gate::authorize('delete-clientes');

        $cliente = $this->findCliente($id);
        $cliente->delete();

        return response()->json(['ok' => true]);
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
