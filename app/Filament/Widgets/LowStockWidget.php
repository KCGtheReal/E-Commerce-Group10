<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Critical Inventory & Low Stock Alerts';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with('product')
                    ->where('stock_quantity', '<=', 5)
                    ->orderBy('stock_quantity', 'asc')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Garment / Product')
                    ->weight('bold')
                    ->searchable()
                    ->url(fn (ProductVariant $record): string => route('filament.admin.resources.products.edit', $record->product_id)),

                Tables\Columns\TextColumn::make('color')
                    ->label('Color & Size')
                    ->formatStateUsing(fn (ProductVariant $record): string => "{$record->color} / {$record->size}")
                    ->description(fn (ProductVariant $record): string => "SKU: {$record->sku}"),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Inventory Status')
                    ->badge()
                    ->formatStateUsing(function (int $state): string {
                        if ($state <= 0) {
                            return 'Out of Stock (0)';
                        }

                        return "Low Stock: {$state} left";
                    })
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Unit Price')
                    ->formatStateUsing(fn (ProductVariant $record): string => $record->formatted_price)
                    ->weight('semibold'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('manage_products')
                    ->label('All Products')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->color('gray')
                    ->url(fn (): string => ProductResource::getUrl('index')),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_product')
                    ->label('Restock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn (ProductVariant $record): string => route('filament.admin.resources.products.edit', $record->product_id)),
            ])
            ->emptyStateHeading('All Inventory Healthy')
            ->emptyStateDescription('There are currently no garments or variants with critically low stock.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
