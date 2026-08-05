@extends('layouts.admin')

@section('title', 'Financeiro')
@section('heading', 'Financeiro')
@section('subheading', 'Gerencie suas movimentacoes')

@section('content')
    <section class="grid finance-stats">
        <article class="card finance-stat stat-entrada">
            <div class="stat-inner">
                <div class="stat-info-group">
                    <span class="finance-stat-title">Entradas Totais</span>
                    <div class="finance-stat-value finance-positive">R$ {{ number_format($summary['entradas'], 2, ',', '.') }}</div>
                </div>
                <div class="stat-icon-bg bg-green-light" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="stat-icon text-green" stroke="currentColor" stroke-width="2.5">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-footer">
                <span class="stat-trend trend-up">
                    <span class="trend-arrow">▲</span> +12.4% <span class="trend-period">em relação a abr</span>
                </span>
            </div>
        </article>

        <article class="card finance-stat stat-saida">
            <div class="stat-inner">
                <div class="stat-info-group">
                    <span class="finance-stat-title">Saídas de Caixa</span>
                    <div class="finance-stat-value finance-negative">R$ {{ number_format($summary['saidas'], 2, ',', '.') }}</div>
                </div>
                <div class="stat-icon-bg bg-red-light" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="stat-icon text-red" stroke="currentColor" stroke-width="2.5">
                        <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                        <polyline points="17 18 23 18 23 12"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-footer">
                <span class="stat-trend trend-down">
                    <span class="trend-arrow">▼</span> -3.2% <span class="trend-period">redução de custos</span>
                </span>
            </div>
        </article>

        <article class="card finance-stat stat-investimento">
            <div class="stat-inner">
                <div class="stat-info-group">
                    <span class="finance-stat-title">Empréstimos (principal)</span>
                    <div class="finance-stat-value finance-neutral">R$ {{ number_format($summary['investimentos'], 2, ',', '.') }}</div>
                </div>
                <div class="stat-icon-bg bg-blue-light" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="stat-icon text-blue" stroke="currentColor" stroke-width="2.5">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-footer">
                <span class="stat-trend trend-neutral">
                    <span class="trend-dot"></span> Alocação estável <span class="trend-period">(16.2%)</span>
                </span>
            </div>
        </article>

        <article class="card finance-stat stat-resultado">
            <div class="stat-inner">
                <div class="stat-info-group">
                    <span class="finance-stat-title">Capital (caixa)</span>
                    <div class="finance-stat-value finance-positive-strong">R$ {{ number_format($summary['resultado'], 2, ',', '.') }}</div>
                </div>
                <div class="stat-icon-bg bg-indigo-light" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="stat-icon text-indigo" stroke="currentColor" stroke-width="2.5">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-footer">
                <span class="stat-trend trend-up">
                    <span class="trend-arrow">▲</span> Margem de 57.1% <span class="trend-period">saudável</span>
                </span>
            </div>
        </article>
    </section>

    <section class="finance-section">
        <div class="finance-section-header">
            <div>
                <h2 class="finance-section-title">Fluxo de Lançamentos</h2>
                <p class="main-subtitle">Acompanhe o histórico analítico de contas a pagar e receber do período.</p>
            </div>
            <div class="finance-filters">
                <div class="select-wrapper">
                    <select class="finance-filter-select js-enhanced-select" aria-label="Filtro de tipo">
                        <option value="todos">Todos os registros</option>
                        <option value="entrada">Apenas Entradas</option>
                        <option value="saida">Apenas Saídas</option>
                    </select>
                </div>

                <button type="button" class="btn-secondary finance-period-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="18" y2="10"></line>
                    </svg>
                    Filtrar por Período
                </button>

                <button type="button" class="btn-primary finance-add-btn" data-open-finance-modal>
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Novo Lançamento
                </button>
            </div>
        </div>

        <article class="card finance-list-card">
            @if (($lancamentos ?? collect())->isEmpty())
                <div class="finance-empty">
                    <div class="finance-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Nenhum lançamento registrado</h3>
                    <p class="activity-time">A sua saúde financeira começa com o registro correto das movimentações de caixa corporativas.</p>
                    <button type="button" class="btn-primary finance-empty-btn" data-open-finance-modal>Criar Primeiro Lançamento</button>
                </div>
            @else
                <div class="finance-table-wrap">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lancamentos as $item)
                                @php($type = (string) ($item->type ?? ''))
                                <tr>
                                    <td>
                                        <span class="finance-type-pill finance-type-{{ $type === 'saida' ? 'saida' : 'entrada' }}">
                                            {{ $type === 'saida' ? 'Saída' : 'Entrada' }}
                                        </span>
                                    </td>
                                    <td class="finance-desc">
                                        {{ (string) ($item->description ?? '') }}
                                    </td>
                                    <td class="finance-date">
                                        {{ \Carbon\Carbon::parse($item->date ?? now()->toDateString())->format('d/m/Y') }}
                                    </td>
                                    <td class="finance-value">
                                        R$ {{ number_format((float) ($item->value ?? 0), 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    <div class="modal-overlay" id="finance-modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2>Registrar Movimentação</h2>
                    <div class="card-subtitle">Insira fluxos operacionais ou de faturamento diretamente no caixa.</div>
                </div>

                <button type="button" class="modal-close" data-close-finance-modal aria-label="Fechar modal">&times;</button>
            </div>

            <form id="finance-form">
                <div class="form-grid">
                    <div class="field field-full">
                        <label for="finance-type">Tipo de Fluxo</label>
                        <select id="finance-type" name="type" class="js-enhanced-select">
                            <option value="entrada" selected>Entrada (Crédito)</option>
                            <option value="saida">Saída (Débito)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="finance-value">Valor líquido (R$)</label>
                        <input type="text" id="finance-value" name="value" placeholder="R$ 0,00">
                    </div>

                    <div class="field">
                        <label for="finance-date">Data da operação</label>
                        <input type="text" id="finance-date-display" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off">
                        <input type="hidden" id="finance-date" name="date" value="{{ now()->toDateString() }}">
                    </div>

                    <div class="field field-full">
                        <label for="finance-description">Descrição / Justificativa</label>
                        <textarea id="finance-description" name="description" placeholder="Ex: Pagamento do servidor em nuvem ou recebimento de fatura..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" data-close-finance-modal>Cancelar</button>
                    <button type="submit" class="btn-primary">Efetivar Lançamento</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/js/admin/financeiro.js'])
        @else
            <script src="{{ route('assets.admin.financeiro', ['v' => filemtime(resource_path('js/admin/financeiro.js'))]) }}" defer></script>
        @endif
    @endpush

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/financeiro.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.financeiro_css', [], false) }}">
        @endif
    @endpush
@endsection
