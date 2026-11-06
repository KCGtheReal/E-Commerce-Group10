<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function shipping(): View
    {
        return view('pages.shipping');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function sizeGuide(): View
    {
        return view('pages.size-guide');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function contactSubmit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        ContactMessage::create($validated);

        return redirect()->back()->with('success', 'Thank you for reaching out. Our team will get back to you within 24 hours.');
    }

    public function newsletterSubmit(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));

        NewsletterSubscriber::firstOrCreate(
            ['email' => $email],
            ['is_active' => true]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('store.newsletter_success'),
            ]);
        }

        return redirect()->back()->with('success', __('store.newsletter_success'));
    }

    public function sitemap(): Response
    {
        $products = Product::where('is_published', true)->get();

        $content = view('pages.sitemap', compact('products'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
