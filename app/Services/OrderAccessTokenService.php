<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderAccessTokenService
{
    public function issue(Order $order, int $ttlDays = 30): string
    {
        $payload = [
            'n' => $order->number,
            'i' => (int) $order->id,
            'e' => $this->emailDigest((string) $order->email),
            'exp' => now()->addDays($ttlDays)->timestamp,
        ];

        $encoded = $this->base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', $encoded, $this->signingKey(), true)
        );

        return "{$encoded}.{$signature}";
    }

    public function verify(?string $token, Order $order): bool
    {
        $token = trim((string) $token);
        if ($token === '' || ! str_contains($token, '.')) {
            return false;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return false;
        }

        [$encodedPayload, $providedSig] = $parts;
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', $encodedPayload, $this->signingKey(), true)
        );

        if (! hash_equals($expectedSig, $providedSig)) {
            return false;
        }

        $decodedJson = $this->base64UrlDecode($encodedPayload);
        if ($decodedJson === false) {
            return false;
        }

        $payload = json_decode($decodedJson, true);
        if (! is_array($payload)) {
            return false;
        }

        if (($payload['n'] ?? null) !== $order->number) {
            return false;
        }

        if ((int) ($payload['i'] ?? 0) !== (int) $order->id) {
            return false;
        }

        $expectedEmailDigest = $this->emailDigest((string) $order->email);
        if (! hash_equals($expectedEmailDigest, (string) ($payload['e'] ?? ''))) {
            return false;
        }

        return (int) ($payload['exp'] ?? 0) >= now()->timestamp;
    }

    public function canAccessOrder(Request $request, Order $order): bool
    {
        $user = $request->user();
        if ($user && ($user->isAdmin() || (int) $user->id === (int) $order->user_id)) {
            return true;
        }

        $headerOrQueryToken = $request->header('X-Order-Token') ?? $request->query('order_token');

        return $this->verify(is_string($headerOrQueryToken) ? $headerOrQueryToken : null, $order);
    }

    private function emailDigest(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key', 'reset-store-fallback-key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $padded = strtr($data, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode($padded, true);
    }
}
