<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CartItem
 */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->variant;
        $product = $variant->product;
        $currency = CurrencyService::current();
        $priceCents = (int) $variant->price_cents;
        $subtotalCents = (int) $this->subtotal_cents;

        return [
            'id' => (int) $this->id,
            'product_id' => (int) $product->id,
            'product_slug' => (string) $product->slug,
            'product_variant_id' => (int) $variant->id,
            'product_name' => $product->localized_name,
            'product_name_en' => (string) $product->name,
            'product_name_km' => $product->name_km,
            'variant_label' => $variant->label,
            'sku' => (string) $variant->sku,
            'size' => (string) $variant->size,
            'color' => (string) $variant->color,
            'color_code' => (string) ($variant->color_code ?: '#111111'),
            'image_url' => $product->primary_image_url,
            'price_cents' => $priceCents,
            'formatted_price' => CurrencyService::formatCents($priceCents, $currency),
            'converted_price' => CurrencyService::convertCents($priceCents, $currency),
            'quantity' => (int) $this->quantity,
            'max_stock' => min(CartService::MAX_ITEM_QUANTITY, max(0, (int) $variant->available_stock)),
            'subtotal_cents' => $subtotalCents,
            'formatted_subtotal' => CurrencyService::formatCents($subtotalCents, $currency),
            'converted_subtotal' => CurrencyService::convertCents($subtotalCents, $currency),
        ];
    }
}
