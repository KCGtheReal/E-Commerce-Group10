<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FirebaseFirestoreService;
use Illuminate\Console\Command;

class PublishFirebaseRuntimeCommand extends Command
{
    protected $signature = 'app:firebase-runtime
        {--lan-api= : LAN base URL for Laravel backend (e.g. http://10.30.0.158:8000)}
        {--lan-gateway= : LAN base URL for Firebase Gateway API (e.g. http://10.30.0.158:4000)}
        {--tunnel= : Public HTTPS Cloudflare/tunnel URL for Laravel backend}
        {--localtunnel=https://reset-store-cambodia.loca.lt : Fallback fixed localtunnel URL}';

    protected $description = 'Publish active local LAN & tunnel presentation URLs to Cloud Firestore settings/runtime_endpoints';

    public function handle(FirebaseFirestoreService $firestore): int
    {
        if (! $firestore->isEnabled()) {
            $this->warn('FIREBASE_ENABLED is false. Runtime endpoint broadcast skipped.');

            return self::SUCCESS;
        }

        $lanApi = $this->normalizeHttpUrl((string) ($this->option('lan-api') ?? ''));
        $lanGateway = $this->normalizeHttpUrl((string) ($this->option('lan-gateway') ?? ''));
        $tunnel = $this->normalizeHttpUrl((string) ($this->option('tunnel') ?? ''));
        $localtunnel = $this->normalizeHttpUrl((string) ($this->option('localtunnel') ?? ''));

        if ($lanApi === null && $lanGateway === null && $tunnel === null) {
            $this->error('Provide at least one valid --lan-api, --lan-gateway, or --tunnel HTTP(S) URL.');

            return self::FAILURE;
        }

        $payload = [
            'key' => 'runtime_endpoints',
            'group' => 'runtime',
            'value' => [
                'lan_api_url' => $lanApi,
                'lan_gateway_url' => $lanGateway,
                'tunnel_url' => $tunnel,
                'localtunnel_url' => $localtunnel,
                'updated_at' => now(),
            ],
            'updated_at' => now(),
        ];

        $ok = $firestore->upsertDocument('settings', 'runtime_endpoints', $payload);

        if (! $ok) {
            $this->warn('Failed to publish runtime_endpoints to Cloud Firestore (check credentials/network).');

            return self::FAILURE;
        }

        $this->info('Published runtime_endpoints to Cloud Firestore.');
        $this->line(json_encode([
            'lan_api_url' => $lanApi,
            'lan_gateway_url' => $lanGateway,
            'tunnel_url' => $tunnel,
            'localtunnel_url' => $localtunnel,
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        return self::SUCCESS;
    }

    private function normalizeHttpUrl(string $raw): ?string
    {
        $trimmed = rtrim(trim($raw), '/');
        if ($trimmed === '') {
            return null;
        }

        $validated = filter_var($trimmed, FILTER_VALIDATE_URL);
        if (! is_string($validated)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($validated, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $validated;
    }
}
