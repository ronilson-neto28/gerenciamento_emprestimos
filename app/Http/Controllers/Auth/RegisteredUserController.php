<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        abort_if(User::query()->exists(), 404);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 404);

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
            'created_by' => null,
            'two_factor_channel' => 'email',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }
}
