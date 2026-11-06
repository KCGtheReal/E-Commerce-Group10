<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Orders by Fulfillment Status';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $pending = Order::where('status', OrderStatus::PendingPayment)->count();
        $paid = Order::where('status', OrderStatus::Paid)->count();
        $inTransit = Order::whereIn('status', [OrderStatus::Packed, OrderStatus::Shipped])->count();
        $delivered = Order::where('status', OrderStatus::Delivered)->count();
        $cancelled = Order::whereIn('status', [OrderStatus::Cancelled, OrderStatus::Expired, OrderStatus::Refunded])->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total Orders',
                    'data' => [$pending, $paid, $inTransit, $delivered, $cancelled],
                    'backgroundColor' => [
                        '#F59E0B', // Pending - Amber
                        '#10B981', // Paid - Emerald
                        '#0EA5E9', // In Transit - Sky
                        '#8B5CF6', // Delivered - Purple
                        '#71717A', // Cancelled/Expired - Zinc
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                'Pending Payment ('.$pending.')',
                'Paid & Confirmed ('.$paid.')',
                'In Transit / Packed ('.$inTransit.')',
                'Delivered ('.$delivered.')',
                'Cancelled / Expired ('.$cancelled.')',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '70%',
        ];
    }
}
