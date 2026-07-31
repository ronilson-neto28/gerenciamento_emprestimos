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
@php($pageClass = request()->route() && request()->route()->getName() ? 'page-' . str_replace('.', '-', request()->route()->getName()) : '')
<body class="auth-page-body {{ $pageClass }}">
    <main class="auth-page">
        <section class="auth-shell">
            <aside class="auth-showcase">
                <div class="auth-showcase-brand">
                    <span class="auth-showcase-brand-mark"></span>
                    <span>Keneddy</span>
                </div>

                <div class="auth-showcase-copy">
                    <p class="auth-showcase-eyebrow">@yield('visual_eyebrow', 'Painel inteligente')</p>
                    <h2 class="auth-showcase-title">@yield('visual_heading', 'Gerencie sua operação com mais controle')</h2>
                    <p class="auth-showcase-text">@yield('visual_text', 'Acompanhe clientes, cobranças e relatórios em um ambiente simples, moderno e organizado.')</p>
                </div>

                <div class="auth-illustration" aria-hidden="true">
                    <div class="auth-illustration-folder"></div>
                    <div class="auth-illustration-card auth-illustration-card-back"></div>
                    <div class="auth-illustration-card auth-illustration-card-front"></div>
                    <div class="auth-illustration-lens"></div>
                </div>
            </aside>

            <section class="auth-panel">
                <div class="auth-card">
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
                </div>
            </section>
        </section>
    </main>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/admin/admin.js'])
    @else
        <script src="{{ route('assets.admin.admin_js', [], false) }}" defer></script>
    @endif
</body>
</html>
