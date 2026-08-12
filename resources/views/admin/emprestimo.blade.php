@extends('layouts.admin')

@section('title', 'Emprestimos')
@section('heading', 'Controle de Empréstimos')
@section('subheading', 'Monitore concessões, fluxo de parcelas, taxas aplicadas e o status financeiro de contratos.')

@push('page-status-badge')
    <div class="page-status-badge">
        <span class="page-status-dot" aria-hidden="true"></span>
        Sistema de Gestão Ativo
    </div>
@endpush

@push('topbar-actions')
    <button type="button" class="kbtn kbtn-primary kbtn-loan-open" data-open-loan-modal>
        <span aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
        </span>
        Cadastrar Empréstimo
    </button>
@endpush

@section('content')
    <section class="kpage kpage-emprestimos page-admin-emprestimos emprestimos-page">
        <div class="kgrid kgrid-metricas kmargin-b-28">
            <div class="kmetric kmetric-card kmetric-total-emprestimos">
                <div class="kmetric-icon kmetric-icon-indigo" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM6.1 15.9L9.9 12.1L11.9 14.1L17.9 8.1L19.3 9.5L11.9 16.9L9.9 14.9L4.7 20.1L6.1 15.9Z" fill="currentColor"/></svg>
                </div>
                <div class="kmetric-copy">
                    <div class="kmetric-label">Total de Empréstimos</div>
                    <div class="kmetric-value"><strong id="loans-total">{{ $summary['total'] }}</strong></div>
                </div>
            </div>

            <div class="kmetric kmetric-card kmetric-operacoes-ativas">
                <div class="kmetric-icon kmetric-icon-emerald" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="kmetric-copy">
                    <div class="kmetric-label">Operações Ativas</div>
                    <div class="kmetric-value kmetric-value-emerald"><strong>{{ $summary['ativos'] }}</strong></div>
                </div>
            </div>

            <div class="kmetric kmetric-card kmetric-exibidos-filtro">
                <div class="kmetric-icon kmetric-icon-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/><path d="M20.5 20.5L16.65 16.65" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
                </div>
                <div class="kmetric-copy">
                    <div class="kmetric-label">Exibidos no Filtro</div>
                    <div class="kmetric-value kmetric-value-amber"><strong id="loans-filtered">{{ $summary['filtrados'] }}</strong></div>
                </div>
            </div>
        </div>

        <div class="kpanel kpanel-table-emprestimos">
            <form method="GET" action="{{ route('admin.emprestimos') }}" class="ktoolbar ktoolbar-emprestimos filter-form">
                <div class="kfield kfield-search search-field field">
                    <div class="kinput-wrap kinput-wrap-icon">
                        <span class="kinput-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <label for="busca" class="ksr-only">Buscar empréstimo</label>
                        <input
                            type="text"
                            id="busca"
                            name="busca"
                            value="{{ $filters['busca'] }}"
                            placeholder="Buscar cliente, valor, parcelas, vencimento..."
                            class="kinput kinput-emprestimo-busca"
                        >
                    </div>
                </div>

                <div class="kfield kfield-cobrador field">
                    <label for="cobrador_filtro" class="ksr-only">Cobrador responsável</label>
                    <input
                        type="text"
                        id="cobrador_filtro"
                        name="cobrador"
                        value="{{ $filters['cobrador'] }}"
                        placeholder="Cobrador responsável..."
                        class="kinput kinput-cobrador-filtro"
                        autocomplete="off"
                    >
                    <div class="autocomplete-menu" id="cobrador_filtro-suggestions" aria-hidden="true"></div>
                </div>

                <div class="kfield kfield-status status-field field">
                    <label for="status" class="ksr-only">Status</label>
                    <select id="status" name="status" class="js-enhanced-select kselect kselect-status">
                        <option value="todos" @selected($filters['status'] === 'todos')>Todos os status</option>
                        <option value="em_dia" @selected($filters['status'] === 'em_dia')>Em dia</option>
                        <option value="atrasado" @selected($filters['status'] === 'atrasado')>Atrasados</option>
                        <option value="analise" @selected($filters['status'] === 'analise')>Em Análise</option>
                        <option value="quitado" @selected($filters['status'] === 'quitado')>Quitados</option>
                    </select>
                </div>

                <div class="kfield kfield-action action-field field">
                    <button type="submit" class="kbtn kbtn-soft-primary">Filtrar</button>
                </div>
            </form>

            <div class="ktable-wrap ktable-wrap-emprestimos table-wrap">
                <table class="ktable ktable-emprestimos projects-table emprestimos-table">
                    <colgroup>
                        <col style="width: 22%;">
                        <col style="width: 12%;">
                        <col style="width: 14%;">
                        <col style="width: 12%;">
                        <col style="width: 10%;">
                        <col style="width: 12%;">
                        <col style="width: 18%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="kth-emprestimo kth-cliente" style="width: 22% !important; padding-left: 20px !important; padding-right: 18px !important;">Cliente</th>
                            <th style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">Valor</th>
                            <th style="width: 14% !important; padding-left: 18px !important; padding-right: 18px !important;">Parcelas</th>
                            <th style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">Vencimento</th>
                            <th style="width: 10% !important; padding-left: 18px !important; padding-right: 18px !important;">Tipo</th>
                            <th style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">Status</th>
                            <th class="kth-acoes text-right" style="width: 18% !important; padding-left: 18px !important; padding-right: 20px !important; text-align: right !important;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($loans->isEmpty())
                            <tr>
                                <td colspan="7">
                                    <div class="kempty kempty-emprestimos empty-state">
                                        <div class="kempty-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M7 10H17M7 14H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        </div>
                                        <h3>Nenhum empréstimo encontrado</h3>
                                        <p>Tente ajustar os filtros de busca ou cadastre um novo empréstimo no botão superior.</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach ($loans as $loan)
                                @php
                                    $statusLabel = match ($loan['status']) {
                                        'em_dia' => 'Em dia',
                                        'atrasado' => 'Atrasado',
                                        'analise' => 'Em análise',
                                        'quitado' => 'Quitado',
                                        default => str_replace('_', ' ', (string) ($loan['status'] ?? '')),
                                    };
                                    $statusClass = (string) ($loan['status'] ?? 'em_dia');
                                    $vencimentoValue = (string) ($loan['vencimento'] ?? '');
                                    $vencimentoDisplay = $vencimentoValue && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimentoValue) ? \Carbon\Carbon::parse($vencimentoValue)->format('d/m/Y') : $vencimentoValue;
                                    $loanJson = [
                                        'id' => (string) ($loan->getKey() ?? ''),
                                        'cliente' => (string) ($loan['cliente'] ?? ''),
                                        'valor' => (string) ($loan['valor'] ?? ''),
                                        'parcelas' => (string) ($loan['parcelas'] ?? ''),
                                        'numero_parcelas' => $loan['numero_parcelas'] ?? '',
                                        'vencimento' => $vencimentoValue,
                                        'vencimento_display' => $vencimentoDisplay,
                                        'tipo' => (string) ($loan['tipo'] ?? ''),
                                        'status' => (string) ($loan['status'] ?? ''),
                                        'data_emprestimo' => (string) ($loan['data_emprestimo'] ?? ''),
                                        'taxa_juros' => (string) ($loan['taxa_juros'] ?? ''),
                                        'tipo_juros' => (string) ($loan['tipo_juros'] ?? 'simples'),
                                        'intervalo' => (string) ($loan['intervalo'] ?? 'mensal'),
                                        'tipo_multa' => (string) ($loan['tipo_multa'] ?? 'percentual'),
                                        'valor_multa' => (string) ($loan['valor_multa'] ?? ''),
                                        'cobranca_multa' => (string) ($loan['cobranca_multa'] ?? 'automatica'),
                                        'cobrador' => (string) ($loan['cobrador'] ?? ''),
                                        'excecoes_dia' => array_values((array) ($loan['excecoes_dia'] ?? [])),
                                    ];
                                    $nomeCliente = (string) ($loan['cliente'] ?? 'Cliente');
                                    $partesNomeCli = preg_split('/\s+/', trim($nomeCliente));
                                    $primeiraLetraCli = mb_substr($partesNomeCli[0] ?? '?', 0, 1);
                                    $ultimaParteCli = !empty($partesNomeCli) ? end($partesNomeCli) : '';
                                    $segundaLetraCli = $ultimaParteCli ? mb_substr($ultimaParteCli, 0, 1) : '';
                                    $iniciaisCliente = mb_strtoupper($primeiraLetraCli . $segundaLetraCli);
                                    $avatarColors = ['kavatar-indigo', 'kavatar-emerald', 'kavatar-amber', 'kavatar-purple', 'kavatar-sky', 'kavatar-rose'];
                                    $avatarClass = $avatarColors[($loop->index ?? 0) % count($avatarColors)] ?? 'kavatar-indigo';
                                @endphp
                                <tr data-loan-row data-loan-id="{{ $loanJson['id'] }}" data-loan='@json($loanJson)' class="ktr-emprestimo emprestimo-row">
                                    <td class="ktd-cliente font-medium" style="width: 22% !important; padding-left: 20px !important; padding-right: 18px !important;">
                                        <div class="kcell-cliente kcell-emprestimo-cliente client-cell">
                                            <span class="kavatar kavatar-cliente kavatar-emprestimo client-avatar {{ $avatarClass }}">{{ $iniciaisCliente }}</span>
                                            <div class="kcell-cliente-meta kcell-emprestimo-meta client-cell-meta">
                                                <div class="kcliente-nome kcliente-nome-emprestimo client-name">{{ $nomeCliente }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-amount ktd-valor" style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">
                                        <span class="ktext-amount-emprestimo">{{ $loan['valor'] }}</span>
                                    </td>
                                    <td class="ktd-parcelas" style="width: 14% !important; padding-left: 18px !important; padding-right: 18px !important;">
                                        <span class="ktext-parcelas-emprestimo">{{ $loan['parcelas'] }}</span>
                                    </td>
                                    <td class="ktd-vencimento" style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">
                                        <span class="ktext-vencimento-emprestimo">{{ $vencimentoDisplay }}</span>
                                    </td>
                                    <td style="width: 10% !important; padding-left: 18px !important; padding-right: 18px !important;">
                                        <span class="ktype-badge ktype-badge-emprestimo type-badge">{{ $loan['tipo'] }}</span>
                                    </td>
                                    <td style="width: 12% !important; padding-left: 18px !important; padding-right: 18px !important;">
                                        <span class="kstatus-pill kstatus-pill-emprestimo kstatus-pill-{{ $statusClass }} status-badge status-{{ $statusClass }}">
                                            <span class="kstatus-dot kstatus-dot-{{ $statusClass }} status-dot" aria-hidden="true"></span>
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="ktd-acoes text-right" style="width: 18% !important; padding-left: 18px !important; padding-right: 20px !important; text-align: right !important;">
                                        <div class="krow-actions krow-actions-emprestimos table-actions">
                                            <button type="button" class="kaction-btn kaction-edit icon-btn" data-edit-loan title="Editar empréstimo" aria-label="Editar empréstimo">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.8"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                            </button>
                                            @can('delete-emprestimos')
                                                <button type="button" class="kaction-btn kaction-delete icon-btn icon-btn-danger" data-delete-loan title="Excluir empréstimo" aria-label="Excluir empréstimo">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9 7V5.93041C9 5.41647 9.41647 5 9.93041 5H14.0696C14.5835 5 15 5.41647 15 5.93041V7" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 11V16.5M14 11V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7.5 7.5L8.08734 18.4235C8.15732 19.6832 9.19859 20.6758 10.4613 20.6865L13.5401 20.7121C14.7941 20.7226 15.8284 19.7411 15.9069 18.4876L16.5 7.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                                </button>
                                            @endcan
                                            <button type="button" class="kaction-btn kaction-installments icon-btn icon-btn-primary" data-open-installments title="Acessar parcelas" aria-label="Acessar parcelas">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M8 2v4M16 2v4M8 14h3M13 14h3M8 18h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="kmodal-overlay kmodal-glass modal-overlay" id="loan-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="loan-modal-title">
        <div class="kmodal-card kmodal-card-light modal-card kloan-card">
            <div class="kmodal-header modal-header kloan-header">
                <div class="kloan-header-texts">
                    <span class="kloan-badge"><span class="kloan-badge-dot"></span>CONTRATO DE EMPRÉSTIMO</span>
                    <h2 id="loan-modal-title" class="kmodal-title kloan-title">Novo Empréstimo</h2>
                    <div class="kmodal-subtitle card-subtitle kloan-subtitle" id="loan-modal-subtitle">Preencha os parâmetros financeiros para simular e gerar o novo contrato.</div>
                </div>
                <button type="button" class="kmodal-close modal-close kloan-close" data-close-loan-modal aria-label="Fechar modal">&times;</button>
            </div>

            <form id="loan-form" class="kform-loan kloan-form">
                <div class="kform-scroll kloan-scroll">

                    <!-- 01 CONDIÇÕES DO EMPRÉSTIMO -->
                    <section class="kmodal-section kloan-section">
                        <h3 class="ksection-title kloan-section-title">
                            <span class="ksection-step">01</span>
                            CONDIÇÕES DO EMPRÉSTIMO
                        </h3>
                        <div class="ksection-divider"></div>

                        <div class="kform-grid form-grid kloan-grid">
                            <div class="kfield field kfield-full field-full">
                                <label for="cliente" class="kfield-label kloan-label">
                                    Cliente Associado <span class="kloan-required">*</span>
                                </label>
                                <select id="cliente" name="cliente" class="js-enhanced-select kselect">
                                    <option value="">Selecione um cliente</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client }}">{{ $client }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="kfield field kloan-calendar-field">
                                <label for="data_emprestimo" class="kfield-label kloan-label">Data de Concessão</label>
                                <input type="text" id="data_emprestimo_display" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off" class="kinput kloan-input">
                                <input type="hidden" id="data_emprestimo" name="data_emprestimo">
                                <span class="kloan-calendar-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                            </div>

                            <div class="kfield field">
                                <label for="valor_emprestimo" class="kfield-label kloan-label">Valor Principal</label>
                                <input type="text" id="valor_emprestimo" name="valor_emprestimo" placeholder="R$ 0,00" class="kinput kloan-input kloan-input-value">
                            </div>

                            <div class="kfield field">
                                <label for="taxa_juros" class="kfield-label kloan-label">Taxa de Juros (%)</label>
                                <input type="text" id="taxa_juros" name="taxa_juros" placeholder="Ex: 2,5%" class="kinput kloan-input">
                            </div>

                            <div class="kfield field">
                                <label for="tipo_juros" class="kfield-label kloan-label">Tipo de Juros</label>
                                <select id="tipo_juros" name="tipo_juros" class="js-enhanced-select kselect">
                                    <option value="simples">Simples</option>
                                    <option value="composto">Composto</option>
                                    <option value="fixo">Fixo</option>
                                </select>
                            </div>

                            <div class="kfield field">
                                <label for="numero_parcelas" class="kfield-label kloan-label">Número de Parcelas</label>
                                <input type="number" id="numero_parcelas" name="numero_parcelas" min="1" placeholder="Ex: 12" class="kinput kloan-input">
                            </div>

                            <div class="kfield field">
                                <label for="intervalo" class="kfield-label kloan-label">Intervalo de Cobrança</label>
                                <select id="intervalo" name="intervalo" class="js-enhanced-select kselect">
                                    <option value="diario">Diário</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="quinzenal">Quinzenal</option>
                                    <option value="mensal">Mensal</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- 02 REGRAS DE MULTA & COBRADOR -->
                    <section class="kmodal-section kloan-section">
                        <h3 class="ksection-title kloan-section-title">
                            <span class="ksection-step">02</span>
                            REGRAS DE MULTA & COBRADOR
                        </h3>
                        <div class="ksection-divider"></div>

                        <div class="kform-grid form-grid kloan-grid">
                            <div class="kfield field">
                                <label for="tipo_multa" class="kfield-label kloan-label">Tipo de Multa por Atraso</label>
                                <select id="tipo_multa" name="tipo_multa" class="js-enhanced-select kselect">
                                    <option value="percentual">Percentual (%)</option>
                                    <option value="fixa">Fixa (R$)</option>
                                </select>
                            </div>

                            <div class="kfield field">
                                <label for="valor_multa" class="kfield-label kloan-label">Valor / Métrica da Multa</label>
                                <input type="text" id="valor_multa" name="valor_multa" placeholder="Ex: 0,50 ou 2%" class="kinput kloan-input">
                            </div>

                            <div class="kfield field">
                                <label for="cobranca_multa" class="kfield-label kloan-label">Geração de Multa</label>
                                <select id="cobranca_multa" name="cobranca_multa" class="js-enhanced-select kselect">
                                    <option value="automatica">Automática pelo Sistema</option>
                                    <option value="manual">Manual pelo Gestor</option>
                                    <option value="desativada">Desativada</option>
                                </select>
                            </div>

                            <div class="kfield field kfield-cobrador">
                                <label for="cobrador" class="kfield-label kloan-label">Cobrador Responsável</label>
                                <input type="text" id="cobrador" name="cobrador" placeholder="Digite o nome do cobrador" class="kinput kloan-input" autocomplete="off">
                                <div class="autocomplete-menu" id="cobrador-suggestions" aria-hidden="true"></div>
                            </div>
                        </div>
                    </section>

                    <!-- 03 CALENDÁRIO & AJUSTES ADICIONAIS -->
                    <section class="kmodal-section kloan-section kloan-section-last">
                        <h3 class="ksection-title kloan-section-title">
                            <span class="ksection-step">03</span>
                            CALENDÁRIO & AJUSTES ADICIONAIS
                        </h3>
                        <div class="ksection-divider"></div>

                        <div class="kform-grid form-grid kloan-grid">
                            <div class="kfield kfield-full field field-full">
                                <label class="kfield-label kloan-label">Exceções de Calendário (Dias Não Úteis)</label>
                                <div class="kcheckbox-group checkbox-group kloan-cards-group">
                                    <label class="kloan-exception-card" for="anular_sabados">
                                        <input type="checkbox" id="anular_sabados" name="excecoes_dia[]" value="anular_sabados">
                                        <span class="kloan-exception-check" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                        <span class="kloan-exception-label">Ignorar Sábados</span>
                                    </label>
                                    <label class="kloan-exception-card" for="anular_domingos">
                                        <input type="checkbox" id="anular_domingos" name="excecoes_dia[]" value="anular_domingos">
                                        <span class="kloan-exception-check" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                        <span class="kloan-exception-label">Ignorar Domingos</span>
                                    </label>
                                    <label class="kloan-exception-card" for="anular_feriados">
                                        <input type="checkbox" id="anular_feriados" name="excecoes_dia[]" value="anular_feriados">
                                        <span class="kloan-exception-check" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                        <span class="kloan-exception-label">Ignorar Feriados</span>
                                    </label>
                                </div>
                            </div>

                            <div class="kfield kfield-full field field-full">
                                <label for="observacoes" class="kfield-label kloan-label">Observações ou Termos Adicionais</label>
                                <textarea id="observacoes" name="observacoes" rows="4" placeholder="Adicione notas internas ou condições específicas deste empréstimo..." class="ktextarea ktextarea-obs kloan-textarea"></textarea>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="kform-actions form-actions kmodal-footer kloan-footer">
                    <button type="button" class="kbtn kbtn-soft btn-secondary kloan-btn-cancel" data-close-loan-modal>Cancelar</button>
                    <button type="submit" class="kbtn kbtn-primary kloan-btn-submit" id="loan-submit-button">
                        Salvar Empréstimo
                        <span class="kloan-btn-arrow" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="kmodal-overlay kmodal-glass modal-overlay" id="installments-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="installments-modal-title">
        <div class="kmodal-card kmodal-card-large kmodal-card-light modal-card modal-card-large schedule-modal-card kschedule-card">

            <!-- ============ HEADER (badge + titulo + botoes acao) ============ -->
            <div class="kschedule-toolbar schedule-toolbar kschedule-header">
                <div class="kschedule-header-texts">
                    <span class="kschedule-badge"><span class="kschedule-badge-dot"></span>CONTROLE DE COBRANÇA</span>
                    <h2 class="kschedule-title schedule-title kschedule-title" id="installments-modal-title">Cronograma de Parcelas</h2>
                    <div class="kschedule-subtitle schedule-subtitle kschedule-subtitle" id="installments-modal-subtitle">Cliente — parcelas MENSAL.</div>
                </div>
                <div class="kschedule-actions schedule-actions kschedule-actions-row">
                    <button type="button" class="kbtn kbtn-soft-emerald schedule-btn btn-add-installment kschedule-btn kschedule-btn-add">
                        <span class="kschedule-btn-plus" aria-hidden="true">+</span>
                        <span>Parcela</span>
                    </button>
                    <button type="button" class="kbtn kbtn-soft-indigo schedule-btn btn-settle kschedule-btn kschedule-btn-settle">Quitar Contrato</button>
                    <button type="button" class="kbtn kbtn-soft schedule-btn btn-pdf kschedule-btn kschedule-btn-pdf">Exportar PDF</button>
                    <button type="button" class="kmodal-close modal-close kschedule-close kschedule-btn-close" data-close-installments-modal aria-label="Fechar modal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <!-- ============ 5 KPIs COLORIDOS (Receita Principal / Juros / Atraso / Saldo) ============ -->
            <div class="kschedule-summary schedule-summary kschedule-kpis" id="installments-summary">
                <!-- JS injeta 5 cards <div class="summary-item"> coloridos aqui -->
            </div>

            <!-- ============ TABELA DE PARCELAS ============ -->
            <div class="kschedule-table-wrap schedule-table-wrap kschedule-table-wrap">
                <table class="ktable ktable-schedule schedule-table kschedule-table">
                    <thead>
                        <tr>
                            <th class="kschedule-th kschedule-th-num">N° Parcela</th>
                            <th class="kschedule-th kschedule-th-venc">Vencimento</th>
                            <th class="kschedule-th kschedule-th-amort">Amortização</th>
                            <th class="kschedule-th kschedule-th-juros">Juros</th>
                            <th class="kschedule-th kschedule-th-multa">Multa</th>
                            <th class="kschedule-th kschedule-th-total">Valor Total</th>
                            <th class="kschedule-th kschedule-th-status">Status</th>
                            <th class="kschedule-th kschedule-th-acoes text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="installments-list"></tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- ============================================================
         MODAL DE CONFIRMAÇÃO DE EXCLUSÃO (CUSTOMIZADO, PREMIUM)
         Substitui o window.confirm padrão do navegador.
         Preservação: nenhuma lógica de rota/CSRF/método é removida; apenas
         intercepta o clique no [data-delete-loan] via JS.
         ============================================================ -->
    <div class="kmodal-overlay kmodal-glass modal-overlay" id="delete-loan-modal"
         aria-hidden="true" role="alertdialog" aria-modal="true"
         aria-labelledby="delete-loan-modal-title"
         aria-describedby="delete-loan-modal-message-1 delete-loan-modal-message-2"
         data-target-id="" data-target-name="">
        <div class="kmodal-card kmodal-card-light modal-card kconfirm-card kconfirm-delete-card">

            <!-- Ícone warning circular VERMELHO -->
            <div class="kconfirm-icon-wrap kconfirm-icon-danger" aria-hidden="true">
                <svg class="kconfirm-icon" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9V13" stroke="currentColor" stroke-width="2.2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 17H12.01" stroke="currentColor" stroke-width="2.6"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                          stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Título + Mensagens -->
            <div class="kconfirm-body">
                <h2 class="kconfirm-title" id="delete-loan-modal-title">Confirmar Exclusão</h2>

                <p class="kconfirm-message" id="delete-loan-modal-message-1">
                    Tem certeza que deseja remover
                    <strong class="kconfirm-target-name" id="delete-loan-target-name">este empréstimo</strong>?
                </p>

                <p class="kconfirm-message kconfirm-message-muted" id="delete-loan-modal-message-2">
                    Esta ação não pode ser desfeita.
                </p>
            </div>

            <!-- Botões: Cancelar + Sim, Excluir -->
            <div class="kconfirm-actions">
                <button type="button"
                        class="kbtn kbtn-soft btn-secondary kconfirm-btn kconfirm-btn-cancel"
                        data-close-delete-loan-modal
                        id="delete-loan-btn-cancel">
                    Cancelar
                </button>

                <button type="button"
                        class="kbtn kbtn-danger kconfirm-btn kconfirm-btn-submit"
                        id="delete-loan-btn-submit"
                        aria-label="Confirmar exclusão do empréstimo">
                    Sim, Excluir
                </button>
            </div>

        </div>
    </div>
    <!-- FIM MODAL CONFIRMAÇÃO EXCLUSÃO -->

    <div class="kmodal-overlay kmodal-glass modal-overlay" id="receive-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="receive-modal-title">
        <div class="kmodal-card kmodal-card-light modal-card receive-modal-card kreceive-card">

            <!-- ============ HEADER (badge + titulo + subtitulo + fechar) ============ -->
            <div class="kreceive-header kmodal-header">
                <div class="kreceive-header-texts">
                    <span class="kreceive-badge"><span class="kreceive-badge-dot"></span>BAIXA DE PARCELA</span>
                    <h2 id="receive-modal-title" class="kreceive-title kmodal-title">Registrar Recebimento — Parcela 1</h2>
                    <div class="kmodal-subtitle card-subtitle kreceive-subtitle" id="receive-modal-subtitle">Revise os valores e confirme a baixa no sistema financeiro.</div>
                </div>
                <button type="button" class="kmodal-close modal-close kreceive-close" data-close-receive-modal aria-label="Fechar modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- ============ RESUMO DE VALORACAO (3 linhas: principal / juros / total roxo) ============ -->
            <div class="kreceive-summary receive-summary" id="receive-summary">
                <!-- JS injeta aqui: .receive-row (Principal/Juros) + .receive-divider + .receive-row receive-total (Total a Receber ROXO) -->
            </div>

            <!-- ============ FORMULARIO ============ -->
            <form class="kreceive-form" onsubmit="event.preventDefault();" novalidate>
                <div class="kreceive-field">
                    <label for="receive-date" class="kreceive-label">Data do Recebimento</label>
                    <div class="kreceive-input-wrap kreceive-date-wrap">
                        <input type="date" id="receive-date" name="receive_date" class="kreceive-input kinput kinput-date">
                        <span class="kreceive-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                    </div>
                </div>

                <div class="kreceive-field">
                    <label for="receive-amount" class="kreceive-label">Valor do Pagamento</label>
                    <input type="text" id="receive-amount" name="receive_amount" placeholder="R$ 0,00" class="kreceive-input kreceive-input-amount kinput">
                </div>

                <!-- Checkbox card interativo: SOMENTE JUROS -->
                <label class="kreceive-interest-card" for="receive-only-interest">
                    <input type="checkbox" id="receive-only-interest" name="receive_only_interest" value="1">
                    <span class="kreceive-interest-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="kreceive-interest-label">O cliente realizou o pagamento apenas dos juros acumulados (R$ 0,00)</span>
                </label>
            </form>

            <!-- ============ RODAPE (Cancelar / Confirmar Baixa ->) ============ -->
            <div class="kreceive-footer kform-actions kmodal-footer">
                <button type="button" class="kbtn kbtn-soft btn-secondary kreceive-btn-cancel" data-close-receive-modal>Cancelar</button>
                <button type="button" class="kbtn kbtn-primary kreceive-btn-submit" id="receive-submit-button">
                    Confirmar Baixa
                    <span class="kreceive-btn-arrow" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </span>
                </button>
            </div>

        </div>
    </div>

    @push('scripts')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/js/admin/emprestimo.js'])
        @else
            <script src="{{ route('assets.admin.emprestimo', [], false) }}" defer></script>
        @endif
    @endpush

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/emprestimos.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.emprestimos_css', [], false) }}">
        @endif
    @endpush
@endsection
