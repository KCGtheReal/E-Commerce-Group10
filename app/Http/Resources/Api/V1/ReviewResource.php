<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'product_id' => (int) $this->product_id,
            'user_id' => (int) $this->user_id,
            'author_name' => (string) ($this->user?->name ?? 'RESET Customer'),
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'body' => (string) $this->body,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'created_at' => $this->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'formatted_date' => $this->created_at?->format('M d, Y') ?? now()->format('M d, Y'),
        ];
    }
}
