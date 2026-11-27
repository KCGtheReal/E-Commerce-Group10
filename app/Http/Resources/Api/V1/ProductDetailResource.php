<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use App\Services\StoreContentService;
use Illuminate\Http\Request;

/**
 * @mixin Product
 */
class ProductDetailResource extends ProductCardResource
{
    private const SIZE_WEIGHTS = [
        'XXS' => 1,
        'XS' => 2,
        'S' => 3,
        'M' => 4,
        'L' => 5,
        'XL' => 6,
        'XXL' => 7,
        '2XL' => 7,
        '3XL' => 8,
        'ONE SIZE' => 9,
        'ONESIZE' => 9,
        'FREE' => 9,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $detailsRaw = is_array($this->details) ? $this->details : [];

        $availableSizes = $this->variants
            ->pluck('size')
            ->filter()
            ->unique()
            ->sortBy(static function ($size): int {
                $upper = strtoupper(trim((string) $size));

                return self::SIZE_WEIGHTS[$upper] ?? 50;
            })
            ->values()
            ->all();

        $availableColors = $this->variants
            ->filter(static fn ($v): bool => ! empty($v->color))
            ->unique('color')
            ->map(static fn ($v): array => [
                'name' => (string) $v->color,
                'code' => (string) ($v->color_code ?: '#111111'),
            ])
            ->values()
            ->all();

        /** @var StoreContentService $contentService */
        $contentService = app(StoreContentService::class);
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());

        return array_merge($base, [
            'description' => $this->localized_description,
            'description_en' => (string) $this->description,
            'description_km' => $this->description_km,
            'images' => ProductImageResource::collection($this->images)->resolve($request),
            'variants' => ProductVariantResource::collection($this->variants)->resolve($request),
            'available_sizes' => $availableSizes,
            'available_colors' => $availableColors,
            'details' => [
                'fabric' => $detailsRaw['fabric'] ?? null,
                'care' => $detailsRaw['care'] ?? null,
                'fit' => $detailsRaw['fit'] ?? null,
                'shipping' => $detailsRaw['shipping'] ?? null,
            ],
            'detail_tabs' => $contentService->productDetailTabs($this->resource, $locale),
            'reviews' => ReviewResource::collection($this->reviews)->resolve($request),
        ]);
    }
}
