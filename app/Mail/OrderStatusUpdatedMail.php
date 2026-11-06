<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $statusMessage;

    public function __construct(public Order $order, ?string $statusMessage = null)
    {
        if ($statusMessage !== null && trim($statusMessage) !== '') {
            $this->statusMessage = trim($statusMessage);
        } else {
            $this->statusMessage = match ($order->status) {
                OrderStatus::Paid => 'Your payment has been settled and your order is currently being prepared at our Phnom Penh studio.',
                OrderStatus::Packed => 'Your order has been carefully packaged and is prepared for courier dispatch.',
                OrderStatus::Shipped => 'Your order has been dispatched with our delivery courier. Tracking information is available below.',
                OrderStatus::Delivered => 'Your order has been delivered. Thank you for choosing RESET.',
                OrderStatus::Cancelled => 'Your order has been cancelled. If you believe this was an error, please contact our support team.',
                OrderStatus::Refunded => 'Your order has been refunded. If you have questions, please reach out to our support team.',
                default => 'The status of your order has been updated to '.$order->status->label().'.',
            };
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update on your Order #{$this->order->number} - RESET Store",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-updated',
        );
    }
}
