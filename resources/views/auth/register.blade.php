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
            <div class="input-with-action">
                <input id="password" type="password" name="password" placeholder="Crie uma senha" required autocomplete="new-password">
                <button type="button" class="password-toggle js-password-toggle" data-target="password" aria-label="Mostrar senha" aria-pressed="false">
                    <svg class="password-toggle-icon password-toggle-icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 5c5.5 0 9.6 4.2 10.9 6.1.1.2.1.5 0 .7C21.6 13.8 17.5 18 12 18S2.4 13.8 1.1 11.8a.7.7 0 0 1 0-.7C2.4 9.2 6.5 5 12 5Zm0 2C7.6 7 4.2 10.2 3.1 11.5 4.2 12.8 7.6 16 12 16s7.8-3.2 8.9-4.5C19.8 10.2 16.4 7 12 7Zm0 2.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6Z"/>
                    </svg>
                    <svg class="password-toggle-icon password-toggle-icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4.7 3.3 20.7 19.3l-1.4 1.4-2.2-2.2A11.8 11.8 0 0 1 12 20C6.5 20 2.4 15.8 1.1 13.8a.7.7 0 0 1 0-.7 15.9 15.9 0 0 1 4-4.5L3.3 4.7l1.4-1.4ZM6.5 9.9a13 13 0 0 0-3.4 3.6C4.2 14.8 7.6 18 12 18a9.7 9.7 0 0 0 3.6-.7l-1.7-1.7c-.6.4-1.2.6-1.9.6a2.8 2.8 0 0 1-2.8-2.8c0-.7.2-1.3.6-1.9L6.5 9.9ZM12 7c-.6 0-1.2.1-1.7.3L8.6 5.6c1.1-.4 2.2-.6 3.4-.6 5.5 0 9.6 4.2 10.9 6.1.1.2.1.5 0 .7a16 16 0 0 1-3.6 4l-1.4-1.4a13 13 0 0 0 2.9-2.9C19.8 10.2 16.4 7 12 7Z"/>
                    </svg>
                </button>
            </div>
            <div class="password-rules" role="status" aria-live="polite">
                <div class="password-rules-title">Requisitos da senha</div>
                <ul class="password-rules-list" data-password-rules>
                    <li class="password-rule" data-rule="length">Mínimo de 8 caracteres</li>
                    <li class="password-rule" data-rule="upper">Pelo menos 1 letra maiúscula</li>
                    <li class="password-rule" data-rule="lower">Pelo menos 1 letra minúscula</li>
                    <li class="password-rule" data-rule="special">Pelo menos 1 caractere especial (ex: @, #, $, !)</li>
                </ul>
            </div>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <div class="input-with-action">
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repita a senha" required autocomplete="new-password">
                <button type="button" class="password-toggle js-password-toggle" data-target="password_confirmation" aria-label="Mostrar senha" aria-pressed="false">
                    <svg class="password-toggle-icon password-toggle-icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 5c5.5 0 9.6 4.2 10.9 6.1.1.2.1.5 0 .7C21.6 13.8 17.5 18 12 18S2.4 13.8 1.1 11.8a.7.7 0 0 1 0-.7C2.4 9.2 6.5 5 12 5Zm0 2C7.6 7 4.2 10.2 3.1 11.5 4.2 12.8 7.6 16 12 16s7.8-3.2 8.9-4.5C19.8 10.2 16.4 7 12 7Zm0 2.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6Z"/>
                    </svg>
                    <svg class="password-toggle-icon password-toggle-icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4.7 3.3 20.7 19.3l-1.4 1.4-2.2-2.2A11.8 11.8 0 0 1 12 20C6.5 20 2.4 15.8 1.1 13.8a.7.7 0 0 1 0-.7 15.9 15.9 0 0 1 4-4.5L3.3 4.7l1.4-1.4ZM6.5 9.9a13 13 0 0 0-3.4 3.6C4.2 14.8 7.6 18 12 18a9.7 9.7 0 0 0 3.6-.7l-1.7-1.7c-.6.4-1.2.6-1.9.6a2.8 2.8 0 0 1-2.8-2.8c0-.7.2-1.3.6-1.9L6.5 9.9ZM12 7c-.6 0-1.2.1-1.7.3L8.6 5.6c1.1-.4 2.2-.6 3.4-.6 5.5 0 9.6 4.2 10.9 6.1.1.2.1.5 0 .7a16 16 0 0 1-3.6 4l-1.4-1.4a13 13 0 0 0 2.9-2.9C19.8 10.2 16.4 7 12 7Z"/>
                    </svg>
                </button>
            </div>
            <div class="field-help field-help-error" id="password_mismatch_help" hidden>As senhas não coincidem.</div>
        </div>

        <button type="submit" class="btn-primary auth-submit">Criar conta</button>
    </form>

    <div class="auth-links">
        <span>Já tem uma conta?</span>
        <a href="{{ route('login') }}">Entrar</a>
    </div>
@endsection
