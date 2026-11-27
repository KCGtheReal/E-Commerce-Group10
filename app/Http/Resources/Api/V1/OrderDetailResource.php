<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * @mixin Order
 */
class OrderDetailResource extends OrderSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        return array_merge($base, [
            'email' => (string) $this->email,
            'phone' => (string) $this->phone,
            'shipping_address' => $this->normalizeAddressPayload($this->shipping_address),
            'billing_address' => $this->normalizeAddressPayload($this->billing_address ?: $this->shipping_address),
            'tracking_number' => $this->tracking_number,
            'shipping_carrier' => $this->shipping_carrier,
            'customer_notes' => $this->customer_notes,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items' => OrderItemResource::collection($this->items)->resolve($request),
            'latest_payment' => $this->latestPayment
                ? (new PaymentResource($this->latestPayment))->resolve($request)
                : null,
            'status_timeline' => $this->buildStatusTimeline(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<string, mixed>
     */
    private function normalizeAddressPayload(?array $address): array
    {
        $addr = $address ?? [];
        $first = (string) ($addr['first_name'] ?? '');
        $last = (string) ($addr['last_name'] ?? '');
        $name = (string) ($addr['name'] ?? ($addr['full_name'] ?? trim("{$first} {$last}")));
        $fullName = (string) ($addr['full_name'] ?? $name);

        return [
            'name' => $name,
            'full_name' => $fullName,
            'first_name' => $first !== '' ? $first : $name,
            'last_name' => $last,
            'phone' => (string) ($addr['phone'] ?? $this->phone),
            'province' => (string) ($addr['province'] ?? 'Phnom Penh'),
            'district' => $addr['district'] ?? null,
            'commune' => $addr['commune'] ?? null,
            'street_address' => (string) ($addr['street_address'] ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildStatusTimeline(): array
    {
        $statusValue = $this->status?->value ?? (string) $this->status;
        $ranks = [
            OrderStatus::PendingPayment->value => 0,
            OrderStatus::Paid->value => 1,
            OrderStatus::Packed->value => 2,
            OrderStatus::Shipped->value => 3,
            OrderStatus::Delivered->value => 4,
        ];
        $currentRank = $ranks[$statusValue] ?? -1;

        $steps = [
            [
                'key' => 'pending_payment',
                'label' => __('store.order_status_pending_payment'),
                'timestamp' => $this->created_at?->toIso8601String(),
                'rank' => 0,
            ],
            [
                'key' => 'paid',
                'label' => __('store.order_status_paid'),
                'timestamp' => $this->paid_at?->toIso8601String(),
                'rank' => 1,
            ],
            [
                'key' => 'packed',
                'label' => __('store.order_status_packed'),
                'timestamp' => $currentRank >= 2 ? ($this->updated_at?->toIso8601String()) : null,
                'rank' => 2,
            ],
            [
                'key' => 'shipped',
                'label' => __('store.order_status_shipped'),
                'timestamp' => $this->shipped_at?->toIso8601String(),
                'rank' => 3,
            ],
            [
                'key' => 'delivered',
                'label' => __('store.order_status_delivered'),
                'timestamp' => $this->delivered_at?->toIso8601String(),
                'rank' => 4,
            ],
        ];

        return array_map(static function (array $step) use ($currentRank): array {
            $rank = $step['rank'];
            $isNormalFlow = $currentRank >= 0;

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'completed' => $isNormalFlow && ($rank < $currentRank || ($currentRank === 4 && $rank === 4)),
                'active' => $isNormalFlow && $rank === $currentRank,
                'timestamp' => $step['timestamp'],
            ];
        }, $steps);
    }
}
