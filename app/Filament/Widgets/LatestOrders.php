<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Services\BakongService;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Mail;

class LatestOrders extends BaseWidget
{
    protected static ?string $heading = 'Recent Orders & Quick Fulfillment';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.edit', $record)),

                Tables\Columns\TextColumn::make('email')
                    ->label('Customer')
                    ->description(function (Order $record): string {
                        $province = $record->shipping_address['province'] ?? '';
                        $phone = $record->phone;

                        return trim($phone.($province ? ' • '.$province : ''));
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::PendingPayment => 'warning',
                        OrderStatus::Paid => 'success',
                        OrderStatus::Packed => 'info',
                        OrderStatus::Shipped => 'primary',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled, OrderStatus::Expired => 'gray',
                        OrderStatus::Refunded => 'danger',
                    }),

                Tables\Columns\TextColumn::make('shipping_carrier')
                    ->label('Courier / Tracking')
                    ->default('—')
                    ->description(fn (Order $record): ?string => $record->tracking_number),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (Order $record): string => $record->formatted_total)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed At')
                    ->dateTime('M d, Y H:i')
                    ->description(fn (Order $record): string => $record->created_at->diffForHumans()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All Orders')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->color('gray')
                    ->url(fn (): string => OrderResource::getUrl('index')),
            ])
            ->actions([
                // Quick View / Edit
                Tables\Actions\Action::make('view')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.edit', $record)),

                // Instant KHQR Gateway Check
                Tables\Actions\Action::make('check_bakong')
                    ->label('Check KHQR')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::PendingPayment && $record->latestPayment !== null)
                    ->action(function (Order $record): void {
                        $bakongService = app(BakongService::class);
                        $orderService = app(OrderService::class);

                        $payment = $record->latestPayment;
                        $result = $bakongService->checkTransaction($payment);

                        if ($result['paid']) {
                            $orderService->finalizePaidOrder($payment, $result['data'] ?? []);
                            Notification::make()
                                ->title('Bakong Settlement Confirmed!')
                                ->body('Payment verified via National Bank of Cambodia gateway.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Payment Pending')
                                ->body($result['error'] ?? 'Transaction has not yet been completed by customer.')
                                ->warning()
                                ->send();
                        }
                    }),

                // Instant Mark Paid
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::PendingPayment)
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Manual Payment Verification Note')
                            ->required()
                            ->placeholder('e.g. Verified via direct ABA bank statement deposit'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Order $record, array $data): void {
                        $orderService = app(OrderService::class);
                        $orderService->manualMarkPaid($record, auth()->user(), (string) $data['note']);

                        Notification::make()
                            ->title('Order #'.$record->number.' marked as PAID')
                            ->success()
                            ->send();
                    }),

                // Instant Fulfill & Ship
                Tables\Actions\Action::make('fulfill')
                    ->label('Fulfill')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => $record->isPaid())
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                OrderStatus::Packed->value => 'Packed & Ready',
                                OrderStatus::Shipped->value => 'Shipped / In Transit',
                                OrderStatus::Delivered->value => 'Delivered',
                            ])
                            ->default(fn (Order $record) => $record->status->value)
                            ->required(),

                        Forms\Components\TextInput::make('shipping_carrier')
                            ->label('Courier Company')
                            ->placeholder('e.g. J&T Express, Virak Buntham, Capitol')
                            ->default(fn (Order $record) => $record->shipping_carrier),

                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Reference')
                            ->placeholder('e.g. JT12345678KH')
                            ->default(fn (Order $record) => $record->tracking_number),

                        Forms\Components\Toggle::make('notify_customer')
                            ->label('Send dispatch email notification to customer')
                            ->default(true),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $newStatus = OrderStatus::from((string) $data['status']);
                        $updates = [
                            'status' => $newStatus,
                            'shipping_carrier' => $data['shipping_carrier'],
                            'tracking_number' => $data['tracking_number'],
                        ];

                        if ($newStatus === OrderStatus::Shipped && ! $record->shipped_at) {
                            $updates['shipped_at'] = now();
                        } elseif ($newStatus === OrderStatus::Delivered && ! $record->delivered_at) {
                            $updates['delivered_at'] = now();
                        }

                        $record->update($updates);

                        if (! empty($data['notify_customer']) && $record->email) {
                            Mail::to($record->email)->send(new OrderStatusUpdatedMail($record));
                        }

                        Notification::make()
                            ->title('Order Fulfill Status Updated')
                            ->body('Order set to '.$newStatus->label())
                            ->success()
                            ->send();
                    }),

                // Print Tax Invoice
                Tables\Actions\Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Order $record): string => route('order.invoice', $record->number))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}
