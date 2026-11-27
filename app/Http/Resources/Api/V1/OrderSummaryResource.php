<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = strtoupper((string) ($this->currency ?: 'KHR'));
        $rate = (int) ($this->exchange_rate ?: CurrencyService::exchangeRate());
        $totalCents = (int) $this->total_cents;
        $equivalentUsd = $rate > 0 ? round($totalCents / $rate, 2) : 0.0;

        $itemsCount = array_key_exists('items_count', $this->resource->getAttributes())
            ? (int) $this->resource->getAttribute('items_count')
            : ($this->relationLoaded('items') ? $this->items->count() : $this->items()->count());

        $previewNames = $this->relationLoaded('items')
            ? $this->items->take(3)->map(static fn ($item): string => $item->localized_product_name)->values()->all()
            : $this->items()->limit(3)->get()->map(static fn ($item): string => $item->localized_product_name)->values()->all();

        $formattedDiscount = (int) $this->discount_cents > 0
            ? $this->formatted_discount
            : CurrencyService::formatCents(0, $currency);

        return [
            'id' => (int) $this->id,
            'number' => (string) $this->number,
            'status' => $this->status?->value ?? (string) $this->status,
            'status_label' => $this->status?->label() ?? ucfirst((string) $this->status),
            'status_color' => $this->status?->color() ?? 'gray',
            'currency' => $currency,
            'exchange_rate' => $rate,
            'subtotal_cents' => (int) $this->subtotal_cents,
            'formatted_subtotal' => $this->formatted_subtotal,
            'discount_cents' => (int) $this->discount_cents,
            'formatted_discount' => $formattedDiscount,
            'discount_code' => $this->discount_code,
            'shipping_fee_cents' => (int) $this->shipping_fee_cents,
            'formatted_shipping' => $this->formatted_shipping,
            'shipping_method' => (string) ($this->shipping_method ?: 'standard'),
            'total_cents' => $totalCents,
            'formatted_total' => $this->formatted_total,
            'total_charged_currency_amount' => (float) $this->total_charged_currency_amount,
            'formatted_charged_total' => $this->formatted_charged_total,
            'equivalent_khr_amount' => $totalCents,
            'formatted_equivalent_khr' => number_format($totalCents, 0).' ៛',
            'equivalent_usd_amount' => $equivalentUsd,
            'formatted_equivalent_usd' => '$'.number_format($equivalentUsd, 2),
            'items_count' => $itemsCount,
            'preview_item_names' => $previewNames,
            'can_pay_now' => in_array($this->status, [OrderStatus::PendingPayment, OrderStatus::Expired], true),
            'created_at' => $this->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'formatted_created_at' => $this->created_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i'),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
