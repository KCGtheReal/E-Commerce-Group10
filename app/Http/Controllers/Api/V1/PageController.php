<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Services\StoreContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(
        private readonly StoreContentService $storeContentService
    ) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $page = $this->storeContentService->page($slug, $locale);

        if ($page === null) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json($page);
    }

    public function contact(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $contact = ContactMessage::create($validated);

        return response()->json([
            'message' => 'Thank you for reaching out. Our team will get back to you within 24 hours.',
            'contact_message_id' => (int) $contact->id,
        ], 201);
    }

    public function newsletter(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['is_active' => true]
        );

        return response()->json([
            'message' => __('store.newsletter_success'),
            'email' => (string) $subscriber->email,
            'is_active' => (bool) $subscriber->is_active,
        ]);
    }
}
