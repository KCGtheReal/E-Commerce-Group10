<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\BakongService;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireStalePaymentsCommand extends Command
{
    protected $signature = 'payments:expire-stale';

    protected $description = 'Expire stale pending KHQR payments after verifying final gateway status';

    public function handle(BakongService $bakongService, OrderService $orderService): int
    {
        $stalePayments = Payment::where('status', PaymentStatus::Pending)
            ->where('expires_at', '<=', now())
            ->with(['order.items'])
            ->get();

        if ($stalePayments->isEmpty()) {
            $this->info('No stale payments found.');

            return self::SUCCESS;
        }

        $this->info("Verifying and expiring {$stalePayments->count()} stale payment(s)...");

        foreach ($stalePayments as $payment) {
            $this->line("Verifying payment #{$payment->id} with Bakong before expiring...");

            // Authoritative check with gateway: Did user pay right before expiration?
            $result = $bakongService->checkTransaction($payment);
            $payment->update(['last_checked_at' => now()]);

            if ($result['paid']) {
                $this->info("Payment #{$payment->id} was PAID! Finalizing order #{$payment->order->number}...");
                $orderService->finalizePaidOrder($payment, $result['data'] ?? []);
            } else {
                $this->line("Payment #{$payment->id} confirmed unpaid. Expiring and releasing stock...");
                $orderService->expirePayment($payment);
            }
        }

        $this->info('Stale payments processed successfully.');

        return self::SUCCESS;
    }
}
