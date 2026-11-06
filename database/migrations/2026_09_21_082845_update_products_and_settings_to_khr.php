<?php

use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Product 1 ("RESET Raw Edge Heavyweight Tee") explicitly to 100 KHR
        $p1 = Product::find(1);
        if ($p1) {
            $p1->update([
                'base_price_cents' => 100,
                'compare_at_price_cents' => null,
            ]);
            ProductVariant::where('product_id', 1)->update(['price_cents' => 100]);
        }

        // 2. Scale other products to KHR if their prices were entered as USD cents (< 10000)
        $otherProducts = Product::where('id', '>', 1)->get();
        foreach ($otherProducts as $product) {
            if ($product->base_price_cents < 10000) {
                $newBase = (int) round(($product->base_price_cents / 100) * 4100);
                $newCompare = $product->compare_at_price_cents ? (int) round(($product->compare_at_price_cents / 100) * 4100) : null;
                $product->update([
                    'base_price_cents' => $newBase,
                    'compare_at_price_cents' => $newCompare,
                ]);

                foreach ($product->variants as $variant) {
                    $newVarPrice = (int) round(($variant->price_cents / 100) * 4100);
                    $variant->update(['price_cents' => $newVarPrice]);
                }
            }
        }

        // 3. Update Settings for KHR (Standard delivery set to 0 ៛)
        Setting::set('free_shipping_threshold', 200000, 'general');
        $provinces = [
            'Phnom Penh', 'Kandal', 'Siem Reap', 'Battambang', 'Sihanoukville',
            'Kampot', 'Kep', 'Takeo', 'Kampong Cham', 'Kampong Chhnang',
            'Kampong Speu', 'Kampong Thom', 'Koh Kong', 'Kratie', 'Mondulkiri',
            'Oddar Meanchey', 'Pailin', 'Preah Vihear', 'Prey Veng', 'Pursat',
            'Ratanakiri', 'Stung Treng', 'Svay Rieng', 'Tboung Khmum', 'Banteay Meanchey',
        ];
        $fees = array_fill_keys($provinces, 0);
        Setting::set('province_shipping_fees', $fees, 'general');

        // 4. Update Discounts for KHR
        Discount::where('code', 'FREESHIP')->update(['min_spend_cents' => 0]);
        Discount::where('code', 'RESET10')->update(['min_spend_cents' => 100000]);
        Discount::where('code', 'STARTFRESH')->update(['value' => 60000, 'min_spend_cents' => 250000]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};
