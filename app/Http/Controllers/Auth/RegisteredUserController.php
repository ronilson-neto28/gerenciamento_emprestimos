<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\RegistrationVerificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, RegistrationVerificationService $verification): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        abort_if(User::query()->where('email', $email)->exists(), 422, 'Já existe um usuário com esse e-mail.');

        $user = User::create([
            'name' => trim((string) $request->input('name')),
            'email' => $email,
            'phone' => trim((string) $request->input('phone')),
            'password' => Hash::make((string) $request->input('password')),
            'role' => 'admin',
            'status' => 'pendente',
            'owner_id' => null,
            'created_by' => null,
            'email_verified_at' => null,
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'two_factor_channel' => 'email',
        ]);

        $user->forceFill([
            'owner_id' => (string) ($user->id ?? $user->getKey() ?? ''),
        ])->save();

        try {
            $verification->issue($user);
        } catch (\RuntimeException $e) {
            report($e);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => $e->getMessage()]);
        }

        event(new Registered($user));

        return redirect()
            ->route('register.verify', ['email' => $email])
            ->with('status', 'Enviamos um código de verificação para o seu e-mail.');
    }
}
