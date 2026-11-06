<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['en', 'km'];
        $locale = $request->session()->get('locale', $request->cookie('locale', config('app.locale', 'en')));

        if (in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
        } else {
            App::setLocale('en');
        }

        // Currency setting
        $supportedCurrencies = ['USD', 'KHR'];
        $currency = $request->session()->get('currency', $request->cookie('currency', config('bakong.currency', 'KHR')));
        if (! in_array($currency, $supportedCurrencies, true)) {
            $currency = 'KHR';
        }
        $request->session()->put('currency', $currency);

        return $next($request);
    }
}
