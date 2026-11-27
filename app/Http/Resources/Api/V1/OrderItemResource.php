<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->variant?->product;

        return [
            'id' => (int) $this->id,
            'product_variant_id' => $this->product_variant_id !== null ? (int) $this->product_variant_id : null,
            'product_slug' => $product?->slug,
            'image_url' => $product?->primary_image_url,
            'product_name' => $this->localized_product_name,
            'product_name_en' => (string) $this->product_name,
            'product_name_km' => $this->product_name_km,
            'variant_label' => (string) $this->variant_label,
            'sku' => (string) $this->sku,
            'price_cents' => (int) $this->price_cents,
            'formatted_price' => $this->formatted_price,
            'quantity' => (int) $this->quantity,
            'subtotal_cents' => (int) $this->subtotal_cents,
            'formatted_subtotal' => $this->formatted_subtotal,
        ];
    }
}
