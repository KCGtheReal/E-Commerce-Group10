<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\BakongService;
use App\Services\OrderService;
use Illuminate\Console\Command;

class CheckPendingPaymentsCommand extends Command
{
    protected $signature = 'payments:check-pending';

    protected $description = 'Check all unexpired pending Bakong KHQR transactions';

    public function handle(BakongService $bakongService, OrderService $orderService): int
    {
        $pendingPayments = Payment::where('status', PaymentStatus::Pending)
            ->where('expires_at', '>', now())
            ->with(['order.items'])
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('No pending unexpired payments to check.');

            return self::SUCCESS;
        }

        $this->info("Checking {$pendingPayments->count()} pending payment(s)...");

        foreach ($pendingPayments as $payment) {
            $this->line("Checking payment #{$payment->id} (Order: {$payment->order->number}, MD5: {$payment->md5})...");

            $result = $bakongService->checkTransaction($payment);
            $payment->update(['last_checked_at' => now()]);

            if ($result['paid']) {
                $this->info("Payment #{$payment->id} confirmed PAID! Finalizing order...");
                $orderService->finalizePaidOrder($payment, $result['data'] ?? []);
            } else {
                if ($result['error']) {
                    $this->warn("Payment #{$payment->id} check: {$result['error']}");
                } else {
                    $this->line("Payment #{$payment->id} still pending.");
                }
            }
        }

        return self::SUCCESS;
    }
}
