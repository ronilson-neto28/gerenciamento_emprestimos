<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreCobradorRequest;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CobradorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-cobradores');

        $cobradores = AdminAccess::cobradoresCreatedBy($request->user());

        return view('admin.cobradores', [
            'cobradores' => $cobradores,
        ]);
    }

    public function store(StoreCobradorRequest $request): RedirectResponse
    {
        Gate::authorize('manage-cobradores');

        $data = $request->validated();
        $email = mb_strtolower(trim((string) $data['email']));

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Já existe um usuário cadastrado com este e-mail.']);
        }

        $creatorId = (string) ($request->user()?->id ?? $request->user()?->getKey() ?? '');

        User::create([
            'name' => trim((string) $data['name']),
            'email' => $email,
            'phone' => trim((string) ($data['phone'] ?? '')),
            'password' => Hash::make((string) $data['password']),
            'role' => 'cobrador',
            'status' => 'ativo',
            'owner_id' => $request->user()?->ownerId(),
            'created_by' => $creatorId,
            'email_verified_at' => now(),
            'two_factor_channel' => 'email',
        ]);

        return redirect()
            ->route('admin.cobradores')
            ->with('status', 'Cobrador cadastrado com sucesso.');
    }
}
