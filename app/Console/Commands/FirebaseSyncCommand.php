<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FirebaseFirestoreService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class FirebaseSyncCommand extends Command
{
    protected $signature = 'app:firebase-sync
        {--collection=all : Collection to sync (all, categories, products, discounts, settings, reviews, orders, payments)}
        {--only= : Optional comma-separated alias for --collection}
        {--dry-run : Build and validate Firestore payloads without sending outbound HTTP requests}';

    protected $description = 'Synchronize non-PII SQLite storefront collections to Firebase Cloud Firestore';

    public function handle(FirebaseFirestoreService $firestore): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyOption = trim((string) ($this->option('only') ?? ''));
        $collection = $onlyOption !== '' ? $onlyOption : (string) $this->option('collection');

        if (! $dryRun && ! $firestore->isEnabled()) {
            $this->warn('FIREBASE_ENABLED is false. No outbound Firestore requests were sent (pass --dry-run to validate payloads).');

            return self::SUCCESS;
        }

        try {
            $summary = $firestore->syncAll($collection, $dryRun);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        $hasFailures = false;
        foreach ($summary as $name => $stats) {
            if ($stats['failed'] > 0) {
                $hasFailures = true;
            }
            $rows[] = [
                $name,
                $stats['total'],
                $stats['synced'],
                $stats['deleted'],
                $stats['skipped'],
                $stats['failed'],
                $stats['status'],
            ];
        }

        $this->table(['Collection', 'Total', 'Synced', 'Deleted', 'Skipped', 'Failed', 'Status'], $rows);

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }
}
