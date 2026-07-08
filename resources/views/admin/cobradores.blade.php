@extends('layouts.admin')

@section('title', 'Cobradores')
@section('heading', 'Cadastro de Cobradores')
@section('subheading', 'Crie acessos individuais para a equipe de cobrança.')

@section('content')
    <section class="grid" style="grid-template-columns: minmax(320px, 420px) 1fr; align-items: start;">
        <article class="card">
            <div class="card-header">
                <div>
                    <h2>Novo cobrador</h2>
                    <div class="card-subtitle">Defina o acesso inicial e vincule o cadastro ao admin logado.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.cobradores.store') }}">
                @csrf

                <div class="field">
                    <label for="name">Nome</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="field">
                    <label for="phone">Telefone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                </div>

                <div class="field">
                    <label for="password">Senha inicial</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirmar senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>

                <button type="submit" class="btn-primary">Cadastrar cobrador</button>
            </form>
        </article>

        <article class="card">
            <div class="card-header">
                <div>
                    <h2>Cobradores cadastrados</h2>
                    <div class="card-subtitle">Relação atual dos usuários com papel de cobrança.</div>
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
