<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function notifyOrderPaid(Order $order): void
    {
        $botToken = config('bakong.telegram.bot_token');
        $chatId = config('bakong.telegram.chat_id');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        try {
            $currencySymbol = $order->currency === 'KHR' ? '៛' : '$';
            $itemsSummary = $order->items->map(fn ($item) => "- {$item->product_name} ({$item->variant_label}) x{$item->quantity}")->implode("\n");

            $customerName = $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? trim(($order->shipping_address['first_name'] ?? '').' '.($order->shipping_address['last_name'] ?? ''));

            $message = "✨ *NEW PAID ORDER: #{$order->number}*\n\n"
                ."👤 *Customer:* {$customerName}\n"
                ."📱 *Phone:* {$order->phone}\n"
                ."📍 *Province:* {$order->shipping_address['province']}\n"
                ."💰 *Total:* {$order->formatted_charged_total}\n"
                ."💳 *Payment:* Bakong KHQR (Verified)\n\n"
                ."📦 *Items:*\n{$itemsSummary}\n\n"
                .'⚡ *Status:* Paid & Ready to Pack';

            Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (Exception $e) {
            Log::error('Telegram notification error: '.$e->getMessage());
        }
    }
}
