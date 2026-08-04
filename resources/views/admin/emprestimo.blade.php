@extends('layouts.admin')

@section('title', 'Emprestimos')
@section('heading', 'Controle de Empréstimos')
@section('subheading', 'Monitore concessões, fluxo de parcelas, taxas aplicadas e o status financeiro de contratos.')

@section('content')
    <section class="card">
        <div class="card-header-zone">
            <div>
                <h1 class="main-title">Controle de Empréstimos</h1>
                <p class="main-subtitle">Monitore concessões, fluxo de parcelas, taxas aplicadas e o status financeiro de contratos.</p>
            </div>
            <button type="button" class="btn-primary" data-open-loan-modal>
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Cadastrar Empréstimo
            </button>
        </div>

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.emprestimos') }}" class="filter-form">
                <div class="field search-field">
                    <label for="busca">Buscar empréstimo</label>
                    <div class="input-icon-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input
                            type="text"
                            id="busca"
                            name="busca"
                            value="{{ $filters['busca'] }}"
                            placeholder="Digite cliente, valor, parcelas, vencimento, tipo ou status"
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="cobrador_filtro">Cobrador</label>
                    <input
                        type="text"
                        id="cobrador_filtro"
                        name="cobrador"
                        value="{{ $filters['cobrador'] }}"
                        placeholder="Digite o nome do cobrador"
                    >
                    <div class="autocomplete-menu" id="cobrador_filtro-suggestions" aria-hidden="true"></div>
                </div>

                <div class="field status-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="js-enhanced-select">
                        <option value="todos" @selected($filters['status'] === 'todos')>Todos os status</option>
                        <option value="em_dia" @selected($filters['status'] === 'em_dia')>Em dia</option>
                        <option value="atrasado" @selected($filters['status'] === 'atrasado')>Atrasados</option>
                        <option value="analise" @selected($filters['status'] === 'analise')>Em Análise</option>
                        <option value="quitado" @selected($filters['status'] === 'quitado')>Quitados</option>
                    </select>
                </div>

                <div class="field action-field">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-secondary">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="table-meta">
            <span class="meta-pill id-total">
                <span class="dot dot-blue"></span> Total de empréstimos: <strong id="loans-total">{{ $summary['total'] }}</strong>
            </span>
            <span class="meta-pill id-filtered">
                <span class="dot dot-gray"></span> Exibidos no filtro: <strong id="loans-filtered">{{ $summary['filtrados'] }}</strong>
            </span>
            <span class="meta-pill id-active">
                <span class="dot dot-green"></span> Operações ativas: <strong>{{ $summary['ativos'] }}</strong>
            </span>
        </div>

        @if ($loans->isEmpty())
            <div class="empty-state">
                Nenhum emprestimo encontrado para os filtros informados.
            </div>
        @else
            <div class="table-wrap">
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Parcelas</th>
                            <th>Vencimento</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loans as $loan)
                            @php($statusLabel = match ($loan['status']) {
                                'em_dia' => 'Em dia',
                                'atrasado' => 'Atrasado',
                                'analise' => 'Em análise',
                                'quitado' => 'Quitado',
                                default => str_replace('_', ' ', $loan['status'] ?? ''),
                            })
                            @php($vencimentoValue = (string) ($loan['vencimento'] ?? ''))
                            @php($vencimentoDisplay = preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimentoValue) ? substr($vencimentoValue, 8, 2) . '/' . substr($vencimentoValue, 5, 2) . '/' . substr($vencimentoValue, 0, 4) : $vencimentoValue)
                            <tr
                                data-loan-row
                                data-loan-id="{{ $loan->getKey() }}"
                                data-cliente="{{ $loan['cliente'] }}"
                                data-valor="{{ $loan['valor'] }}"
                                data-parcelas="{{ $loan['parcelas'] }}"
                                data-numero-parcelas="{{ $loan['numero_parcelas'] }}"
                                data-vencimento="{{ $vencimentoValue }}"
                                data-tipo="{{ $loan['tipo'] }}"
                                data-status="{{ $loan['status'] }}"
                                data-data-emprestimo="{{ $loan['data_emprestimo'] }}"
                                data-taxa-juros="{{ $loan['taxa_juros'] }}"
                                data-tipo-juros="{{ $loan['tipo_juros'] }}"
                                data-intervalo="{{ $loan['intervalo'] }}"
                                data-tipo-multa="{{ $loan['tipo_multa'] }}"
                                data-valor-multa="{{ $loan['valor_multa'] }}"
                                data-cobranca-multa="{{ $loan['cobranca_multa'] }}"
                                data-cobrador="{{ $loan['cobrador'] ?? '' }}"
                                data-excecoes-dia="{{ implode(',', (array) ($loan['excecoes_dia'] ?? [])) }}"
                            >
                                <td class="font-medium">{{ $loan['cliente'] }}</td>
                                <td class="text-amount">{{ $loan['valor'] }}</td>
                                <td>{{ $loan['parcelas'] }}</td>
                                <td>{{ $vencimentoDisplay }}</td>
                                <td><span class="type-badge">{{ $loan['tipo'] }}</span></td>
                                <td>
                                    <span class="status-badge status-{{ $loan['status'] }}">
                                        <span class="status-dot" aria-hidden="true"></span> {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="icon-btn" data-edit-loan title="Editar emprestimo" aria-label="Editar emprestimo">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        @can('delete-emprestimos')
                                            <button type="button" class="icon-btn icon-btn-danger" data-delete-loan title="Excluir emprestimo" aria-label="Excluir emprestimo">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        @endcan
                                        <button type="button" class="icon-btn icon-btn-primary" data-open-installments title="Acessar parcelas" aria-label="Acessar parcelas">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                                <path d="M3 10h18M8 2v4M16 2v4M8 14h3M13 14h3M8 18h3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="modal-overlay" id="loan-modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2 id="loan-modal-title">Novo Empréstimo</h2>
                    <div class="card-subtitle" id="loan-modal-subtitle">Preencha os dados principais para iniciar um novo contrato de empréstimo.</div>
                </div>

                <button type="button" class="modal-close" data-close-loan-modal aria-label="Fechar modal">&times;</button>
            </div>

            <form id="loan-form">
                <div class="form-grid">
                    <div class="field">
                        <label for="cliente">Cliente associado</label>
                        <select id="cliente" name="cliente" class="js-enhanced-select">
                            <option value="">Selecione um cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client }}">{{ $client }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="data_emprestimo">Data de concessão</label>
                        <input type="text" id="data_emprestimo_display" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off">
                        <input type="hidden" id="data_emprestimo" name="data_emprestimo">
                    </div>

                    <div class="field">
                        <label for="valor_emprestimo">Valor principal</label>
                        <input type="text" id="valor_emprestimo" name="valor_emprestimo" placeholder="R$ 0,00">
                    </div>

                    <div class="field">
                        <label for="taxa_juros">Taxa de juros</label>
                        <input type="text" id="taxa_juros" name="taxa_juros" placeholder="Ex: 2,5%">
                    </div>

                    <div class="field">
                        <label for="tipo_juros">Tipo de juros</label>
                        <select id="tipo_juros" name="tipo_juros" class="js-enhanced-select">
                            <option value="simples">Simples</option>
                            <option value="composto">Composto</option>
                            <option value="fixo">Fixo</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="numero_parcelas">Numero de parcelas</label>
                        <input type="number" id="numero_parcelas" name="numero_parcelas" min="1" placeholder="Ex: 12">
                    </div>

                    <div class="field">
                        <label for="intervalo">Intervalo de cobrança</label>
                        <select id="intervalo" name="intervalo" class="js-enhanced-select">
                            <option value="diario">Diario</option>
                            <option value="semanal">Semanal</option>
                            <option value="quinzenal">Quinzenal</option>
                            <option value="mensal">Mensal</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="tipo_multa">Tipo de multa por atraso</label>
                        <select id="tipo_multa" name="tipo_multa" class="js-enhanced-select">
                            <option value="percentual">Percentual</option>
                            <option value="fixa">Fixa</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="valor_multa">Valor/Metrica da multa (diario)</label>
                        <input type="text" id="valor_multa" name="valor_multa" placeholder="Ex: 0,50 ou 2%">
                    </div>

                    <div class="field">
                        <label for="cobranca_multa">Geracao de multa</label>
                        <select id="cobranca_multa" name="cobranca_multa" class="js-enhanced-select">
                            <option value="automatica">Automatica pelo Sistema</option>
                            <option value="manual">Manual pelo Gestor</option>
                            <option value="desativada">Desativada</option>
                        </select>
                    </div>

                    <div class="field field-full">
                        <label for="cobrador">COBRADOR</label>
                        <input type="text" id="cobrador" name="cobrador" placeholder="Digite o nome do cobrador responsavel">
                        <div class="autocomplete-menu" id="cobrador-suggestions" aria-hidden="true"></div>
                    </div>

                    <div class="field field-full">
                        <label>Excecoes de calendario (Dias nao uteis)</label>
                        <div class="checkbox-group">
                            <label class="checkbox-option" for="anular_sabados">
                                <input type="checkbox" id="anular_sabados" name="excecoes_dia[]" value="anular_sabados">
                                <span>Ignorar Sabados</span>
                            </label>

                            <label class="checkbox-option" for="anular_domingos">
                                <input type="checkbox" id="anular_domingos" name="excecoes_dia[]" value="anular_domingos">
                                <span>Ignorar Domingos</span>
                            </label>

                            <label class="checkbox-option" for="anular_feriados">
                                <input type="checkbox" id="anular_feriados" name="excecoes_dia[]" value="anular_feriados">
                                <span>Ignorar Feriados</span>
                            </label>
                        </div>
                    </div>

                    <div class="field field-full">
                        <label for="observacoes">Observacoes ou Termos Adicionais</label>
                        <textarea id="observacoes" name="observacoes" placeholder="Adicione notas internas ou condicoes especificas deste contrato de emprestimo..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" data-close-loan-modal>Cancelar</button>
                    <button type="submit" class="btn-primary" id="loan-submit-button">Salvar Contrato</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="installments-modal" aria-hidden="true">
        <div class="modal-card modal-card-large schedule-modal-card">
            <div class="schedule-toolbar">
                <div>
                    <h2 class="schedule-title" id="installments-modal-title">Cronograma de Parcelas</h2>
                    <div class="schedule-subtitle" id="installments-modal-subtitle">Provisionamento e controle de fluxo para parcelas MENSAIS.</div>
                </div>

                <div class="schedule-actions">
                    <button type="button" class="schedule-btn btn-add-installment">+ Parcela</button>
                    <button type="button" class="schedule-btn btn-settle">Quitar Contrato</button>
                    <button type="button" class="schedule-btn btn-pdf">Exportar PDF</button>
                    <button type="button" class="modal-close-round" data-close-installments-modal aria-label="Fechar modal">&times;</button>
                </div>
            </div>

            <div class="schedule-summary" id="installments-summary"></div>

            <div class="schedule-table-wrap">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Nº Parcela</th>
                            <th>Vencimento</th>
                            <th>Amortizacao</th>
                            <th>Juros</th>
                            <th>Multa</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="installments-list"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="receive-modal" aria-hidden="true">
        <div class="modal-card receive-modal-card">
            <div class="modal-header">
                <div>
                    <h2 id="receive-modal-title">Registrar Recebimento</h2>
                    <div class="card-subtitle" id="receive-modal-subtitle">Revise e edite os valores finais antes de efetivar a baixa da parcela.</div>
                </div>

                <button type="button" class="modal-close" data-close-receive-modal aria-label="Fechar modal">&times;</button>
            </div>

            <div class="receive-summary" id="receive-summary"></div>

            <div class="form-grid">
                <div class="field field-full">
                    <label for="receive-date">Data do Recebimento</label>
                    <input type="date" id="receive-date" name="receive_date">
                </div>

                <div class="field field-full">
                    <label for="receive-amount">Valor da Parcela (Principal + Juros)</label>
                    <input type="text" id="receive-amount" name="receive_amount" placeholder="R$ 0,00">
                </div>

                <div class="field field-full">
                    <label class="checkbox-option" for="receive-only-interest">
                        <input type="checkbox" id="receive-only-interest" name="receive_only_interest" value="1">
                        <span>O cliente realizou o pagamento apenas dos juros acumulados</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" data-close-receive-modal>Cancelar</button>
                <button type="button" class="btn-primary" id="receive-submit-button">Confirmar Baixa</button>
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
