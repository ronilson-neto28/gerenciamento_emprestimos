<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel Admin')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/admin.css'])
    @else
        <link rel="stylesheet" href="{{ route('assets.admin.css', [], false) }}">
    @endif
    @stack('styles')
</head>
@php($pageClass = request()->route() && request()->route()->getName() ? 'page-' . str_replace('.', '-', request()->route()->getName()) : '')
<body class="{{ $pageClass }}">
    @php($authUser = auth()->user())
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">Keneddy<span>Admin</span></div>

            <div class="nav-label">Principal</div>
            @can('view-dashboard')
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            @endcan
            <a href="{{ route('admin.clientes') }}" class="nav-link {{ request()->routeIs('admin.clientes') ? 'active' : '' }}">Clientes</a>
            <a href="{{ route('admin.emprestimos') }}" class="nav-link {{ request()->routeIs('admin.emprestimos') ? 'active' : '' }}">Emprestimo</a>
            <a href="{{ route('admin.relatorios') }}" class="nav-link {{ request()->routeIs('admin.relatorios') ? 'active' : '' }}">Relatorios</a>
            @can('view-financeiro')
                <a href="{{ route('admin.financeiro') }}" class="nav-link {{ request()->routeIs('admin.financeiro') ? 'active' : '' }}">Financeiro</a>
            @endcan

            @can('manage-cobradores')
                <div class="nav-label">Admin</div>
                <a href="{{ route('admin.cobradores') }}" class="nav-link {{ request()->routeIs('admin.cobradores') ? 'active' : '' }}">Cobradores</a>
            @endcan

            @if ($authUser)
                <div class="sidebar-user-card">
                    <div class="sidebar-user-name">{{ $authUser->name }}</div>
                    <div class="sidebar-user-role">{{ ucfirst((string) ($authUser->role ?? 'usuario')) }}</div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary sidebar-logout">Sair</button>
                    </form>
                </div>
            @endif
        </aside>

        <main class="main">
            <header class="topbar">
                @unless (request()->routeIs('admin.dashboard') || request()->routeIs('admin.clientes') || request()->routeIs('admin.emprestimos'))
                    <div class="page-title">
                        <h1>@yield('heading', 'Dashboard')</h1>
                        <p>@yield('subheading', 'Base inicial do painel administrativo em Blade.')</p>
                    </div>
                @endunless

                <div class="topbar-actions">
                    @if ($authUser)
                        <div class="topbar-user-chip">
                            <strong>{{ $authUser->name }}</strong>
                            <span>{{ ucfirst((string) ($authUser->role ?? 'usuario')) }}</span>
                        </div>
                    @endif
                    @stack('topbar-actions')
                </div>
            </header>

            @if (session('status'))
                <div class="flash-message flash-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash-message flash-error">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/admin/admin.js'])
    @else
        <script src="{{ route('assets.admin.admin_js', [], false) }}" defer></script>
    @endif
    @stack('scripts')
</body>
</html>
