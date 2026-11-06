<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Services\BakongService;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Sales & Orders';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::whereIn('status', [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Packed])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Orders awaiting payment or courier dispatch';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Order Summary')
                            ->schema([
                                Forms\Components\TextInput::make('number')
                                    ->label('Order Number')
                                    ->disabled(),

                                Forms\Components\Select::make('status')
                                    ->options(OrderStatus::class)
                                    ->required(),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required(),

                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Section::make('Fulfillment & Tracking')
                            ->schema([
                                Forms\Components\TextInput::make('shipping_carrier')
                                    ->label('Courier / Carrier')
                                    ->placeholder('e.g. J&T Express, Virak Buntham, Grab'),

                                Forms\Components\TextInput::make('tracking_number')
                                    ->label('Tracking Reference')
                                    ->placeholder('e.g. JT89327492KH'),

                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->disabled(),

                                Forms\Components\DateTimePicker::make('shipped_at'),

                                Forms\Components\DateTimePicker::make('delivered_at'),
                            ])->columns(2),

                        Forms\Components\Section::make('Customer Delivery Address')
                            ->schema([
                                Forms\Components\KeyValue::make('shipping_address')
                                    ->label('Address Details'),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Financial Details')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_cents')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn (?Order $record) => $record?->formatted_subtotal ?? '')
                                    ->disabled(),

                                Forms\Components\TextInput::make('discount_cents')
                                    ->label('Discount')
                                    ->formatStateUsing(fn (?Order $record) => $record?->formatted_discount ?? '')
                                    ->disabled(),

                                Forms\Components\TextInput::make('shipping_fee_cents')
                                    ->label('Shipping Fee')
                                    ->formatStateUsing(fn (?Order $record) => $record?->formatted_shipping ?? '')
                                    ->disabled(),

                                Forms\Components\TextInput::make('total_cents')
                                    ->label('Total')
                                    ->formatStateUsing(fn (?Order $record) => $record?->formatted_total ?? '')
                                    ->disabled(),

                                Forms\Components\TextInput::make('exchange_rate')
                                    ->label('Exchange Rate (៛/$)')
                                    ->disabled(),
                            ]),

                        Forms\Components\Section::make('Refunds')
                            ->schema([
                                Forms\Components\TextInput::make('refund_amount_cents')
                                    ->label('Refund Amount (Cents)')
                                    ->numeric(),

                                Forms\Components\Textarea::make('refund_note')
                                    ->label('Refund Reason / Memo'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record): string => $record->phone),

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
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (Order $record): string => $record->formatted_total)
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('shipping_carrier')
                    ->label('Carrier / Tracking')
                    ->description(fn (Order $record): ?string => $record->tracking_number)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(OrderStatus::class),
            ])
            ->actions([
                // Manual Mark Paid Action
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::PendingPayment)
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Manual Payment Verification Note')
                            ->required()
                            ->placeholder('e.g. Verified via direct bank statement / deposit slip #9841'),
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

                // Check Bakong Live Status Action
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

                // Update Fulfillment & Tracking Action
                Tables\Actions\Action::make('update_shipping')
                    ->label('Fulfill / Ship')
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
                            ->label('Tracking / Reference Number')
                            ->placeholder('e.g. JT12345678KH')
                            ->default(fn (Order $record) => $record->tracking_number),

                        Forms\Components\Textarea::make('status_message')
                            ->label('Custom Message to Customer (Optional)')
                            ->placeholder('Leave empty to use automatic status update message')
                            ->rows(2),

                        Forms\Components\Toggle::make('notify_customer')
                            ->label('Send email notification to customer')
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
                            $customMsg = ! empty($data['status_message']) ? (string) $data['status_message'] : null;
                            try {
                                Mail::to($record->email)->send(new OrderStatusUpdatedMail($record, $customMsg));
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }

                        Notification::make()
                            ->title('Fulfillment Updated')
                            ->body('Order status set to '.$newStatus->label())
                            ->success()
                            ->send();
                    }),

                // Print Invoice Link
                Tables\Actions\Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Order $record): string => route('order.invoice', $record->number))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
