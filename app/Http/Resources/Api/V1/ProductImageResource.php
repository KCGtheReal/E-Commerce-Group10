<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductImage
 */
class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'image_url' => (string) $this->image_url,
            'alt_text' => $this->alt_text,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
