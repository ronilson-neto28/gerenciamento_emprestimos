@extends('layouts.guest')

@section('title', 'Entrar')
@section('heading', 'Login')
@section('subheading', 'Use seu e-mail e senha para acessar o painel.')
@section('visual_heading', 'Gerencie seus empréstimos do jeito certo')
@section('visual_text', 'Centralize acessos, acompanhe a carteira e entre rapidamente no painel com uma experiência mais clara e moderna.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="admin@teste.com" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" placeholder="Digite sua senha" required autocomplete="current-password">
        </div>

        <div class="auth-meta-row">
            <label class="checkbox-inline">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Lembrar acesso</span>
            </label>

            <a class="auth-inline-link" href="{{ route('password.request') }}">Esqueci minha senha</a>
        </div>

        <button type="submit" class="btn-primary auth-submit">Entrar</button>
    </form>

    <div class="auth-links">
        <span>Ainda não tem conta?</span>
        <a href="{{ route('register') }}">Criar conta</a>
    </div>
@endsection
