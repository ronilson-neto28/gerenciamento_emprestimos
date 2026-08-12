@extends('layouts.admin')

@section('title', 'Relatórios')
@section('heading', 'Relatório de Movimentações')
@section('subheading', 'Acompanhe o desempenho operacional da cobrança e os recebimentos realizados.')

@push('page-status-badge')
    <div class="page-status-badge">
        <span class="page-status-dot" aria-hidden="true"></span>
        Sistema de Gestão Ativo
    </div>
@endpush

@section('content')
    <section class="kpage kpage-relatorios relatorios-page">
        <div class="kpanel kpanel-filtros-relatorios kmargin-b-28">
            <div class="kheader-relatorios kmargin-b-20">
                <div>
                    <h2 class="kpanel-title">Relatório de Movimentações</h2>
                    <div class="kpanel-subtitle">Histórico de recebimentos e ações executadas pelos cobradores.</div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.relatorios') }}" class="ktoolbar ktoolbar-relatorios filter-form">
                @if ($isAdmin)
                    <div class="kfield kfield-cobrador">
                        <label for="cobrador" class="kfield-label kfield-label-rel">Cobrador</label>
                        <select id="cobrador" name="cobrador" class="js-enhanced-select kselect kselect-cobrador">
                            <option value="">Todos os cobradores</option>
                            @foreach ($cobradores as $cobrador)
                                <option value="{{ (string) ($cobrador->id ?? $cobrador->getKey() ?? '') }}" @selected($filters['cobrador'] === (string) ($cobrador->id ?? $cobrador->getKey() ?? ''))>
                                    {{ $cobrador['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="kfield kfield-periodo">
                    <label for="de" class="kfield-label kfield-label-rel">De</label>
                    <input id="de" name="de" type="date" value="{{ $filters['de'] }}" class="kinput kinput-date">
                </div>

                <div class="kfield kfield-periodo">
                    <label for="ate" class="kfield-label kfield-label-rel">Até</label>
                    <input id="ate" name="ate" type="date" value="{{ $filters['ate'] }}" class="kinput kinput-date">
                </div>

                <div class="kfield kfield-action action-field">
                    <label class="ksr-only" for="btn-filtrar-rel">Filtrar</label>
                    <button type="submit" class="kbtn kbtn-soft-primary" id="btn-filtrar-rel">Filtrar</button>
                </div>
            </form>

            <div class="kdivider-relatorios"></div>

            <div class="kpills kpills-metricas">
                <span class="kpill kpill-operadores">
                    <span class="kpill-dot kpill-dot-indigo" aria-hidden="true"></span>
                    Operadores: <strong>{{ $summary['operadores'] }}</strong>
                </span>
                <span class="kpill kpill-total-recebido">
                    <span class="kpill-dot kpill-dot-emerald" aria-hidden="true"></span>
                    Total recebido: <strong class="kpill-strong-emerald">{{ $summary['total_recebido'] }}</strong>
                </span>
                <span class="kpill kpill-recebimentos">
                    <span class="kpill-dot kpill-dot-amber" aria-hidden="true"></span>
                    Recebimentos: <strong>{{ $summary['recebimentos'] }}</strong>
                </span>
                <span class="kpill kpill-emprestimos">
                    <span class="kpill-dot kpill-dot-sky" aria-hidden="true"></span>
                    Empréstimos criados: <strong>{{ $summary['emprestimos'] }}</strong>
                </span>
            </div>
        </div>

        <div class="kgrid kgrid-2-colunas">
            <article class="kpanel kpanel-resumo-cobrador">
                <div class="kpanel-header kmargin-b-16">
                    <div>
                        <h3 class="kpanel-title-sm">Resumo por cobrador</h3>
                        <div class="kpanel-subtitle-sm">Totais de recebimento e quantidade de operações no período filtrado.</div>
                    </div>
                </div>

                @if ($rows->isEmpty())
                    <div class="kempty kempty-relatorios">
                        <div class="kempty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7V17C3 18.1046 3.89543 19 5 19H19C20.1046 19 21 18.1046 21 17V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="3" y="4" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M7 10H13M7 14H11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </div>
                        <h3>Nenhuma movimentação encontrada</h3>
                        <p>Tente ajustar os filtros de período para visualizar dados de desempenho.</p>
                    </div>
                @else
                    <div class="ktable-wrap ktable-wrap-resumo">
                        <table class="ktable ktable-resumo-cobradores relatorios-resumo-table">
                            <colgroup>
                                <col style="width: 30%;">
                                <col style="width: 22%;">
                                <col style="width: 20%;">
                                <col style="width: 14%;">
                                <col style="width: 14%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th style="width:30%;">Nome</th>
                                    <th style="width:22%;">E-mail</th>
                                    <th style="width:20%;">Total Recebido</th>
                                    <th style="width:14%;">Recebimentos</th>
                                    <th style="width:14%;">Empréstimos Criados</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="ktr-cobrador">
                                        <td class="ktd-nome-cobrador font-medium" style="width:30%;">
                                            <div class="kcell-cobrador">
                                                <span class="kavatar kavatar-resumo kavatar-indigo">
                                                    @php
                                                        $nomeCobrador = (string) ($row['nome'] ?? 'Cobrador');
                                                        $partesNome = preg_split('/\s+/', trim($nomeCobrador));
                                                        $primeiraLetra = mb_substr($partesNome[0] ?? '?', 0, 1);
                                                        $ultimaParte = !empty($partesNome) ? end($partesNome) : '';
                                                        $segundaLetra = $ultimaParte ? mb_substr($ultimaParte, 0, 1) : '';
                                                        $iniciaisAvatar = mb_strtoupper($primeiraLetra . $segundaLetra);
                                                    @endphp
                                                    {{ $iniciaisAvatar }}
                                                </span>
                                                <div class="kcell-cobrador-meta">
                                                    <div class="kcobrador-nome">{{ $row['nome'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="ktd-email-cobrador" style="width:22%;">{{ !empty($row['email']) ? (string) $row['email'] : '-' }}</td>
                                        <td style="width:20%;">
                                            <span class="ktotal-recebido-valor">{{ $row['total_recebido'] }}</span>
                                        </td>
                                        <td style="width:14%;">
                                            <span class="kbadge-count kbadge-count-receb">{{ $row['recebimentos'] }}</span>
                                        </td>
                                        <td style="width:14%;">
                                            <span class="kbadge-count kbadge-count-emp">{{ $row['emprestimos_criados'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <aside class="kpanel kpanel-acoes-recentes">
                <div class="kpanel-header kmargin-b-16">
                    <div>
                        <h3 class="kpanel-title-sm">Ações recentes</h3>
                        <div class="kpanel-subtitle-sm">Últimas baixas registradas no período.</div>
                    </div>
                </div>

                <div class="kactivity-list kactivity-list-recente activity-list">
                    @forelse ($activities as $activity)
                        @php($tipoAtividade = mb_strtolower(trim((string) ($activity['tipo'] ?? 'Parcial'))))
                        @php($isTotal = $tipoAtividade === 'total')
                        <div class="kactivity-item">
                            <div class="kactivity-icon-wrap {{ $isTotal ? 'kactivity-icon-indigo' : 'kactivity-icon-emerald' }}" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="kactivity-copy">
                                <div class="kactivity-operador">{{ (string) ($activity['operador'] ?? 'Operador') }}</div>
                                <div class="kactivity-time">{{ ucfirst($tipoAtividade) }} em {{ (string) ($activity['data'] ?? '') }}</div>
                            </div>
                            <div class="kactivity-value">{{ (string) ($activity['valor'] ?? '—') }}</div>
                        </div>
                    @empty
                        <div class="kactivity-item kactivity-empty">
                            <div class="kactivity-icon-wrap kactivity-icon-gray" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                            </div>
                            <div class="kactivity-copy">
                                <div class="kactivity-operador">Nenhuma ação registrada</div>
                                <div class="kactivity-time">Ainda não há baixas efetuadas nesse período.</div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/relatorios.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.relatorios_css', [], false) }}">
        @endif
    @endpush
@endsection
