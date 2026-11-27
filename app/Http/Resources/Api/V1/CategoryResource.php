<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => (int) $this->id,
            'name' => $this->localized_name,
            'name_en' => (string) $this->name,
            'name_km' => $this->name_km,
            'slug' => (string) $this->slug,
            'description' => $this->localized_description,
            'description_en' => $this->description,
            'description_km' => $this->description_km,
            'image_url' => $this->image_url,
            'sort_order' => (int) $this->sort_order,
        ];

        if (array_key_exists('products_count', $this->resource->getAttributes())) {
            $data['products_count'] = (int) $this->products_count;
        }

        return $data;
    }
}
