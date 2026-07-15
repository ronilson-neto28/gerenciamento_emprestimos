<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\RegistrationVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VerifyRegistrationCodeController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.verify-code', [
            'email' => trim((string) $request->query('email', '')),
        ]);
    }

    public function store(Request $request, RegistrationVerificationService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = mb_strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Não encontramos um cadastro com esse e-mail.']);
        }

        if (!$user->isPending()) {
            return redirect()
                ->route('login')
                ->with('status', 'Sua conta já está ativa. Faça login para continuar.');
        }

        if (!$service->verify($user, (string) $data['code'])) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Código inválido ou expirado. Solicite um novo código.']);
        }

        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }

    public function resend(Request $request, RegistrationVerificationService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ]);

        $email = mb_strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Não encontramos um cadastro com esse e-mail.']);
        }

        if (!$service->canResend($user)) {
            return redirect()
                ->route('login')
                ->with('status', 'Sua conta já está ativa. Faça login para continuar.');
        }

        try {
            $service->issue($user);
        } catch (\RuntimeException $e) {
            report($e);

            return back()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', 'Enviamos um novo código para o seu e-mail.');
    }
}
