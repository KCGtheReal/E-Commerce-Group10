<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\ApiToken;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Discount;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\CatalogCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeedScenarioCommand extends Command
{
    protected $signature = 'app:seed-scenario
        {scenario : clean, demo, or stress}
        {--database= : Target SQLite file path or :memory: (recommended for clean/stress QA)}
        {--force-destructive : Required when running against live database/database.sqlite outside testing}';

    protected $description = 'Run deterministic Tri-Scenario seeding (clean, demo, stress) with live SQLite protection';

    public function handle(): int
    {
        $scenario = strtolower(trim((string) $this->argument('scenario')));
        if (! in_array($scenario, ['clean', 'demo', 'stress'], true)) {
            $this->error("Invalid scenario '{$scenario}'. Allowed: clean, demo, stress.");

            return self::FAILURE;
        }

        $targetDatabase = trim((string) ($this->option('database') ?? ''));
        if ($targetDatabase !== '') {
            $this->switchSqliteConnection($targetDatabase);
        }

        $activeDb = (string) config('database.connections.'.config('database.default').'.database');
        $liveDbPath = realpath(database_path('database.sqlite')) ?: database_path('database.sqlite');
        $resolvedActiveDb = $activeDb === ':memory:' ? ':memory:' : (realpath($activeDb) ?: $activeDb);

        if (
            $resolvedActiveDb === $liveDbPath
            && ! app()->environment('testing')
            && ! $this->option('force-destructive')
        ) {
            $this->error('Refusing destructive scenario seeding on live database/database.sqlite. Use --database=database/testing_sandbox.sqlite or pass --force-destructive explicitly.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('products')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        $this->wipeTables();

        if ($scenario === 'clean') {
            CatalogCacheService::bumpCatalogEpoch();
            $this->info('Seed scenario [clean] applied: 0 domain records.');

            return self::SUCCESS;
        }

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        if ($scenario === 'stress') {
            $this->seedStressRecords();
        }

        CatalogCacheService::bumpCatalogEpoch();
        $this->info("Seed scenario [{$scenario}] applied cleanly.");

        return self::SUCCESS;
    }

    private function switchSqliteConnection(string $target): void
    {
        $resolved = $target === ':memory:'
            ? ':memory:'
            : (str_starts_with($target, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $target) ? $target : base_path($target));

        if ($resolved !== ':memory:') {
            $dir = dirname($resolved);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (! file_exists($resolved)) {
                touch($resolved);
            }
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $resolved,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    private function wipeTables(): void
    {
        $tables = [
            Payment::class,
            OrderItem::class,
            Order::class,
            CartItem::class,
            Cart::class,
            Review::class,
            Wishlist::class,
            Address::class,
            ApiToken::class,
            ProductImage::class,
            ProductVariant::class,
            Product::class,
            Category::class,
            Discount::class,
            ContactMessage::class,
            NewsletterSubscriber::class,
            Setting::class,
            User::class,
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $modelClass) {
                $modelClass::query()->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Cache::forget('settings.all');
        Cache::memo()->forget('settings.all');
    }

    private function seedStressRecords(): void
    {
        $categories = Category::orderBy('id')->get();
        $reviewer = User::where('role', UserRole::Customer)->first() ?? User::factory()->create([
            'name' => 'Stress Reviewer',
            'email' => 'stress-reviewer@reset.store',
            'password' => Hash::make('password'),
            'role' => UserRole::Customer,
        ]);

        $now = now()->toDateTimeString();
        $categoryIds = $categories->pluck('id')->all();
        $categoryCount = count($categoryIds);

        // Adversarial records for XSS, long non-wrapping text, Unicode/emoji, quotes, and boundary prices
        $adversarialProducts = [
            [
                'category_id' => $categoryIds[0],
                'name' => str_repeat('LONGUNBROKENTOKEN', 13), // 221 chars without spaces
                'name_km' => 'អាវយឺតខ្មែរ_វែង_គ្មានដកឃ្លា_'.str_repeat('ក', 180),
                'slug' => 'stress-long-unbroken-string-tee',
                'sku_prefix' => 'RST-STR-LONG',
                'tagline' => str_repeat('X', 210),
                'tagline_km' => 'សាកល្បងអក្សរវែង 🔥✨',
                'description' => 'Stress test product with a 200+ character unbroken identifier string.',
                'description_km' => 'ការធ្វើតេស្តពាក្យវែងគ្មានដកឃ្លា។',
                'details' => ['fabric' => '280 GSM Cotton', 'care' => 'Cold wash'],
                'base_price_cents' => 0,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'is_published' => true,
            ],
            [
                'category_id' => $categoryIds[1 % $categoryCount],
                'name' => "<script>alert('xss')</script> O'Reilly & \"Quotes\" Tee 🚀🔥",
                'name_km' => "អាវយឺតសាកល្បង <script>alert('xss')</script> 🔥✨🇰🇭",
                'slug' => 'stress-adversarial-xss-unicode-tee',
                'sku_prefix' => 'RST-STR-XSS',
                'tagline' => "Safe escaping check & O'Reilly \"Quotes\" ✨",
                'tagline_km' => 'តេស្តសុវត្ថិភាពអក្សរ និង Emoji 🚀',
                'description' => "Contains benign XSS probe <script>alert('xss')</script> and boundary price 99,999,999 ៛.",
                'description_km' => 'តេស្តសុវត្ថិភាព XSS និងតម្លៃអតិបរមា។',
                'details' => ['fabric' => "O'Reilly \"Organic\" Cotton", 'care' => 'Do not execute scripts'],
                'base_price_cents' => 99999999,
                'compare_at_price_cents' => 99999999,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'is_published' => true,
            ],
        ];

        foreach ($adversarialProducts as $advData) {
            $product = Product::create($advData);
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $product->sku_prefix.'-BLK-M',
                'size' => 'M',
                'color' => 'Onyx Black',
                'color_code' => '#111111',
                'price_cents' => max(100, $product->base_price_cents),
                'stock_quantity' => 50,
                'reserved_quantity' => 0,
            ]);
            Review::create([
                'product_id' => $product->id,
                'user_id' => $reviewer->id,
                'rating' => 5,
                'title' => "<script>alert('xss')</script> & \"Quotes\"",
                'body' => 'Khmer Unicode review អស្ចារ្យ 🔥 '.str_repeat('W', 205),
                'is_verified_purchase' => true,
                'is_approved' => true,
            ]);
        }

        // Bulk insert 500 additional products + variants + images in fast chunks
        $productRows = [];
        for ($i = 1; $i <= 500; $i++) {
            $catId = $categoryIds[$i % $categoryCount];
            $price = 45000 + (($i % 20) * 10000);
            $productRows[] = [
                'category_id' => $catId,
                'name' => sprintf('RESET Stress Capsule Piece #%03d', $i),
                'name_km' => sprintf('ម៉ូដសម្លៀកបំពាក់សាកល្បង #%03d', $i),
                'slug' => sprintf('reset-stress-capsule-%03d', $i),
                'sku_prefix' => sprintf('RST-STR-%03d', $i),
                'tagline' => 'Architectural heavyweight garment stress fixture.',
                'tagline_km' => 'សាច់ក្រណាត់កប្បាសធ្ងន់សម្រាប់ការធ្វើតេស្ត។',
                'description' => 'Stress test product generated for pagination, query plan, and rendering benchmarks.',
                'description_km' => 'ទិន្នន័យសាកល្បងសម្រាប់ការធ្វើតេស្តប្រព័ន្ធ។',
                'details' => json_encode(['fabric' => '280 GSM Cotton', 'care' => 'Cold wash'], JSON_THROW_ON_ERROR),
                'base_price_cents' => $price,
                'compare_at_price_cents' => ($i % 4 === 0) ? $price + 20000 : null,
                'is_featured' => $i <= 10,
                'is_new_arrival' => $i % 3 === 0,
                'is_best_seller' => $i % 5 === 0,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($productRows, 100) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $stressProductIds = Product::where('slug', 'like', 'reset-stress-capsule-%')
            ->orderBy('id')
            ->get(['id', 'sku_prefix', 'base_price_cents']);

        $variantRows = [];
        $imageRows = [];
        foreach ($stressProductIds as $p) {
            $imageRows[] = [
                'product_id' => $p->id,
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',
                'alt_text' => $p->sku_prefix,
                'is_primary' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            foreach (['S', 'M', 'L'] as $sz) {
                $variantRows[] = [
                    'product_id' => $p->id,
                    'sku' => "{$p->sku_prefix}-BLK-{$sz}",
                    'size' => $sz,
                    'color' => 'Onyx Black',
                    'color_code' => '#111111',
                    'price_cents' => $p->base_price_cents,
                    'stock_quantity' => 25,
                    'reserved_quantity' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($imageRows, 150) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }
        foreach (array_chunk($variantRows, 150) as $chunk) {
            DB::table('product_variants')->insert($chunk);
        }
    }
}
