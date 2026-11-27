<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $exchangeRate = (int) ($order?->exchange_rate ?: CurrencyService::exchangeRate());
        $currency = strtoupper((string) ($this->currency ?: 'KHR'));
        $amount = (float) $this->amount;

        if ($order) {
            $khrAmount = (int) $order->total_cents;
            $usdAmount = $currency === 'USD'
                ? round($amount, 2)
                : ($exchangeRate > 0 ? round($khrAmount / $exchangeRate, 2) : 0.0);
        } elseif ($currency === 'USD') {
            $usdAmount = round($amount, 2);
            $khrAmount = (int) round($usdAmount * $exchangeRate);
        } else {
            $khrAmount = (int) round($amount);
            $usdAmount = $exchangeRate > 0 ? round($khrAmount / $exchangeRate, 2) : 0.0;
        }

        return [
            'id' => (int) $this->id,
            'order_id' => (int) $this->order_id,
            'order_number' => (string) ($order?->number ?? ''),
            'provider' => (string) ($this->provider ?: 'bakong_khqr'),
            'md5' => (string) $this->md5,
            'qr_string' => (string) $this->qr_string,
            'account_id' => (string) $this->account_id,
            'merchant_name' => (string) config('bakong.merchant_name', 'RESET Store'),
            'amount' => $amount,
            'currency' => $currency,
            'formatted_amount' => $this->formatted_amount,
            'khr_amount' => $khrAmount,
            'formatted_khr_amount' => number_format($khrAmount, 0).' ៛',
            'usd_amount' => $usdAmount,
            'formatted_usd_amount' => '$'.number_format($usdAmount, 2),
            'exchange_rate' => $exchangeRate,
            'status' => $this->status?->value ?? (string) $this->status,
            'status_label' => $this->status?->label() ?? ucfirst((string) $this->status),
            'expires_at' => $this->expires_at?->toIso8601String() ?? now()->toIso8601String(),
            'time_remaining_seconds' => (int) $this->time_remaining_seconds,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'bakong_mock' => (bool) config('bakong.mock', false),
            'deeplink_url' => 'bakong://qr?data='.urlencode((string) $this->qr_string),
        ];
    }
}
