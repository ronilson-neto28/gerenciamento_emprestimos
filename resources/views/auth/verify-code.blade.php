@extends('layouts.guest')

@section('title', 'Verificar conta')
@section('heading', 'Verificar conta')
@section('subheading', 'Digite o código de 6 dígitos enviado para o seu e-mail para ativar o acesso.')
@section('visual_heading', 'Ative sua conta com segurança')
@section('visual_text', 'Depois da validação do código, sua conta administradora fica ativa e você já pode entrar no painel do seu negócio.')

@section('content')
    <form method="POST" action="{{ route('register.verify.store') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" placeholder="usuario@teste.com" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="code">Código de verificação</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}" inputmode="numeric" maxlength="6" placeholder="000000" required autocomplete="one-time-code">
        </div>

        <button type="submit" class="btn-primary auth-submit">Validar código</button>
    </form>

    <form method="POST" action="{{ route('register.verify.resend') }}" class="auth-form" style="margin-top: 12px;">
        @csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <button type="submit" class="btn-secondary auth-submit">Reenviar código</button>
    </form>

    <div class="auth-links">
        <a href="{{ route('login') }}">Voltar para o login</a>
    </div>
@endsection
