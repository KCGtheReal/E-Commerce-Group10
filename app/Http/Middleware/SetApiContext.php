<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = (string) config('app.locale', 'en');
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);
        $request->attributes->set('api_locale', $locale);

        $currency = $this->resolveCurrency($request);
        $request->attributes->set('api_currency', $currency);

        $bearer = $request->bearerToken();
        if (is_string($bearer) && trim($bearer) !== '') {
            $apiToken = ApiToken::findActiveByPlainText($bearer);

            if ($apiToken && $apiToken->user) {
                $user = $apiToken->user;
                $request->attributes->set('api_token', $apiToken);
                $request->setUserResolver(static fn () => $user);

                if ($apiToken->last_used_at === null || $apiToken->last_used_at->lt(now()->subMinutes(5))) {
                    $apiToken->forceFill(['last_used_at' => now()])->saveQuietly();
                }
            } else {
                $request->setUserResolver(static fn () => null);
            }
        }

        try {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        } finally {
            App::setLocale($previousLocale);
        }
    }

    private function resolveLocale(Request $request): string
    {
        $candidates = [
            $request->header('X-Locale'),
            $request->query('lang'),
            $request->header('Accept-Language'),
            config('app.fallback_locale', 'en'),
        ];

        foreach ($candidates as $raw) {
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }

            $normalized = strtolower(trim($raw));
            $primary = strtolower(substr(explode(',', $normalized)[0], 0, 2));
            if (in_array($primary, ['en', 'km'], true)) {
                return $primary;
            }
        }

        return 'en';
    }

    private function resolveCurrency(Request $request): string
    {
        $candidates = [
            $request->header('X-Currency'),
            $request->query('currency'),
            config('bakong.currency', 'KHR'),
        ];

        foreach ($candidates as $raw) {
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }

            $normalized = strtoupper(trim($raw));
            if (in_array($normalized, ['KHR', 'USD'], true)) {
                return $normalized;
            }
        }

        return 'KHR';
    }
}
