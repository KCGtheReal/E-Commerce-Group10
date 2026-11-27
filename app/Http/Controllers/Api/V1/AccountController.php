<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AddressResource;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\OrderSummaryResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Address;
use App\Services\CartService;
use App\Services\OrderAccessTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderAccessTokenService $orderAccessTokenService
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('defaultShippingAddress');

        return response()->json([
            'user' => (new UserResource($user))->resolve($request),
            'default_shipping_address' => $user->defaultShippingAddress
                ? (new AddressResource($user->defaultShippingAddress))->resolve($request)
                : null,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => trim((string) $validated['name']),
            'phone' => isset($validated['phone']) ? trim((string) $validated['phone']) : null,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => (new UserResource($user->fresh()))->resolve($request),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->input('per_page', 10)));
        $paginator = $request->user()
            ->orders()
            ->with(['items.variant.product', 'latestPayment.order'])
            ->withCount('items')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => OrderSummaryResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function orderDetail(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->where('number', $orderNumber)
            ->with(['items.variant.product.images', 'latestPayment.order'])
            ->firstOrFail();

        return response()->json([
            'order' => (new OrderDetailResource($order))->resolve($request),
            'order_access_token' => $this->orderAccessTokenService->issue($order),
        ]);
    }

    public function addresses(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return response()->json([
            'addresses' => AddressResource::collection($addresses)->resolve($request),
            'provinces' => $this->cartService->getProvinces(),
        ]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $data = $this->validateAddressPayload($request);
        $user = $request->user();

        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data);

        return response()->json([
            'message' => 'Address saved successfully.',
            'address' => (new AddressResource($address))->resolve($request),
        ], 201);
    }

    public function updateAddress(Request $request, Address $address): JsonResponse
    {
        if ((int) $address->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this address.',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $data = $this->validateAddressPayload($request);

        if (! empty($data['is_default'])) {
            $request->user()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'message' => 'Address updated successfully.',
            'address' => (new AddressResource($address->fresh()))->resolve($request),
        ]);
    }

    public function destroyAddress(Request $request, Address $address): JsonResponse
    {
        if ((int) $address->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this address.',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAddressPayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required_without:first_name', 'nullable', 'string', 'max:200'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'province' => ['required', 'string'],
            'district' => ['nullable', 'string', 'max:150'],
            'commune' => ['nullable', 'string', 'max:150'],
            'street_address' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['name']) && empty($data['first_name'])) {
            $parts = explode(' ', trim((string) $data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
        }

        unset($data['name']);
        $data['type'] = 'shipping';
        $data['first_name'] = (string) ($data['first_name'] ?? '');
        $data['last_name'] = (string) ($data['last_name'] ?? '');
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }
}
