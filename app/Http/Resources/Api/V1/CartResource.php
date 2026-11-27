<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * @param  array<string, mixed>|null  $totals
     */
    public function __construct($resource, private readonly ?array $totals = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totals = $this->totals ?? app(CartService::class)->calculateTotals(
            $request->query('province'),
            (string) ($request->query('shipping_method', 'standard')),
            $this->resource
        );

        $activeDiscount = ($this->discount && $this->discount->isValidFor((int) $totals['subtotal_cents']))
            ? $this->discount
            : null;

        return [
            'cart_token' => $this->user_id ? null : $this->session_id,
            'currency' => $totals['currency'],
            'exchange_rate' => $totals['exchange_rate'],
            'province' => $totals['province'],
            'shipping_method' => $totals['shipping_method'],
            'subtotal_cents' => $totals['subtotal_cents'],
            'formatted_subtotal' => $totals['formatted_subtotal'],
            'discount_cents' => $totals['discount_cents'],
            'formatted_discount' => $totals['formatted_discount'],
            'shipping_fee_cents' => $totals['shipping_fee_cents'],
            'formatted_shipping' => $totals['formatted_shipping'],
            'total_cents' => $totals['total_cents'],
            'formatted_total' => $totals['formatted_total'],
            'converted_total' => $totals['converted_total'],
            'free_shipping_threshold_cents' => $totals['free_shipping_threshold_cents'],
            'formatted_free_shipping_threshold' => $totals['formatted_free_shipping_threshold'],
            'free_shipping_qualified' => $totals['free_shipping_qualified'],
            'free_shipping_remaining_cents' => $totals['free_shipping_remaining_cents'],
            'free_shipping_remaining_formatted' => $totals['free_shipping_remaining_formatted'],
            'free_shipping_progress_percent' => $totals['free_shipping_progress_percent'],
            'item_count' => (int) $this->item_count,
            'items' => CartItemResource::collection($this->items)->resolve($request),
            'discount_code' => $activeDiscount?->code,
            'discount' => $activeDiscount ? (new CartDiscountResource($activeDiscount))->resolve($request) : null,
        ];
    }
}
