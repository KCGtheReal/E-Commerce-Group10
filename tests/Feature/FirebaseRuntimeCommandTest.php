<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebaseRuntimeCommandTest extends TestCase
{
    public function test_it_skips_cleanly_when_firebase_is_disabled(): void
    {
        config()->set('firebase.enabled', false);

        $this->artisan('app:firebase-runtime', [
            '--lan-api' => 'http://10.30.0.158:8000',
            '--lan-gateway' => 'http://10.30.0.158:4000',
        ])->assertExitCode(0);
    }

    public function test_it_publishes_sanitized_runtime_endpoints_to_firestore(): void
    {
        $tempConfig = null;
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($resource === false) {
            $tempConfig = tempnam(sys_get_temp_dir(), 'reset_openssl_');
            if ($tempConfig !== false) {
                file_put_contents($tempConfig, "[req]\ndistinguished_name=req_dn\n[req_dn]\n");
                $resource = openssl_pkey_new([
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                    'config' => $tempConfig,
                ]);
            }
        }
        $this->assertNotFalse($resource);
        $privateKeyPem = '';
        $exportOptions = is_string($tempConfig) ? ['config' => $tempConfig] : [];
        openssl_pkey_export($resource, $privateKeyPem, null, $exportOptions);
        if (is_string($tempConfig)) {
            @unlink($tempConfig);
        }

        config()->set('firebase.enabled', true);
        config()->set('firebase.project_id', 'reset-store-ecommerce');
        config()->set('firebase.database_id', '(default)');
        config()->set('firebase.credentials_json', json_encode([
            'project_id' => 'reset-store-ecommerce',
            'client_email' => 'firebase-test@reset-store-ecommerce.iam.gserviceaccount.com',
            'private_key' => $privateKeyPem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));

        $capturedPayload = null;

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ], 200),
            'https://firestore.googleapis.com/*' => function (Request $request) use (&$capturedPayload) {
                $capturedPayload = $request->data();

                return Http::response(['name' => 'projects/reset-store-ecommerce/databases/(default)/documents/settings/runtime_endpoints'], 200);
            },
        ]);

        $this->artisan('app:firebase-runtime', [
            '--lan-api' => 'http://10.30.0.158:8000/',
            '--lan-gateway' => 'http://10.30.0.158:4000/',
            '--tunnel' => 'https://reset-demo.trycloudflare.com/',
        ])->assertExitCode(0);

        $this->assertIsArray($capturedPayload);
        $fields = $capturedPayload['fields'] ?? [];
        $this->assertSame('runtime_endpoints', $fields['key']['stringValue'] ?? null);
        $valueFields = $fields['value']['mapValue']['fields'] ?? [];
        $this->assertSame('http://10.30.0.158:8000', $valueFields['lan_api_url']['stringValue'] ?? null);
        $this->assertSame('http://10.30.0.158:4000', $valueFields['lan_gateway_url']['stringValue'] ?? null);
        $this->assertSame('https://reset-demo.trycloudflare.com', $valueFields['tunnel_url']['stringValue'] ?? null);
    }

    public function test_it_rejects_missing_or_invalid_urls_when_enabled(): void
    {
        config()->set('firebase.enabled', true);

        $this->artisan('app:firebase-runtime', [
            '--lan-api' => 'ftp://invalid-scheme',
        ])->assertExitCode(1);
    }
}
