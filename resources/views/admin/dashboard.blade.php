@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('heading', 'Dashboard')
@section('subheading', 'Tela inicial do painel com resumo, atalhos e acompanhamento das principais areas.')

@section('content')
    @php
        $quickLinks = [
            ['label' => 'Clientes', 'href' => route('admin.clientes')],
            ['label' => 'Empréstimos', 'href' => route('admin.emprestimos')],
            ['label' => 'Financeiro', 'href' => route('admin.financeiro')],
            ['label' => 'Relatórios', 'href' => '#'],
        ];
    @endphp

    <div class="card-header-zone">
        <div>
            <h1 class="main-title">Dashboard</h1>
            <p class="main-subtitle">Tela inicial do painel com resumo, atalhos e acompanhamento das principais areas.</p>
        </div>
        <a class="btn-primary" href="{{ route('admin.financeiro') }}">Financeiro</a>
    </div>

    <section class="grid stats-grid">
        @foreach ($stats as $stat)
            <article class="card stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-trend">{{ $stat['trend'] }} neste mes</div>
            </article>
        @endforeach
    </section>

    <div class="toolbar dashboard-toolbar" role="region" aria-label="Ações rápidas do dashboard">
        <div class="dashboard-toolbar-copy">
            <div class="dashboard-toolbar-title">Ações rápidas</div>
            <div class="dashboard-toolbar-subtitle">Acesso direto aos módulos mais utilizados.</div>
        </div>
        <div class="dashboard-toolbar-actions">
            <a class="btn-secondary" href="{{ route('admin.clientes') }}">Ver clientes</a>
            <a class="btn-secondary" href="{{ route('admin.emprestimos') }}">Ver empréstimos</a>
            <a class="btn-primary" href="{{ route('admin.financeiro') }}">Ir para financeiro</a>
        </div>
    </div>

    <section class="grid hero-grid">
        <article class="card hero-card">
            <div class="hero-main">
                <h2 class="hero-title">Bem-vindo ao painel administrativo</h2>
                <p class="hero-text">
                    Esta é a tela inicial do sistema. A partir daqui você pode acompanhar indicadores,
                    acessar áreas importantes e organizar a operação diária do projeto.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('admin.clientes') }}" class="btn-primary">Acessar módulos</a>
                    <a href="#" class="btn-secondary">Configurar painel</a>
                </div>
            </div>

            <div class="hero-quick">
                <div class="card-header">
                    <div>
                        <h2>Atalhos rápidos</h2>
                        <div class="card-subtitle">Links de entrada para as principais rotinas.</div>
                    </div>
                </div>

                <div class="quick-links">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['href'] }}" class="quick-link">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </article>

        <aside class="hero-highlight">
            @foreach ($miniStats as $stat)
                <article class="mini-stat">
                    <span>{{ $stat['label'] }}</span>
                    <strong>{{ $stat['value'] }}</strong>
                </article>
            @endforeach
        </aside>
    </section>

    <section class="grid content-grid">
        @php
            $maxMonthlyRevenue = max(1, ...array_column($monthlyRevenue, 'amount'));
        @endphp

        <article class="card chart-card">
            <div class="card-header">
                <div>
                    <h2>Dinheiro arrecadado</h2>
                    <div class="card-subtitle">Grafico de colunas com o total recebido em cada mes, de janeiro a dezembro.</div>
                </div>
                <span class="badge">12 meses</span>
            </div>

            <div class="chart-wrap">
                @foreach ($monthlyRevenue as $item)
                    @php
                        $height = max(24, (int) round(($item['amount'] / $maxMonthlyRevenue) * 220));
                    @endphp
                    <div class="chart-column">
                        <div class="chart-value">R$ {{ number_format($item['amount'], 0, ',', '.') }}</div>
                        <div class="chart-bar-area">
                            <div class="chart-bar" style="--bar-h: {{ $height }}px;"></div>
                        </div>
                        <div class="chart-label">{{ $item['month'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="card activity-card activity-card-due">
            <div class="card-header">
                <div>
                    <h2>Vencendo hoje</h2>
                    <div class="card-subtitle">Emprestimos com vencimento no dia atual: {{ $todayLabel }}.</div>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($dueTodayLoans as $loan)
                    <div class="activity-item">
                        <strong>{{ $loan['cliente'] }}</strong>
                        <div class="activity-time">{{ $loan['parcela'] }} - {{ $loan['valor'] }}</div>
                    </div>
                @empty
                    <div class="activity-item">
                        <strong>Nenhum emprestimo vence hoje</strong>
                        <div class="activity-time">Nao ha parcelas previstas para {{ $todayLabel }}.</div>
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    <section class="grid content-grid">
        @php
            $maxMonthlyInvested = max(1, ...array_column($monthlyInvested, 'amount'));
        @endphp

        <article class="card chart-card">
            <div class="card-header">
                <div>
                    <h2>Dinheiro investido</h2>
                    <div class="card-subtitle">Grafico de colunas com o total de dinheiro emprestado em cada mes, de janeiro a dezembro.</div>
                </div>
                <span class="badge">12 meses</span>
            </div>

            <div class="chart-wrap">
                @foreach ($monthlyInvested as $item)
                    @php
                        $height = max(24, (int) round(($item['amount'] / $maxMonthlyInvested) * 220));
                    @endphp
                    <div class="chart-column">
                        <div class="chart-value">R$ {{ number_format($item['amount'], 0, ',', '.') }}</div>
                        <div class="chart-bar-area">
                            <div class="chart-bar invested" style="--bar-h: {{ $height }}px;"></div>
                        </div>
                        <div class="chart-label">{{ $item['month'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="card activity-card activity-card-overdue">
            <div class="card-header">
                <div>
                    <h2>Parcelas vencidas</h2>
                    <div class="card-subtitle">Parcelas que ja passaram da data de vencimento e precisam de acompanhamento.</div>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($overdueInstallments as $installment)
                    <div class="activity-item">
                        <strong>{{ $installment['cliente'] }}</strong>
                        <div class="activity-time">{{ $installment['parcela'] }} - {{ $installment['valor'] }}</div>
                        <div class="activity-time">Venceu em {{ $installment['vencimento'] }}</div>
                    </div>
                @empty
                    <div class="activity-item">
                        <strong>Nenhuma parcela vencida</strong>
                        <div class="activity-time">No momento nao ha parcelas em atraso.</div>
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/dashboard.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.dashboard_css', [], false) }}">
        @endif
    @endpush
@endsection
