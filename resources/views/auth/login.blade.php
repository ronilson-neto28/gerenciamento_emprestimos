@extends('layouts.guest')

@section('title', 'Entrar')
@section('heading', 'Entrar no sistema')
@section('subheading', 'Use seu e-mail e senha para acessar o painel.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="checkbox-inline">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Lembrar acesso</span>
        </label>

        <button type="submit" class="btn-primary auth-submit">Entrar</button>
    </form>

    <div class="auth-links">
        <a href="{{ route('password.request') }}">Esqueci minha senha</a>
        @if (!\App\Models\User::query()->exists())
            <span>•</span>
            <a href="{{ route('register') }}">Primeiro acesso</a>
        @endif
    </div>
@endsection
