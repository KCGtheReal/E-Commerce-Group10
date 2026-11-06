<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class OrderStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $paidStatuses = [
            OrderStatus::Paid,
            OrderStatus::Packed,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ];

        $rate = (int) Setting::get('exchange_rate', 4100);
        if ($rate <= 0) {
            $rate = 4100;
        }

        // Today's Performance
        $todayRevenueKhr = (float) Order::whereIn('status', $paidStatuses)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_cents');
        $todayOrdersCount = Order::whereIn('status', $paidStatuses)
            ->whereDate('created_at', Carbon::today())
            ->count();
        $todayRevenueUsd = $todayRevenueKhr / $rate;

        // Total Lifetime Performance
        $totalRevenueKhr = (float) Order::whereIn('status', $paidStatuses)->sum('total_cents');
        $revenueUsd = $totalRevenueKhr / $rate;
        $paidOrdersCount = Order::whereIn('status', $paidStatuses)->count();

        // Operational Counts
        $pendingPaymentsCount = Order::where('status', OrderStatus::PendingPayment)->count();
        $ordersToFulfillCount = Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Packed])->count();

        // 7-Day Revenue Sparkline
        $sparklineData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->whereDate('created_at', $day)
                ->sum('total_cents');
            $sparklineData[] = (int) round($dayRevenue / 1000); // in thousands of KHR
        }

        return [
            Stat::make("Today's Settled Revenue", number_format($todayRevenueKhr, 0).' ៛ KHR')
                ->description('≈ $'.number_format($todayRevenueUsd, 2).' USD • '.$todayOrdersCount.' orders today')
                ->descriptionIcon('heroicon-m-sparkles')
                ->chart($sparklineData)
                ->color('success'),

            Stat::make('Total Settled Revenue', number_format($totalRevenueKhr, 0).' ៛ KHR')
                ->description('≈ $'.number_format($revenueUsd, 2).' USD across '.$paidOrdersCount.' orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($sparklineData)
                ->color('primary'),

            Stat::make('Orders to Fulfill', (string) $ordersToFulfillCount)
                ->description('Paid & packed, awaiting courier dispatch')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Pending Bakong KHQR', (string) $pendingPaymentsCount)
                ->description('Active 5-minute QR scan sessions')
                ->descriptionIcon('heroicon-m-qr-code')
                ->color('warning'),
        ];
    }
}
