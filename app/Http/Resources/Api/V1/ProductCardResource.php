<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = CurrencyService::current();
        $basePrice = (int) $this->base_price_cents;
        $comparePrice = $this->compare_at_price_cents !== null ? (int) $this->compare_at_price_cents : null;

        $colors = $this->variants
            ->filter(static fn ($v): bool => ! empty($v->color))
            ->unique('color')
            ->map(static fn ($v): array => [
                'name' => (string) $v->color,
                'code' => (string) ($v->color_code ?: '#111111'),
            ])
            ->values()
            ->all();

        $totalStock = $this->total_stock;

        return [
            'id' => (int) $this->id,
            'name' => $this->localized_name,
            'name_en' => (string) $this->name,
            'name_km' => $this->name_km,
            'slug' => (string) $this->slug,
            'sku_prefix' => (string) $this->sku_prefix,
            'tagline' => $this->localized_tagline,
            'tagline_en' => $this->tagline,
            'tagline_km' => $this->tagline_km,
            'category' => $this->category ? (new CategorySummaryResource($this->category))->resolve($request) : null,
            'primary_image_url' => $this->primary_image_url,
            'base_price_cents' => $basePrice,
            'compare_at_price_cents' => $comparePrice,
            'formatted_price' => CurrencyService::formatCents($basePrice, $currency),
            'formatted_compare_at_price' => $comparePrice !== null
                ? CurrencyService::formatCents($comparePrice, $currency)
                : null,
            'converted_price' => CurrencyService::convertCents($basePrice, $currency),
            'converted_compare_at_price' => $comparePrice !== null
                ? CurrencyService::convertCents($comparePrice, $currency)
                : null,
            'is_on_sale' => $comparePrice !== null && $comparePrice > $basePrice,
            'is_featured' => (bool) $this->is_featured,
            'is_new_arrival' => (bool) $this->is_new_arrival,
            'is_best_seller' => (bool) $this->is_best_seller,
            'is_in_stock' => $totalStock > 0,
            'total_stock' => $totalStock,
            'average_rating' => round((float) $this->average_rating, 1),
            'review_count' => (int) $this->review_count,
            'colors' => $colors,
        ];
    }
}
