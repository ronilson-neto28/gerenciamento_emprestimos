@extends('layouts.guest')

@section('title', 'Primeiro acesso')
@section('heading', 'Criar administrador inicial')
@section('subheading', 'Cadastre o primeiro usuário admin para habilitar o acesso ao painel.')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="name">Nome</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>

        <div class="field">
            <label for="phone">Telefone</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary auth-submit">Criar admin</button>
    </form>
@endsection
