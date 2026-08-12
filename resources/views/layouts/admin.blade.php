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
@php($routeName = request()->route()?->getName())
@php($pageClass = $routeName ? 'page-' . str_replace('.', '-', $routeName) : '')
<body class="{{ $pageClass }}">
    @php($authUser = auth()->user())
    @php($userInitials = (static function ($u) {
        $n = preg_split('/\s+/', trim((string) ($u->name ?? '')));
        if (!$n) {
            return 'KA';
        }
        $a = mb_strtoupper(mb_substr($n[0], 0, 1) ?: 'K');
        $b = mb_strtoupper(mb_substr($n[count($n) - 1] ?? '', 0, 1) ?: 'A');
        return $a . $b;
    })($authUser))
    <div class="admin-shell admin-shell-clean-light">
        <aside class="sidebar sidebar-clean-light">
            <div class="brand brand-clean-light">
                <span class="brand-mark brand-mark-kennedy" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM7.5 7.6L10.13 12L7.5 16.4H9.5L11.5 12.85L11.13 12L11.5 11.15L9.5 7.6H7.5ZM13.5 7.6H15.5L17.15 12.6H17.19L18.84 7.6H20.84L17.87 16.4H15.86L12.89 7.6H13.5ZM15.23 10.54L16.41 14.15H16.45L17.63 10.54H15.23Z" fill="currentColor"/></svg>
                </span>
                Keneddy<span>Admin</span>
            </div>

            <div class="nav-section-label nav-section-label-clean">Visão Geral</div>
            @can('view-dashboard')
                <a href="{{ route('admin.dashboard') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.dashboard') ? 'active active-clean' : '' }}">
                    <span class="nav-link-indicator" aria-hidden="true"></span>
                    <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><rect x="13" y="4" width="7" height="5" rx="2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><rect x="13" y="13" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><rect x="4" y="14" width="7" height="6" rx="2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                    Dashboard
                </a>
            @endcan
            <a href="{{ route('admin.clientes') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.clientes') ? 'active active-clean' : '' }}">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.0001 18.5C17.2092 18.5 19.0001 16.7091 19.0001 14.5C19.0001 12.2909 17.2092 10.5 15.0001 10.5C12.791 10.5 11.0001 12.2909 11.0001 14.5C11.0001 16.7091 12.791 18.5 15.0001 18.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M5.00012 19.5C7.20926 19.5 9.00012 17.7091 9.00012 15.5C9.00012 13.2909 7.20926 11.5 5.00012 11.5C2.79098 11.5 1.00012 13.2909 1.00012 15.5C1.00012 17.7091 2.79098 19.5 5.00012 19.5Z" stroke="currentColor" stroke-width="1.8"/><circle cx="5" cy="7" r="2.5" stroke="currentColor" stroke-width="1.8"/><circle cx="15" cy="6.5" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg></span>
                Clientes
            </a>
            <a href="{{ route('admin.emprestimos') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.emprestimos') ? 'active active-clean' : '' }}">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.5" y="5.5" width="17" height="13.5" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M7 10H17M7 13.5H14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                Empréstimos
            </a>
            <a href="{{ route('admin.relatorios') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.relatorios') ? 'active active-clean' : '' }}">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.5 20V10M12 20V4.5M18.5 20V13.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                Relatórios
            </a>
            @can('view-financeiro')
                <a href="{{ route('admin.financeiro') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.financeiro') ? 'active active-clean' : '' }}">
                    <span class="nav-link-indicator" aria-hidden="true"></span>
                    <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.5 7.5V6.5C6.5 4.567 8.067 3 10 3H15C16.933 3 18.5 4.567 18.5 6.5V17.5C18.5 19.433 16.933 21 15 21H10C8.067 21 6.5 19.433 6.5 17.5V18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 8.5H19M3 15.5H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    Financeiro
                </a>
            @endcan

            @can('manage-cobradores')
                <div class="nav-section-label nav-section-label-clean">Administração</div>
                <a href="{{ route('admin.cobradores') }}" class="nav-link nav-link-clean {{ request()->routeIs('admin.cobradores') ? 'active active-clean' : '' }}">
                    <span class="nav-link-indicator" aria-hidden="true"></span>
                    <span class="nav-icon nav-icon-clean" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="7.5" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M5 20C5 16.134 8.13401 13 12 13C15.866 13 19 16.134 19 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    Cobradores
                </a>
            @endcan

            <div class="sidebar-footer-clean">
                @if ($authUser)
                    <div class="sidebar-user-card sidebar-user-card-clean">
                        <div class="sidebar-user-avatar sidebar-user-avatar-clean">{{ $userInitials }}</div>
                        <div class="sidebar-user-meta sidebar-user-meta-clean">
                            <div class="sidebar-user-name sidebar-user-name-clean">{{ $authUser->name }}</div>
                            <div class="sidebar-user-role sidebar-user-role-clean">{{ ucfirst($authUser->role ?? 'Super Admin') }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form sidebar-logout-form-clean" title="Sair">
                            @csrf
                            <button type="submit" class="sidebar-logout-btn sidebar-logout-btn-clean" aria-label="Sair do painel">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 16.5L20 12L15.5 7.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12H9.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M9.5 6.5H5C4.17157 6.5 3.5 7.17157 3.5 8V16C3.5 16.8284 4.17157 17.5 5 17.5H9.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </aside>

        <main class="main main-light main-clean-light">
            <header class="page-hero page-hero-clean">
                <div class="page-hero-copy page-hero-copy-clean">
                    @hasSection('heading')
                        @stack('page-status-badge')
                        <h1>@yield('heading', 'Dashboard')</h1>
                        <p>@yield('subheading', 'Base inicial do painel administrativo em Blade.')</p>
                    @else
                        <h1 class="page-title-heading">@yield('heading', 'Dashboard')</h1>
                        <p class="page-title-paragraph">@yield('subheading', 'Base inicial do painel administrativo em Blade.')</p>
                    @endif
                </div>
                <div class="page-hero-actions">
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
