@extends('layouts.guest')

@section('title', 'Recuperar senha')
@section('heading', 'Recuperar senha')
@section('subheading', 'Informe seu e-mail para receber o link de redefinição.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <button type="submit" class="btn-primary auth-submit">Enviar link</button>
    </form>

    <div class="auth-links">
        <a href="{{ route('login') }}">Voltar para o login</a>
    </div>
@endsection
