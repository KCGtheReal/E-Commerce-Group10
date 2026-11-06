<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Catalog & Inventory';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $lowStock = ProductVariant::where('stock_quantity', '<=', 5)->count();

        return $lowStock > 0 ? "{$lowStock} low" : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Variants with critical or low inventory';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General Information')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Name (English)')
                                    ->lazy(),

                                Forms\Components\TextInput::make('name_km')
                                    ->label('Name (Khmer)'),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                Forms\Components\TextInput::make('sku_prefix')
                                    ->required()
                                    ->default('RST'),

                                Forms\Components\TextInput::make('tagline')
                                    ->label('Tagline (English)'),

                                Forms\Components\TextInput::make('tagline_km')
                                    ->label('Tagline (Khmer)'),
                            ])->columns(2),

                        Forms\Components\Section::make('Descriptions & Details')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Description (English)')
                                    ->required()
                                    ->rows(3),

                                Forms\Components\Textarea::make('description_km')
                                    ->label('Description (Khmer)')
                                    ->rows(3),
                            ]),

                        Forms\Components\Section::make('Variants (Sizes & Colors)')
                            ->schema([
                                Forms\Components\Repeater::make('variants')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->required(),
                                        Forms\Components\Select::make('size')
                                            ->options([
                                                'XS' => 'XS',
                                                'S' => 'S',
                                                'M' => 'M',
                                                'L' => 'L',
                                                'XL' => 'XL',
                                                'XXL' => 'XXL',
                                                'OS' => 'One Size',
                                            ])
                                            ->required(),
                                        Forms\Components\ColorPicker::make('color_code'),
                                        Forms\Components\TextInput::make('price_cents')
                                            ->numeric()
                                            ->default(100000)
                                            ->required()
                                            ->prefix('៛')
                                            ->label('Price (៛ KHR)'),
                                        Forms\Components\TextInput::make('stock_quantity')
                                            ->numeric()
                                            ->default(10)
                                            ->required()
                                            ->label('Stock Qty'),
                                    ])
                                    ->columns(7)
                                    ->defaultItems(1),
                            ]),

                        Forms\Components\Section::make('Product Images')
                            ->schema([
                                Forms\Components\Repeater::make('images')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('image_url')
                                            ->required()
                                            ->label('Image URL / Path'),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('Primary Image'),
                                        Forms\Components\TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pricing')
                            ->schema([
                                Forms\Components\TextInput::make('base_price_cents')
                                    ->label('Base Price (៛ KHR)')
                                    ->prefix('៛')
                                    ->required()
                                    ->numeric()
                                    ->helperText('Price in Cambodian Riel (៛).'),

                                Forms\Components\TextInput::make('compare_at_price_cents')
                                    ->label('Compare-at Price (៛ KHR)')
                                    ->prefix('៛')
                                    ->numeric()
                                    ->helperText('Optional strike-through original price in Cambodian Riel (៛).'),
                            ]),

                        Forms\Components\Section::make('Visibility & Flags')
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Published on Storefront')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured on Home'),

                                Forms\Components\Toggle::make('is_new_arrival')
                                    ->label('New Arrival Badge'),

                                Forms\Components\Toggle::make('is_best_seller')
                                    ->label('Best Seller Badge'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image_url')
                    ->label('Image'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): ?string => $record->name_km),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0).' ៛')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants'),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published Status'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Status'),
            ])
            ->actions([
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
