<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ProductVariant;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = CurrencyService::current();

        return [
            'id' => (int) $this->id,
            'product_id' => (int) $this->product_id,
            'sku' => (string) $this->sku,
            'size' => (string) $this->size,
            'color' => (string) $this->color,
            'color_code' => (string) ($this->color_code ?: '#111111'),
            'label' => $this->label,
            'price_cents' => (int) $this->price_cents,
            'formatted_price' => CurrencyService::formatCents((int) $this->price_cents, $currency),
            'converted_price' => CurrencyService::convertCents((int) $this->price_cents, $currency),
            'stock_quantity' => (int) $this->stock_quantity,
            'reserved_quantity' => (int) $this->reserved_quantity,
            'available_stock' => (int) $this->available_stock,
            'is_in_stock' => (bool) $this->is_in_stock,
        ];
    }
}
