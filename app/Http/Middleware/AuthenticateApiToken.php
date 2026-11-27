<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }
}
