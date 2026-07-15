<?php

use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\LancamentoFinanceiro;
use App\Models\Parcela;
use App\Models\Recebimento;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use MongoDB\Client as MongoClient;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-admin', function () {
    $user = User::withoutGlobalScopes()->updateOrCreate(
        ['email' => 'admin@teste.com'],
        [
            'name' => 'Administrador',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'ativo',
            'phone' => null,
            'created_by' => null,
            'email_verified_at' => now(),
            'two_factor_channel' => 'email',
        ]
    );

    $user->forceFill([
        'owner_id' => (string) ($user->id ?? $user->getKey() ?? ''),
    ])->save();

    $this->info('Usuário administrador pronto: ' . $user->email);
    $this->line('Role: ' . (string) $user->role);
})->purpose('Cria ou atualiza o usuário administrador inicial');

Artisan::command('app:backfill-owner-ids', function () {
    $admins = User::withoutGlobalScopes()
        ->where('role', 'admin')
        ->get()
        ->keyBy(fn (User $user) => (string) ($user->id ?? $user->getKey() ?? ''));

    $singleAdminId = $admins->count() === 1 ? (string) $admins->keys()->first() : '';

    User::withoutGlobalScopes()->get()->each(function (User $user) use ($admins, $singleAdminId) {
        $userId = (string) ($user->id ?? $user->getKey() ?? '');
        $ownerId = trim((string) ($user->owner_id ?? ''));

        if ($ownerId === '') {
            if ($user->isAdmin()) {
                $ownerId = $userId;
            } else {
                $creatorId = trim((string) ($user->created_by ?? ''));
                $creator = $creatorId !== '' ? $admins->get($creatorId) : null;
                $ownerId = trim((string) ($creator?->ownerId() ?? $creatorId ?: $singleAdminId));
            }
        }

        $status = trim((string) ($user->status ?? ''));
        if ($status === '') {
            $status = 'ativo';
        }

        $user->forceFill([
            'owner_id' => $ownerId,
            'status' => $status,
        ])->save();
    });

    $usersById = User::withoutGlobalScopes()
        ->get()
        ->keyBy(fn (User $user) => (string) ($user->id ?? $user->getKey() ?? ''));

    Cliente::withoutGlobalScopes()->get()->each(function (Cliente $cliente) use ($usersById, $singleAdminId) {
        $ownerId = trim((string) ($cliente->owner_id ?? ''));
        if ($ownerId === '') {
            $creatorId = trim((string) ($cliente->created_by ?? ''));
            $ownerId = trim((string) ($usersById->get($creatorId)?->ownerId() ?? $singleAdminId));
        }

        if ($ownerId !== '') {
            $cliente->forceFill(['owner_id' => $ownerId])->save();
        }
    });

    $clientesById = Cliente::withoutGlobalScopes()
        ->get()
        ->keyBy(fn (Cliente $cliente) => (string) ($cliente->id ?? $cliente->getKey() ?? ''));

    Emprestimo::withoutGlobalScopes()->get()->each(function (Emprestimo $loan) use ($usersById, $clientesById, $singleAdminId) {
        $ownerId = trim((string) ($loan->owner_id ?? ''));

        if ($ownerId === '') {
            $creatorId = trim((string) ($loan->created_by ?? ''));
            $clienteId = trim((string) ($loan->cliente_id ?? ''));
            $cobradorId = trim((string) ($loan->cobrador_user_id ?? ''));

            $ownerId = trim((string) (
                $usersById->get($creatorId)?->ownerId()
                ?? $usersById->get($cobradorId)?->ownerId()
                ?? $clientesById->get($clienteId)?->owner_id
                ?? $singleAdminId
            ));
        }

        if ($ownerId !== '') {
            $loan->forceFill(['owner_id' => $ownerId])->save();
        }
    });

    $loansById = Emprestimo::withoutGlobalScopes()
        ->get()
        ->keyBy(fn (Emprestimo $loan) => (string) ($loan->id ?? $loan->getKey() ?? ''));

    Parcela::withoutGlobalScopes()->get()->each(function (Parcela $parcela) use ($loansById, $singleAdminId) {
        $ownerId = trim((string) ($parcela->owner_id ?? ''));
        if ($ownerId === '') {
            $loanId = trim((string) ($parcela->emprestimo_id ?? ''));
            $ownerId = trim((string) ($loansById->get($loanId)?->owner_id ?? $singleAdminId));
        }

        if ($ownerId !== '') {
            $parcela->forceFill(['owner_id' => $ownerId])->save();
        }
    });

    Recebimento::withoutGlobalScopes()->get()->each(function (Recebimento $recebimento) use ($loansById, $usersById, $singleAdminId) {
        $ownerId = trim((string) ($recebimento->owner_id ?? ''));
        if ($ownerId === '') {
            $loanId = trim((string) ($recebimento->emprestimo_id ?? ''));
            $userId = trim((string) ($recebimento->user_id ?? ''));
            $ownerId = trim((string) ($loansById->get($loanId)?->owner_id ?? $usersById->get($userId)?->ownerId() ?? $singleAdminId));
        }

        if ($ownerId !== '') {
            $recebimento->forceFill(['owner_id' => $ownerId])->save();
        }
    });

    LancamentoFinanceiro::withoutGlobalScopes()->get()->each(function (LancamentoFinanceiro $lancamento) use ($singleAdminId) {
        if (trim((string) ($lancamento->owner_id ?? '')) === '' && $singleAdminId !== '') {
            $lancamento->forceFill(['owner_id' => $singleAdminId])->save();
        }
    });

    $this->info('Backfill de owner_id e status concluído.');
})->purpose('Preenche owner_id e status em registros legados para o modo multi-tenant');

Artisan::command('mongo:cleanup-parcelas {--force}', function () {
    $dsn = (string) config('database.connections.mongodb.dsn');
    $dbName = (string) config('database.connections.mongodb.database');

    $client = new MongoClient($dsn);
    $db = $client->selectDatabase($dbName);
    $parcelas = $db->selectCollection('parcelas');

    $normalized = $parcelas->updateMany(
        ['emprestimo_id' => ['$type' => 'objectId']],
        [['$set' => ['emprestimo_id' => ['$toString' => '$emprestimo_id']]]]
    );

    $this->info('Normalizados (emprestimo_id ObjectId -> string): ' . (int) $normalized->getModifiedCount());

    $orphanFilter = [
        '$or' => [
            ['emprestimo_id' => ''],
            ['emprestimo_id' => null],
            ['emprestimo_id' => ['$exists' => false]],
        ],
    ];

    $orphans = (int) $parcelas->countDocuments($orphanFilter);
    $this->info('Parcelas órfãs (emprestimo_id vazio/nulo/ausente): ' . $orphans);

    if ($orphans > 0 && $this->option('force')) {
        $deleted = $parcelas->deleteMany($orphanFilter);
        $this->info('Órfãs removidas: ' . (int) $deleted->getDeletedCount());
    } elseif ($orphans > 0) {
        $this->warn('Use --force para remover as órfãs.');
    }

    $duplicatesCursor = $parcelas->aggregate([
        ['$match' => ['emprestimo_id' => ['$type' => 'string', '$ne' => '']]],
        ['$group' => [
            '_id' => ['emprestimo_id' => '$emprestimo_id', 'numero' => '$numero'],
            'docs' => ['$push' => ['id' => '$_id', 'status' => '$status']],
            'count' => ['$sum' => 1],
        ]],
        ['$match' => ['count' => ['$gt' => 1]]],
        ['$limit' => 500],
    ]);

    $toDelete = [];
    $totalGroups = 0;
    foreach ($duplicatesCursor as $group) {
        $totalGroups++;
        $docs = $group['docs'] ?? [];

        $keepId = null;
        foreach ($docs as $doc) {
            if (($doc['status'] ?? '') === 'pago') {
                $keepId = $doc['id'] ?? null;
                break;
            }
        }
        if (!$keepId) {
            $keepId = $docs[0]['id'] ?? null;
        }

        foreach ($docs as $doc) {
            $id = $doc['id'] ?? null;
            if (!$id) {
                continue;
            }
            if ((string) $id === (string) $keepId) {
                continue;
            }
            $toDelete[] = $id;
        }
    }

    $this->info('Grupos duplicados (emprestimo_id+numero): ' . $totalGroups);
    $this->info('Docs duplicados a remover: ' . count($toDelete));

    if ($toDelete && $this->option('force')) {
        $deleted = $parcelas->deleteMany(['_id' => ['$in' => $toDelete]]);
        $this->info('Duplicadas removidas: ' . (int) $deleted->getDeletedCount());
    } elseif ($toDelete) {
        $this->warn('Use --force para remover as duplicadas.');
    }
})->purpose('Normaliza emprestimo_id, detecta e (opcionalmente) remove parcelas órfãs/duplicadas');

Artisan::command('mongo:ensure-indexes', function () {
    $dsn = (string) config('database.connections.mongodb.dsn');
    $dbName = (string) config('database.connections.mongodb.database');

    $client = new MongoClient($dsn);
    $db = $client->selectDatabase($dbName);
    $users = $db->selectCollection('users');
    $clientes = $db->selectCollection('clientes');
    $emprestimos = $db->selectCollection('emprestimos');
    $parcelas = $db->selectCollection('parcelas');
    $recebimentos = $db->selectCollection('recebimentos');
    $lancamentos = $db->selectCollection('lancamentos_financeiro');

    $users->createIndex(['email' => 1], ['unique' => true, 'name' => 'uniq_users_email']);
    $users->createIndex(['owner_id' => 1, 'role' => 1], ['name' => 'idx_users_owner_role']);
    $clientes->createIndex(['owner_id' => 1], ['name' => 'idx_clientes_owner']);
    $emprestimos->createIndex(['owner_id' => 1], ['name' => 'idx_emprestimos_owner']);
    $parcelas->createIndex(['owner_id' => 1], ['name' => 'idx_parcelas_owner']);
    $recebimentos->createIndex(['owner_id' => 1, 'user_id' => 1], ['name' => 'idx_recebimentos_owner_user']);
    $lancamentos->createIndex(['owner_id' => 1, 'date' => 1], ['name' => 'idx_lancamentos_owner_date']);

    $parcelas->createIndex(
        ['emprestimo_id' => 1, 'numero' => 1],
        ['unique' => true, 'name' => 'uniq_emprestimo_numero']
    );

    $this->info('Índices multi-tenant e de parcelas garantidos com sucesso.');
})->purpose('Cria índices necessários no Mongo (ex.: unicidade de parcela por emprestimo)');
