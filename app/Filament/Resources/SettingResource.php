<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings & System';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Setting Key')
                    ->helperText(fn (?Setting $record): string => match ($record?->key) {
                        'store_name' => 'Brand name used across storefront, email notifications, and customer invoices.',
                        'store_tagline' => 'English brand tagline displayed on homepage hero and meta descriptions.',
                        'store_tagline_km' => 'Khmer brand tagline displayed when the customer switches language to Khmer.',
                        'store_email' => 'Support email address displayed in store footer and order emails.',
                        'store_phone' => 'Customer service phone number shown on the contact page and footer.',
                        'store_address' => 'Physical store and studio address in Phnom Penh, Cambodia.',
                        'exchange_rate' => 'Exchange rate used to convert prices between ៛ KHR and $ USD (e.g., 4100 = 1 USD).',
                        'free_shipping_threshold' => 'Minimum cart subtotal in ៛ KHR to qualify for free courier delivery.',
                        'announcement_banner_en' => 'English marquee announcement banner shown at the very top of the store.',
                        'announcement_banner_km' => 'Khmer marquee announcement banner shown when Khmer language is selected.',
                        'province_shipping_fees' => 'JSON mapping of delivery fee for each province across Cambodia.',
                        default => 'Internal configuration identifier.',
                    })
                    ->disabled()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('value')
                    ->label('Setting Value')
                    ->helperText(fn (?Setting $record): string => match ($record?->key) {
                        'exchange_rate' => 'Enter number of KHR per 1 USD (e.g., 4100). Used by Currency switcher and Bakong KHQR.',
                        'free_shipping_threshold' => 'Enter amount in ៛ KHR (e.g., 200000). Cart progress bar updates automatically.',
                        'province_shipping_fees' => 'JSON object mapping province names to delivery fees in ៛ KHR (0 = free delivery).',
                        default => 'Text or configuration value.',
                    })
                    ->required()
                    ->rows(5)
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                    ->dehydrateStateUsing(function ($state) {
                        $decoded = json_decode((string) $state, true);

                        return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Name')
                    ->weight('bold')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'store_name' => 'Store Name',
                        'store_tagline' => 'Tagline (English)',
                        'store_tagline_km' => 'Tagline (Khmer)',
                        'store_email' => 'Support Email',
                        'store_phone' => 'Contact Phone',
                        'store_address' => 'Store Address',
                        'exchange_rate' => 'Exchange Rate (USD → KHR)',
                        'free_shipping_threshold' => 'Free Delivery Threshold',
                        'announcement_banner_en' => 'Top Banner (English)',
                        'announcement_banner_km' => 'Top Banner (Khmer)',
                        'province_shipping_fees' => 'Provincial Delivery Rates',
                        default => str($state)->headline()->toString(),
                    })
                    ->description(fn (Setting $record): string => match ($record->key) {
                        'store_name' => 'Brand name on storefront & invoices',
                        'store_tagline' => 'English hero subtitle',
                        'store_tagline_km' => 'Khmer hero subtitle',
                        'store_email' => 'Support contact address',
                        'store_phone' => 'Customer helpline',
                        'store_address' => 'Flagship studio in Phnom Penh',
                        'exchange_rate' => 'Currency converter rate (1 USD = X KHR)',
                        'free_shipping_threshold' => 'Minimum order amount for free delivery',
                        'announcement_banner_en' => 'Top marquee text in English',
                        'announcement_banner_km' => 'Top marquee text in Khmer',
                        'province_shipping_fees' => 'Cambodian provinces shipping fees',
                        default => $record->key,
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Current Configured Value')
                    ->limit(65)
                    ->formatStateUsing(function ($state, Setting $record) {
                        if ($record->key === 'exchange_rate') {
                            return '1 USD = '.number_format((float) $state).' ៛ KHR';
                        }
                        if ($record->key === 'free_shipping_threshold') {
                            return number_format((float) $state).' ៛ KHR';
                        }
                        if ($record->key === 'province_shipping_fees') {
                            return '25 Cambodian Provinces Configured (Phnom Penh, Siem Reap, etc.)';
                        }

                        return (string) $state;
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
