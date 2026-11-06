<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $rate = (int) Setting::get('exchange_rate', 4100);
        if ($rate <= 0) {
            $rate = 4100;
        }

        $freeShipping = (int) Setting::get('free_shipping_threshold', 200000);
        $standardShippingFee = (int) Setting::get('standard_shipping_fee_khr', 0);
        $pendingPaymentsCount = Order::where('status', OrderStatus::PendingPayment)->count();
        $readyToShipCount = Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Packed])->count();
        $lowStockCount = ProductVariant::where('stock_quantity', '<=', 5)->count();
        $unreadInquiriesCount = ContactMessage::where('is_read', false)->count();

        return [
            'adminName' => auth()->user()?->name ?? 'Administrator',
            'exchangeRate' => $rate,
            'freeShippingThreshold' => $freeShipping,
            'standardShippingFee' => $standardShippingFee,
            'isBakongMock' => (bool) config('bakong.mock', false),
            'merchantId' => config('bakong.account_id', 'reset_clothing@aclb'),
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'readyToShipCount' => $readyToShipCount,
            'lowStockCount' => $lowStockCount,
            'unreadInquiriesCount' => $unreadInquiriesCount,
            'todayDate' => now()->format('l, F j, Y'),
        ];
    }
}
