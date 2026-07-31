@extends('layouts.admin')

@section('title', 'Relatórios')
@section('heading', 'Relatório de Movimentações')
@section('subheading', 'Acompanhe o desempenho operacional da cobrança e os recebimentos realizados.')

@section('content')
    <section class="card">
        <div class="card-header-zone">
            <div>
                <h1 class="main-title">Relatório de Movimentações</h1>
                <p class="main-subtitle">Histórico de recebimentos e ações executadas pelos cobradores.</p>
            </div>
        </div>

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.relatorios') }}" class="filter-form" style="grid-template-columns: repeat({{ $isAdmin ? 3 : 2 }}, minmax(0, 1fr)) auto;">
                @if ($isAdmin)
                    <div class="field">
                        <label for="cobrador">Cobrador</label>
                        <select id="cobrador" name="cobrador" class="js-enhanced-select">
                            <option value="">Todos</option>
                            @foreach ($cobradores as $cobrador)
                                <option value="{{ (string) ($cobrador->id ?? $cobrador->getKey() ?? '') }}" @selected($filters['cobrador'] === (string) ($cobrador->id ?? $cobrador->getKey() ?? ''))>
                                    {{ $cobrador['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="field">
                    <label for="de">De</label>
                    <input id="de" name="de" type="date" value="{{ $filters['de'] }}">
                </div>

                <div class="field">
                    <label for="ate">Até</label>
                    <input id="ate" name="ate" type="date" value="{{ $filters['ate'] }}">
                </div>

                <div class="field action-field">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-secondary">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="table-meta">
            <span class="meta-pill"><span class="dot dot-blue"></span> Operadores: <strong>{{ $summary['operadores'] }}</strong></span>
            <span class="meta-pill"><span class="dot dot-green"></span> Total recebido: <strong>{{ $summary['total_recebido'] }}</strong></span>
            <span class="meta-pill"><span class="dot dot-gray"></span> Recebimentos: <strong>{{ $summary['recebimentos'] }}</strong></span>
            <span class="meta-pill"><span class="dot dot-blue"></span> Empréstimos criados: <strong>{{ $summary['emprestimos'] }}</strong></span>
        </div>
    </section>

    <section class="grid" style="grid-template-columns: 1.5fr 1fr; align-items: start; margin-top: 20px;">
        <article class="card">
            <div class="card-header">
                <div>
                    <h2>Resumo por cobrador</h2>
                    <div class="card-subtitle">Totais de recebimento e quantidade de operações no período filtrado.</div>
                </div>
            </div>

            @if ($rows->isEmpty())
                <div class="empty-state">Nenhuma movimentação encontrada para os filtros informados.</div>
            @else
                <div class="table-wrap">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Total recebido</th>
                                <th>Recebimentos</th>
                                <th>Empréstimos criados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['nome'] }}</td>
                                    <td>{{ $row['email'] ?: '-' }}</td>
                                    <td>{{ $row['total_recebido'] }}</td>
                                    <td>{{ $row['recebimentos'] }}</td>
                                    <td>{{ $row['emprestimos_criados'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <aside class="card">
            <div class="card-header">
                <div>
                    <h2>Ações recentes</h2>
                    <div class="card-subtitle">Últimas baixas registradas no período.</div>
                </div>
            </div>

            <div class="activity-list">
                @forelse ($activities as $activity)
                    <div class="activity-item">
                        <strong>{{ $activity['operador'] }}</strong>
                        <div class="activity-time">{{ ucfirst($activity['tipo']) }} em {{ $activity['data'] }}</div>
                        <div class="activity-time">{{ $activity['valor'] }}</div>
                    </div>
                @empty
                    <div class="activity-item">
                        <strong>Nenhuma ação encontrada</strong>
                        <div class="activity-time">Ainda não há baixas registradas nesse período.</div>
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/relatorios.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.relatorios_css', [], false) }}">
        @endif
    @endpush
@endsection
