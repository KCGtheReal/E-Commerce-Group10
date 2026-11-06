<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\BakongService;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Sales & Orders';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Payment::where('status', PaymentStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending Bakong KHQR transactions';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'number')
                    ->disabled(),

                Forms\Components\TextInput::make('provider')
                    ->disabled(),

                Forms\Components\TextInput::make('amount')
                    ->disabled(),

                Forms\Components\TextInput::make('currency')
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->options(PaymentStatus::class)
                    ->required(),

                Forms\Components\TextInput::make('md5')
                    ->label('MD5 Hash')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('paid_at')
                    ->disabled(),

                Forms\Components\Textarea::make('manual_paid_note')
                    ->label('Manual Override Memo')
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('bakong_response')
                    ->label('Raw Gateway Payload')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (Payment $record) => $record->formatted_amount)
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Expired => 'gray',
                        PaymentStatus::Failed => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('md5')
                    ->label('Bakong MD5')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->limit(12),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Settled At')
                    ->dateTime('M d, H:i:s')
                    ->sortable()
                    ->placeholder('Unsettled'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PaymentStatus::class),
            ])
            ->actions([
                // Check status button
                Tables\Actions\Action::make('check_md5')
                    ->label('Check Gateway')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Payment $record): bool => $record->isPending())
                    ->action(function (Payment $record): void {
                        $bakong = app(BakongService::class);
                        $orderService = app(OrderService::class);
                        $res = $bakong->checkTransaction($record);

                        if ($res['paid']) {
                            $orderService->finalizePaidOrder($record, $res['data'] ?? []);
                            Notification::make()->title('Payment is Paid!')->success()->send();
                        } else {
                            Notification::make()->title('Payment Pending')->body($res['error'] ?? 'Unpaid')->warning()->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
