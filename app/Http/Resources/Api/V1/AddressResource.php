<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'type' => (string) ($this->type ?: 'shipping'),
            'first_name' => (string) $this->first_name,
            'last_name' => (string) ($this->last_name ?? ''),
            'full_name' => $this->full_name,
            'phone' => (string) $this->phone,
            'province' => (string) $this->province,
            'district' => $this->district,
            'commune' => $this->commune,
            'street_address' => (string) $this->street_address,
            'postal_code' => $this->postal_code,
            'formatted_address' => $this->formatted_address,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
