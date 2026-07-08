<?php

namespace App\Http\Controllers\Admin\Api\Emprestimos;

use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\Recebimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MongoDB\BSON\ObjectId;

class DestroyController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        Gate::authorize('delete-emprestimos');

        $loan = Emprestimo::query()->findOrFail($id);
        $loanId = (string) ($loan->id ?? $loan->getKey() ?? '');
        $loanIdValues = [$loanId];
        if (preg_match('/^[a-f0-9]{24}$/i', $loanId)) {
            $loanIdValues[] = new ObjectId($loanId);
        }

        Parcela::query()->whereIn('emprestimo_id', $loanIdValues)->delete();
        Recebimento::query()->whereIn('emprestimo_id', $loanIdValues)->delete();
        $loan->delete();

        return response()->json(['ok' => true]);
    }
}
