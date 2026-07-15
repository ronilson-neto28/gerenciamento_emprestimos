@extends('layouts.guest')

@section('title', 'Criar conta')
@section('heading', 'Criar conta')
@section('subheading', 'Cadastre sua empresa e ative o acesso com o código enviado por e-mail.')
@section('visual_heading', 'Abra o seu próprio ambiente de empréstimos')
@section('visual_text', 'Crie a conta administradora da sua empresa, valide o e-mail e comece com um tenant isolado para seus clientes, cobradores e empréstimos.')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="name">Nome completo</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Digite o nome completo" required autofocus autocomplete="name">
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@teste.com" required autocomplete="username">
        </div>

        <div class="field">
            <label for="phone">Telefone</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="(00) 00000-0000" autocomplete="tel">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" placeholder="Crie uma senha" required autocomplete="new-password">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repita a senha" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary auth-submit">Criar conta</button>
    </form>

    <div class="auth-links">
        <span>Já tem uma conta?</span>
        <a href="{{ route('login') }}">Entrar</a>
    </div>
@endsection
