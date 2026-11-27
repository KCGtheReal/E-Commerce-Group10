<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Discount
 */
class CartDiscountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'type' => $this->type?->value ?? (string) $this->type,
            'value' => (int) $this->value,
            'formatted_value' => $this->formatted_value,
            'min_spend_cents' => (int) $this->min_spend_cents,
        ];
    }
}
