@extends('layouts.admin')

@section('title', 'Cobradores')
@section('heading', 'Cadastro de Cobradores')
@section('subheading', 'Crie acessos de cobrança vinculados ao administrador da empresa.')

@section('content')
    <section class="user-access-layout">
        <article class="card user-access-card">
            <div class="user-access-card-inner">
                <div class="user-access-header">
                    <span class="user-access-kicker">Novo acesso</span>
                    <h2 class="user-access-title">Cadastrar cobrador</h2>
                    <p class="user-access-subtitle">Crie acessos de cobrança com o mesmo padrão visual do login e vincule tudo ao seu tenant.</p>
                </div>

                <form method="POST" action="{{ route('admin.cobradores.store') }}" class="user-access-form">
                    @csrf

                    <div class="field">
                        <label for="name">Nome completo</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Digite o nome completo" required autocomplete="name">
                    </div>

                    <div class="field">
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="usuario@teste.com" required autocomplete="username">
                    </div>

                    <div class="field">
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="(00) 00000-0000" autocomplete="tel">
                    </div>

                    <div class="field">
                        <label for="password">Senha inicial</label>
                        <input id="password" name="password" type="password" placeholder="Crie uma senha" required autocomplete="new-password">
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmar senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repita a senha" required autocomplete="new-password">
                    </div>

                    <div class="user-access-actions">
                        <button type="submit" class="btn-primary user-access-submit">Cadastrar cobrador</button>
                    </div>
                </form>
            </div>
        </article>

        <article class="card">
            <div class="card-header">
                <div>
                    <h2>Cobradores cadastrados</h2>
                    <div class="card-subtitle">Relação atual dos usuários de cobrança vinculados à sua empresa.</div>
                </div>
                <span class="badge">{{ $cobradores->count() }} registros</span>
            </div>

            @if ($cobradores->isEmpty())
                <div class="empty-state">Nenhum cobrador cadastrado até o momento.</div>
            @else
                <div class="table-wrap">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cobradores as $cobrador)
                                <tr>
                                    <td>{{ $cobrador['name'] }}</td>
                                    <td>{{ $cobrador['email'] }}</td>
                                    <td>{{ $cobrador['phone'] ?? '-' }}</td>
                                    <td>{{ optional($cobrador->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
@endsection
