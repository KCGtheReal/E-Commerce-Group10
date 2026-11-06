<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Catalog & Inventory';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $activeCount = Discount::where('is_active', true)->count();

        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Discount Code (e.g. RESET10)')
                    ->required()
                    ->unique(Discount::class, 'code', ignoreRecord: true),

                Forms\Components\Select::make('type')
                    ->options(DiscountType::class)
                    ->required(),

                Forms\Components\TextInput::make('value')
                    ->label('Discount Value')
                    ->numeric()
                    ->required()
                    ->helperText('If Percentage: enter integer (e.g. 10 for 10%). If Fixed Amount: enter in KHR (e.g. 20000 for 20,000 ៛).'),

                Forms\Components\TextInput::make('min_spend_cents')
                    ->label('Minimum Spend (៛ KHR)')
                    ->prefix('៛')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('max_uses')
                    ->label('Max Uses (Empty for unlimited)')
                    ->numeric(),

                Forms\Components\TextInput::make('used_count')
                    ->label('Times Used')
                    ->numeric()
                    ->default(0)
                    ->disabled(),

                Forms\Components\DateTimePicker::make('starts_at'),

                Forms\Components\DateTimePicker::make('expires_at'),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge(),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (Discount $record) => $record->formatted_value),

                Tables\Columns\TextColumn::make('used_count')
                    ->label('Usage')
                    ->formatStateUsing(fn (Discount $record) => $record->max_uses ? "{$record->used_count} / {$record->max_uses}" : "{$record->used_count} / ∞"),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M d, Y')
                    ->placeholder('Never'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
