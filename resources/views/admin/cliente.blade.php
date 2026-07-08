@extends('layouts.admin')

@section('title', 'Clientes')
@section('heading', 'Gerenciamento de Clientes')
@section('subheading', 'Visualize, filtre e gerencie os dados cadastrais da sua base de clientes ativos.')

@section('content')
    <section class="card">
        <div class="card-header-zone">
            <div>
                <h1 class="main-title">Gerenciamento de Clientes</h1>
                <p class="main-subtitle">Visualize, filtre e gerencie os dados cadastrais da sua base de clientes ativos.</p>
            </div>
            <button type="button" class="btn-primary" data-open-client-modal>
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Novo Cliente
            </button>
        </div>

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.clientes') }}" class="filter-form">
                <div class="field search-field">
                    <label for="busca">Buscar cliente</label>
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
                            placeholder="Nome, telefone, CPF, cidade..."
                        >
                    </div>
                </div>

                <div class="field status-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="js-enhanced-select">
                        <option value="todos" @selected($filters['status'] === 'todos')>Todos os status</option>
                        <option value="ativo" @selected($filters['status'] === 'ativo')>Ativos</option>
                        <option value="inativo" @selected($filters['status'] === 'inativo')>Inativos</option>
                        <option value="pendente" @selected($filters['status'] === 'pendente')>Pendentes</option>
                    </select>
                </div>

                <div class="field action-field">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-secondary">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <div class="table-meta">
            <span class="meta-pill id-total">
                <span class="dot dot-blue"></span> Total: <strong id="clients-total">{{ $summary['total'] }}</strong>
            </span>
            <span class="meta-pill id-filtered">
                <span class="dot dot-gray"></span> Filtrados: <strong id="clients-filtered">{{ $summary['filtrados'] }}</strong>
            </span>
            <span class="meta-pill id-active">
                <span class="dot dot-green"></span> Ativos: <strong>{{ $summary['ativos'] }}</strong>
            </span>
        </div>

        @if ($clients->isEmpty())
            <div class="empty-state">
                Nenhum cliente encontrado para os filtros informados.
            </div>
        @else
            <div class="table-wrap">
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th>Nome do Cliente</th>
                            <th>Telefone</th>
                            <th>CPF</th>
                            <th>Cidade</th>
                            <th>Chave Pix</th>
                            <th>Banco</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            @php($statusLabel = match ($client['status']) {
                                'ativo' => 'Ativo',
                                'inativo' => 'Inativo',
                                'pendente' => 'Pendente',
                                default => ucfirst($client['status'] ?? ''),
                            })
                            <tr
                                data-client-row
                                data-client-id="{{ (string) $client->getKey() }}"
                                data-nome="{{ $client['nome'] }}"
                                data-telefone="{{ $client['telefone'] }}"
                                data-cpf="{{ $client['cpf'] }}"
                                data-endereco="{{ $client['endereco'] }}"
                                data-cidade="{{ $client['cidade'] }}"
                                data-chave-pix="{{ $client['chave_pix'] }}"
                                data-banco="{{ $client['banco'] }}"
                                data-status="{{ $client['status'] }}"
                            >
                                <td class="font-medium">{{ $client['nome'] }}</td>
                                <td class="text-muted">{{ $client['telefone'] }}</td>
                                <td>{{ $client['cpf'] }}</td>
                                <td>{{ $client['cidade'] }}</td>
                                <td class="text-mono">{{ $client['chave_pix'] }}</td>
                                <td>{{ $client['banco'] }}</td>
                                <td>
                                    <span class="status-badge status-{{ $client['status'] }}">
                                        <span class="status-dot" aria-hidden="true"></span> {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="icon-btn edit-btn" data-edit-client title="Editar cliente" aria-label="Editar cliente">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        @can('delete-clientes')
                                            <button type="button" class="icon-btn icon-btn-danger" data-delete-client title="Excluir cliente" aria-label="Excluir cliente">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="modal-overlay" id="client-modal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2 id="client-modal-title">Cadastrar Novo Cliente</h2>
                    <div class="card-subtitle" id="client-modal-subtitle">Insira as informações principais para registrar o cliente no sistema.</div>
                </div>

                <button type="button" class="modal-close" data-close-client-modal aria-label="Fechar modal">&times;</button>
            </div>

            <form id="client-form">
                <div class="form-grid">
                    <div class="field field-full">
                        <label for="nome">Nome completo</label>
                        <input type="text" id="nome" name="nome" placeholder="Ex: João Silva Sauro">
                    </div>

                    <div class="field">
                        <label for="telefone">Telefone corporativo</label>
                        <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                    </div>

                    <div class="field">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00">
                    </div>

                    <div class="field field-full">
                        <label for="endereco">Endereço de Faturamento</label>
                        <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro...">
                    </div>

                    <div class="field">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" placeholder="Digite a cidade">
                    </div>

                    <div class="field">
                        <label for="chave_pix">Chave Pix principal</label>
                        <input type="text" id="chave_pix" name="chave_pix" placeholder="E-mail, CPF, celular...">
                    </div>

                    <div class="field field-full">
                        <label for="banco">Instituição Bancária</label>
                        <input type="text" id="banco" name="banco" placeholder="Ex: Banco do Brasil S.A.">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" data-close-client-modal>Cancelar</button>
                    <button type="submit" class="btn-primary" id="client-submit-button">Salvar Cadastro</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/js/admin/cliente.js'])
        @else
            <script src="{{ route('assets.admin.cliente', [], false) }}" defer></script>
        @endif
    @endpush

    @push('styles')
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/admin/clientes.css'])
        @else
            <link rel="stylesheet" href="{{ route('assets.admin.clientes_css', [], false) }}">
        @endif
    @endpush
@endsection
