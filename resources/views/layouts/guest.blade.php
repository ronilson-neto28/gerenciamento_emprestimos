<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acesso ao Sistema')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/admin.css'])
    @else
        <link rel="stylesheet" href="{{ route('assets.admin.css', [], false) }}">
    @endif
</head>
<body class="auth-page-body">
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-brand">Keneddy<span>Admin</span></div>
            <h1 class="auth-title">@yield('heading', 'Acesse sua conta')</h1>
            <p class="auth-subtitle">@yield('subheading', 'Entre com suas credenciais para continuar.')</p>

            @if (session('status'))
                <div class="flash-message flash-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash-message flash-error">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </section>
    </main>
</body>
</html>
