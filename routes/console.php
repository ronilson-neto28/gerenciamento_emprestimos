<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use MongoDB\Client as MongoClient;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
    $parcelas = $db->selectCollection('parcelas');

    $parcelas->createIndex(
        ['emprestimo_id' => 1, 'numero' => 1],
        ['unique' => true, 'name' => 'uniq_emprestimo_numero']
    );

    $this->info('Índice criado/garantido: uniq_emprestimo_numero');
})->purpose('Cria índices necessários no Mongo (ex.: unicidade de parcela por emprestimo)');
