<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Settled Revenue (៛ KHR)';

    protected static ?int $sort = 2;

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
        $paidStatuses = [
            OrderStatus::Paid,
            OrderStatus::Packed,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ];

        $labels = [];
        $values = [];

        // Last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('D, M d');

            $dailyTotal = (float) Order::whereIn('status', $paidStatuses)
                ->whereDate('created_at', $date)
                ->sum('total_cents');

            $values[] = $dailyTotal;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (៛ KHR)',
                    'data' => $values,
                    'borderColor' => '#E65100',
                    'backgroundColor' => 'rgba(230, 81, 0, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return Number(value).toLocaleString() + " ៛"; }',
                    ],
                ],
            ],
        ];
    }
}
