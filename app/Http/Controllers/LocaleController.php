<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'km'], true)) {
            session(['locale' => $locale]);
            cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));
        }

        return redirect()->back();
    }

    public function setCurrency(Request $request, string $currency): RedirectResponse
    {
        $currency = strtoupper($currency);
        if (in_array($currency, ['USD', 'KHR'], true)) {
            session(['currency' => $currency]);
            cookie()->queue(cookie('currency', $currency, 60 * 24 * 365));
        }

        return redirect()->back();
    }
}
