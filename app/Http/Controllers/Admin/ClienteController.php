<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cliente;
use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MongoDB\BSON\Regex;

class ClienteController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('busca', ''));
        $search = mb_substr($search, 0, 120);

        $allowedStatus = ['todos', 'ativo', 'inativo', 'pendente'];
        $status = (string) $request->query('status', 'todos');
        $status = in_array($status, $allowedStatus, true) ? $status : 'todos';

        $baseQuery = AdminAccess::visibleClientQuery($request->user());
        $query = clone $baseQuery;

        if ($search !== '') {
            $regex = new Regex(preg_quote($search), 'i');

            $query->where(function ($q) use ($regex) {
                $q->orWhere('nome', 'regex', $regex)
                    ->orWhere('telefone', 'regex', $regex)
                    ->orWhere('cpf', 'regex', $regex)
                    ->orWhere('endereco', 'regex', $regex)
                    ->orWhere('cidade', 'regex', $regex)
                    ->orWhere('chave_pix', 'regex', $regex)
                    ->orWhere('banco', 'regex', $regex);
            });
        }

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $filteredClients = $query->orderBy('nome')->get();
        $summaryQuery = AdminAccess::visibleClientQuery($request->user());

        return view('admin.cliente', [
            'clients' => $filteredClients,
            'filters' => [
                'busca' => $search,
                'status' => $status,
            ],
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'filtrados' => $filteredClients->count(),
                'ativos' => (clone $summaryQuery)->where('status', 'ativo')->count(),
            ],
        ]);
    }
}
