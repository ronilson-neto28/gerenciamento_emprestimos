@extends('layouts.admin')

@section('title', 'Clientes')
@section('heading', 'Gerenciamento de Clientes')
@section('subheading', 'Consulte, cadastre e edite as informações dos clientes com praticidade.')

@push('page-status-badge')
    <div class="page-status-badge">
        <span class="page-status-dot" aria-hidden="true"></span>
        Sistema de Gestão Ativo
    </div>
@endpush

@push('topbar-actions')
    <button
        type="button"
        data-open-client-modal
        class="kbtn kbtn-primary"
    >
        <svg class="kbtn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Novo Cliente
    </button>
@endpush

@push('styles')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/admin/clientes.css'])
    @else
        <link rel="stylesheet" href="{{ route('assets.admin.clientes_css', [], false) }}">
    @endif
@endpush

@push('scripts')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/admin/cliente.js'])
    @else
        <script src="{{ route('assets.admin.cliente', [], false) }}" defer></script>
    @endif
@endpush

@section('content')
<section class="kpage kpage-clientes">
    <div class="kgrid-metrics">
        <article class="kmetric-card">
            <div class="kmetric-icon kmetric-indigo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.0001 18.5C17.2092 18.5 19.0001 16.7091 19.0001 14.5C19.0001 12.2909 17.2092 10.5 15.0001 10.5C12.791 10.5 11.0001 12.2909 11.0001 14.5C11.0001 16.7091 12.791 18.5 15.0001 18.5Z" stroke="currentColor" stroke-width="2"/><path d="M5.00012 19.5C7.20926 19.5 9.00012 17.7091 9.00012 15.5C9.00012 13.2909 7.20926 11.5 5.00012 11.5C2.79098 11.5 1.00012 13.2909 1.00012 15.5C1.00012 17.7091 2.79098 19.5 5.00012 19.5Z" stroke="currentColor" stroke-width="2"/><circle cx="5" cy="7" r="2.6" stroke="currentColor" stroke-width="2"/><circle cx="15" cy="6.5" r="2.6" stroke="currentColor" stroke-width="2"/></svg>
            </div>
            <div class="kmetric-copy">
                <span class="kmetric-label">Total Cadastrado</span>
                <strong class="kmetric-value">{{ number_format((int) ($summary['total'] ?? 0), 0, '', '.') }}</strong>
            </div>
        </article>

        <article class="kmetric-card">
            <div class="kmetric-icon kmetric-emerald" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12.5L10 17.5L19 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="kmetric-copy">
                <span class="kmetric-label">Clientes Ativos</span>
                <strong class="kmetric-value kmetric-value-emerald">{{ number_format((int) ($summary['ativos'] ?? 0), 0, '', '.') }}</strong>
            </div>
        </article>

        <article class="kmetric-card">
            <div class="kmetric-icon kmetric-amber" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7V12L15 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="kmetric-copy">
                <span class="kmetric-label">Pendentes</span>
                <strong class="kmetric-value kmetric-value-amber">{{ number_format((int) ($summary['pendentes'] ?? 0), 0, '', '.') }}</strong>
            </div>
        </article>
    </div>

    <div class="kpanel kpanel-clientes">
        <div class="ktoolbar ktoolbar-clientes">
            <div class="ksearch ksearch-clientes">
                <form method="GET" action="{{ route('admin.clientes') }}" class="ksearch-form">
                    <span class="ksearch-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"/><path d="M16.0607 16.0607L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input
                        id="busca"
                        name="busca"
                        type="search"
                        placeholder="Buscar por nome, CPF, cidade ou telefone..."
                        value="{{ e($filters['busca'] ?? '') }}"
                        autocomplete="off"
                        class="ksearch-input"
                    >
                </form>
            </div>

            <div class="kstatus-filter">
                <form method="GET" action="{{ route('admin.clientes') }}" class="kstatus-form">
                    @if (!empty($filters['busca']))
                        <input type="hidden" name="busca" value="{{ e($filters['busca']) }}">
                    @endif
                    <select id="status" name="status" class="kselect js-enhanced-select kselect-status" aria-label="Filtrar por status do cliente">
                        <option value="todos" {{ ($filters['status'] ?? 'todos') === 'todos' ? 'selected' : '' }}>Todos os Status</option>
                        <option value="ativo" {{ ($filters['status'] ?? '') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                        <option value="inativo" {{ ($filters['status'] ?? '') === 'inativo' ? 'selected' : '' }}>Inativo</option>
                        <option value="pendente" {{ ($filters['status'] ?? '') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="ktable-wrap ktable-wrap-clientes">
            <table class="ktable ktable-clientes clientes-table">
                <colgroup>
                    <col style="width: 24%;">
                    <col style="width: 14%;">
                    <col style="width: 14%;">
                    <col style="width: 12%;">
                    <col style="width: 18%;">
                    <col style="width: 9%;">
                    <col style="width: 9%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="kth-cliente">Cliente</th>
                        <th>Contato</th>
                        <th>CPF</th>
                        <th>Cidade</th>
                        <th>Pix / Banco</th>
                        <th style="text-align: center;">Status</th>
                        <th class="kth-acoes" style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $index => $client)
                        @php($statusLabel = match ($client['status']) {
                            'ativo' => 'Ativo',
                            'inativo' => 'Inativo',
                            'pendente' => 'Pendente',
                            default => ucfirst($client['status'] ?? ''),
                        })
                        @php($avatarColors = ['kavatar-indigo', 'kavatar-emerald', 'kavatar-amber', 'kavatar-purple', 'kavatar-sky', 'kavatar-rose'])
                        @php($avatarClass = $avatarColors[$index % count($avatarColors)] ?? 'kavatar-indigo')
                        @php($nome = (string) ($client['nome'] ?? ''))
                        @php($iniciais = (static function ($n) {
                            $partes = preg_split('/\s+/', trim($n));
                            if (!$partes) {
                                return '??';
                            }
                            $a = mb_strtoupper(mb_substr($partes[0], 0, 1) ?: '?');
                            $b = mb_strtoupper(mb_substr($partes[count($partes) - 1] ?? '', 0, 1) ?: '');
                            return $a . $b;
                        })($nome))
                        @php($clientIdDisplay = 'ID: #' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT))
                        @php($clientJson = [
                            'id' => (string) ($client->getKey() ?? ''),
                            'nome' => $nome,
                            'telefone' => (string) ($client['telefone'] ?? ''),
                            'cpf' => (string) ($client['cpf'] ?? ''),
                            'endereco' => (string) ($client['endereco'] ?? ''),
                            'cidade' => (string) ($client['cidade'] ?? ''),
                            'chave_pix' => (string) ($client['chave_pix'] ?? ''),
                            'banco' => (string) ($client['banco'] ?? ''),
                            'status' => (string) ($client['status'] ?? ''),
                        ])
                        <tr
                            data-client-row
                            data-client-id="{{ $clientJson['id'] }}"
                            data-client='@json($clientJson)'
                            class="ktr-cliente cliente-row"
                        >
                            <td class="ktd-cliente cliente-td-first">
                                <div class="kcell-cliente client-cell">
                                    <span class="kavatar kavatar-cliente client-avatar {{ $avatarClass }}">{{ $iniciais }}</span>
                                    <div class="kcell-cliente-meta client-cell-meta">
                                        <div class="kcliente-nome client-name">{{ $nome }}</div>
                                        <div class="kcliente-id client-id">{{ $clientIdDisplay }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="kcontact-text">{{ $client['telefone'] ? (string) $client['telefone'] : '—' }}</span>
                            </td>
                            <td>
                                <span class="kcpf-text">{{ $client['cpf'] ? (string) $client['cpf'] : '—' }}</span>
                            </td>
                            <td>
                                @if (!empty($client['cidade']))
                                    <span class="kcity-pill">{{ (string) $client['cidade'] }}</span>
                                @else
                                    <span class="kmuted">—</span>
                                @endif
                            </td>
                            <td class="ktd-banco">
                                <div class="kcell-banco">
                                    <div class="kbanco-nome">{{ !empty($client['banco']) ? (string) $client['banco'] : '—' }}</div>
                                    @if (!empty($client['chave_pix']))
                                        <div class="kpix-key">{{ (string) $client['chave_pix'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @php($statusClass = (string) ($client['status'] ?? 'ativo'))
                                <span class="kstatus-pill kstatus-pill-{{ $statusClass }}">
                                    <span class="kstatus-dot kstatus-dot-{{ $statusClass }}" aria-hidden="true"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="ktd-acoes" style="text-align: right;">
                                <div class="krow-actions">
                                    <button type="button" class="kaction-btn kaction-edit" data-edit-client title="Editar cliente" aria-label="Editar cliente">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.2322 5.23223L18.7678 8.76777C19.2441 9.24408 19.2441 10.0159 18.7678 10.4922L9.70711 19.5528C9.53654 19.7234 9.3104 19.8248 9.07181 19.8429L5.21577 20.1249C4.80096 20.1568 4.41307 19.8739 4.38116 19.4591L4.09914 15.603C4.08109 15.3644 4.18249 15.1383 4.35307 14.9677L13.4137 5.90711C13.89 5.4308 14.6618 5.4308 15.1381 5.90711L15.2322 5.23223Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 6.5L17.5 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    </button>
                                    @can('delete-clientes')
                                        <button type="button" class="kaction-btn kaction-delete" data-delete-client title="Excluir cliente" aria-label="Excluir cliente">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9 7V5.93041C9 5.41647 9.41647 5 9.93041 5H14.0696C14.5835 5 15 5.41647 15 5.93041V7" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 11V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 11V16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7.5 7.5L8.08734 18.4235C8.15732 19.6832 9.19859 20.6758 10.4613 20.6865L13.5401 20.7121C14.7941 20.7226 15.8284 19.7411 15.9069 18.4876L16.5 7.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (count($clients) === 0)
                <div class="kempty kempty-clientes">
                    <div class="kempty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M3 20C3 16.6863 5.68629 14 9 14C12.3137 14 15 16.6863 15 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                    <h3>Nenhum cliente encontrado</h3>
                    <p>Tente ajustar os filtros de busca ou cadastre um novo cliente no botão superior.</p>
                </div>
            @endif
        </div>
    </div>
</section>

<div id="client-modal" class="kmodal-overlay kmodal-glass" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="client-modal-title">
    <div class="kmodal-card kmodal-card-light">
        <header class="kmodal-header kmodal-header-light">
            <div class="kmodal-title-stack">
                <h2 id="client-modal-title">Cadastrar Novo Cliente</h2>
                <p id="client-modal-subtitle">Preencha os campos abaixo para registrar um novo cliente.</p>
            </div>
            <button type="button" class="kmodal-close" data-close-client-modal aria-label="Fechar modal">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 7L17 17M17 7L7 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </header>

        <form id="client-form" method="POST" novalidate>
            @csrf

            <div class="kform-grid kform-grid-cliente">
                <div class="kfield kfield-full">
                    <label for="nome">Nome Completo</label>
                    <input
                        id="nome"
                        name="nome"
                        type="text"
                        placeholder="Ex: João Silva Sauro"
                        autocomplete="name"
                        maxlength="180"
                        class="kinput"
                    >
                </div>

                <div class="kfield">
                    <label for="telefone">Telefone Corporativo</label>
                    <input
                        id="telefone"
                        name="telefone"
                        type="tel"
                        placeholder="(99) 99999-9999"
                        autocomplete="tel-national"
                        maxlength="20"
                        class="kinput"
                    >
                </div>

                <div class="kfield">
                    <label for="cpf">CPF</label>
                    <input
                        id="cpf"
                        name="cpf"
                        type="text"
                        placeholder="999.999.999-99"
                        autocomplete="off"
                        maxlength="18"
                        class="kinput"
                    >
                </div>

                <div class="kfield kfield-full">
                    <label for="endereco">Endereço de Faturamento</label>
                    <input
                        id="endereco"
                        name="endereco"
                        type="text"
                        placeholder="Av. Tapajós, 120"
                        autocomplete="street-address"
                        maxlength="220"
                        class="kinput"
                    >
                </div>

                <div class="kfield">
                    <label for="cidade">Cidade</label>
                    <input
                        id="cidade"
                        name="cidade"
                        type="text"
                        placeholder="Ananindeua"
                        autocomplete="address-level2"
                        maxlength="120"
                        class="kinput"
                    >
                </div>

                <div class="kfield">
                    <label for="chave_pix">Chave Pix Principal</label>
                    <input
                        id="chave_pix"
                        name="chave_pix"
                        type="text"
                        placeholder="chave@pix.com.br"
                        autocomplete="off"
                        maxlength="140"
                        class="kinput"
                    >
                </div>

                <div class="kfield kfield-full">
                    <label for="banco">Instituição Bancária</label>
                    <input
                        id="banco"
                        name="banco"
                        type="text"
                        placeholder="Nubank"
                        autocomplete="organization"
                        maxlength="120"
                        class="kinput"
                    >
                </div>
            </div>

            <div class="kform-actions kform-actions-cliente">
                <button type="button" class="kbtn kbtn-secondary" data-close-client-modal>
                    Cancelar
                </button>
                <button type="submit" id="client-submit-button" class="kbtn kbtn-primary kbtn-large">
                    Salvar Cadastro
                </button>
            </div>
        </form>
    </div>
</div>

<div id="client-delete-modal" class="kmodal-overlay kmodal-glass kmodal-danger" aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="client-delete-title" aria-describedby="client-delete-desc">
    <div class="kmodal-card kmodal-card-delete">
        <div class="kdelete-icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M12 17.5H12.01" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/><path d="M10.2945 4.5L2.82551 17.5C2.39001 18.2595 2.93922 19.25 3.83101 19.25H20.169C21.0608 19.25 21.61 18.2595 21.1745 17.5L13.7055 4.5C13.2888 3.775 12.3878 3.775 11.9711 4.5L10.2945 4.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/></svg>
        </div>

        <h2 id="client-delete-title" class="kdelete-title">Confirmar Exclusão</h2>
        <p id="client-delete-desc" class="kdelete-desc">
            Tem certeza que deseja remover <strong class="kdelete-client-name">este cliente</strong>?<br>
            Esta ação não pode ser desfeita.
        </p>

        <div class="kform-actions kdelete-actions">
            <button type="button" class="kbtn kbtn-secondary" data-close-delete-modal>
                Cancelar
            </button>
            <button type="button" id="confirm-delete-client" class="kbtn kbtn-danger">
                Sim, Excluir
            </button>
        </div>
    </div>
</div>
@endsection