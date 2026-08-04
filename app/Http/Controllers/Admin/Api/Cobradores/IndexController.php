<?php

namespace App\Http\Controllers\Admin\Api\Cobradores;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\Regex;

class IndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('create-emprestimos');

        $ownerId = AdminAccess::resolveOwnerId($request->user());
        if ($ownerId === '') {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('role', 'cobrador')
            ->where('status', 'ativo')
            ->where('owner_id', $ownerId)
            ->orderBy('name');

        if ($term !== '') {
            $query->where('name', 'regex', new Regex(preg_quote($term), 'i'));
        }

        $cobradores = $query
            ->limit(12)
            ->get()
            ->map(fn (User $user) => [
                'id' => (string) ($user->id ?? $user->getKey() ?? ''),
                'name' => trim((string) ($user->name ?? '')),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values();

        return response()->json([
            'data' => $cobradores,
        ]);
    }
}

