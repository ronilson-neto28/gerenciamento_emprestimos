@extends('layouts.guest')

@section('title', 'Redefinir senha')
@section('heading', 'Criar nova senha')
@section('subheading', 'Defina sua nova senha para voltar a acessar o painel.')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="auth-form">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Nova senha</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary auth-submit">Redefinir senha</button>
    </form>
@endsection
