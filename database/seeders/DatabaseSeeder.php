<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@reset.store'],
            [
                'name' => 'RESET Administrator',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'phone' => '+855 12 888 999',
                'email_verified_at' => now(),
            ]
        );

        // Storefront customer account (normal shopping account)
        User::firstOrCreate(
            ['email' => 'kongchhunyt@gmail.com'],
            [
                'name' => 'Kongchhun',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'phone' => '0966556556',
                'email_verified_at' => now(),
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@reset.store'],
            [
                'name' => 'Dara Sok',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'phone' => '+855 12 777 888',
                'email_verified_at' => now(),
            ]
        );

        // Saved customer address
        Address::firstOrCreate(
            ['user_id' => $customer->id, 'type' => 'shipping'],
            [
                'first_name' => 'Dara',
                'last_name' => 'Sok',
                'phone' => '+855 12 777 888',
                'province' => 'Phnom Penh',
                'district' => 'Daun Penh',
                'commune' => 'Phsar Kandal 1',
                'street_address' => 'Street 130, House 45B',
                'postal_code' => '120204',
                'is_default' => true,
            ]
        );

        // 2. Seed Store Settings
        $settings = [
            'store_name' => 'RESET',
            'store_tagline' => 'Start fresh.',
            'store_tagline_km' => 'ចាប់ផ្តើមថ្មី។ ភាពសាមញ្ញនៃស្ទាយទំនើប។',
            'store_email' => 'support@reset.store',
            'store_phone' => '+855 23 999 123',
            'store_address' => 'Street 240, Daun Penh, Phnom Penh, Cambodia',
            'exchange_rate' => 4100,
            'free_shipping_threshold' => 200000, // 200,000 ៛
            'announcement_banner_en' => 'Free delivery on orders over 200,000 ៛ | Instant QR checkout with Bakong KHQR',
            'announcement_banner_km' => 'ដឹកជញ្ជូនឥតគិតថ្លៃសម្រាប់ការបញ្ជាទិញចាប់ពី 200,000 ៛ ឡើងទៅ | ទូទាត់រហ័សជាមួយបាគង KHQR',
            'province_shipping_fees' => [
                'Phnom Penh' => 0,
                'Kandal' => 0,
                'Siem Reap' => 0,
                'Battambang' => 0,
                'Sihanoukville' => 0,
                'Kampot' => 0,
                'Kep' => 0,
                'Takeo' => 0,
                'Kampong Cham' => 0,
                'Kampong Chhnang' => 0,
                'Kampong Speu' => 0,
                'Kampong Thom' => 0,
                'Koh Kong' => 0,
                'Kratie' => 0,
                'Mondulkiri' => 0,
                'Oddar Meanchey' => 0,
                'Pailin' => 0,
                'Preah Vihear' => 0,
                'Prey Veng' => 0,
                'Pursat' => 0,
                'Ratanakiri' => 0,
                'Stung Treng' => 0,
                'Svay Rieng' => 0,
                'Tboung Khmum' => 0,
                'Banteay Meanchey' => 0,
            ],
        ];

        foreach ($settings as $key => $val) {
            Setting::set($key, $val, 'general');
        }

        // 3. Seed Discount Codes
        Discount::firstOrCreate(
            ['code' => 'RESET10'],
            [
                'type' => DiscountType::Percentage,
                'value' => 10,
                'min_spend_cents' => 100000, // 100,000 ៛
                'max_uses' => 500,
                'used_count' => 14,
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ]
        );

        Discount::firstOrCreate(
            ['code' => 'FREESHIP'],
            [
                'type' => DiscountType::FreeShipping,
                'value' => 0,
                'min_spend_cents' => 0, // Free shipping for testing anytime
                'max_uses' => 300,
                'used_count' => 45,
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        Discount::firstOrCreate(
            ['code' => 'STARTFRESH'],
            [
                'type' => DiscountType::FixedAmount,
                'value' => 60000, // 60,000 ៛
                'min_spend_cents' => 250000, // 250,000 ៛
                'max_uses' => 200,
                'used_count' => 8,
                'expires_at' => now()->addMonths(4),
                'is_active' => true,
            ]
        );

        // 4. Seed Categories
        $categoriesData = [
            [
                'name' => 'Tees & Tops',
                'name_km' => 'អាវយឺត និងអាវលើ',
                'slug' => 'tees-and-tops',
                'description' => 'Heavyweight combed organic cotton tees, crafted boxy silhouettes, and elevated basics.',
                'description_km' => 'អាវយឺតសាច់ក្រាស់ទន់កម្រិតខ្ពស់ ឌីហ្សាញទាន់សម័យ និងមានផាសុកភាពខ្ពស់។',
                'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1000&q=80',
                'sort_order' => 1,
            ],
            [
                'name' => 'Hoodies & Outerwear',
                'name_km' => 'អាវរងា និងអាវក្រៅ',
                'slug' => 'hoodies-and-outerwear',
                'description' => 'Architectural fleeces, 450 GSM loopback cotton hoodies, and technical windbreakers.',
                'description_km' => 'អាវរងាសាច់ក្រាស់ 450 GSM និងអាវក្រៅស្ទាយបែបទំនើប ផ្តល់ភាពកក់ក្តៅ និងសោភ័ណភាព។',
                'image_url' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=1000&q=80',
                'sort_order' => 2,
            ],
            [
                'name' => 'Pants & Bottoms',
                'name_km' => 'ខោវែង និងខោខ្លី',
                'slug' => 'pants-and-bottoms',
                'description' => 'Tailored cargo pants, heavyweight sweatpants, relaxed pleat trousers, and modular shorts.',
                'description_km' => 'ខោខូវប៊យ ខោការហ្គោ និងខោកីឡាសាច់ក្រាស់ ស័ក្តិសមសម្រាប់ជីវិតប្រចាំថ្ងៃ។',
                'image_url' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1000&q=80',
                'sort_order' => 3,
            ],
            [
                'name' => 'Accessories & Headwear',
                'name_km' => 'គ្រឿងតុបតែង និងមួក',
                'slug' => 'accessories-and-headwear',
                'description' => 'Structured low-profile caps, heavy canvas tote bags, organic beanies, and leather goods.',
                'description_km' => 'មួកកាតឹប កាបូបក្រណាត់ស្វិតរឹងមាំ និងគ្រឿងតុបតែងបន្ថែមភាពទាក់ទាញ។',
                'image_url' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=1000&q=80',
                'sort_order' => 4,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::firstOrCreate(['slug' => $c['slug']], $c);
        }

        // 5. Seed 24 Realistic RESET Products with Variants, Images & Stock
        $productsData = [
            // Tees (6 products)
            [
                'category' => 'tees-and-tops',
                'name' => 'RESET Raw Edge Heavyweight Tee',
                'name_km' => 'អាវយឺត RESET សាច់ក្រាស់គែមឆៅ',
                'slug' => 'reset-raw-edge-heavyweight-tee',
                'sku_prefix' => 'RST-TEE-01',
                'tagline' => '280 GSM combed cotton with subtle distressed hems.',
                'tagline_km' => 'សាច់កប្បាសសុទ្ធ 280 GSM ជាមួយការកាត់ថ្នេរគែមឆៅដ៏ទាក់ទាញ។',
                'description' => 'The cornerstone of the RESET silhouette. Engineered with 100% long-staple organic cotton at 280 GSM. Features a drop-shoulder cut, reinforced ribbed crewneck, and precision raw-edge finishes that soften beautifully with every wash.',
                'description_km' => 'ជាម៉ូដស្នូលដ៏លេចធ្លោរបស់ RESET។ ផលិតឡើងពីសាច់កប្បាសធម្មជាតិសុទ្ធ 100% កម្រាស់ 280 GSM។ កាត់ដេរទម្លាក់ស្មា ផាសុកភាពខ្ពស់ និងរឹងមាំយូរអង្វែង។',
                'base_price_cents' => 100,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Washed Olive', 'code' => '#555843'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Boxy Minimalist Pocket Tee',
                'name_km' => 'អាវយឺតមានហោប៉ៅបែបតិចតួចនិយម',
                'slug' => 'boxy-minimalist-pocket-tee',
                'category' => 'tees-and-tops',
                'sku_prefix' => 'RST-TEE-02',
                'tagline' => 'Seamless chest pocket on relaxed architectural proportions.',
                'tagline_km' => 'ហោប៉ៅដើមទ្រូងបិទជិតយ៉ាងប្រណិត លើទម្រង់កាត់ដេរធំល្មម។',
                'description' => 'Designed for effortless everyday wear. Seamless flatlock stitching, bounded chest pocket, and a clean structured drape that maintains shape throughout the day.',
                'description_km' => 'រចនាឡើងសម្រាប់ការស្លៀកពាក់ប្រចាំថ្ងៃយ៉ាងងាយស្រួល។ ការដេរបង្កប់ស្អាតល្អ រក្សាទម្រង់អាវមិនឱ្យយារធ្លាក់។',
                'base_price_cents' => 3400,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1581655353564-df123a1eb820?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Concrete Gray', 'code' => '#8C8D8F'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'RESET Monogram Oversized Tee',
                'name_km' => 'អាវយឺតអក្សរកាត់ RESET បែបទូលាយ',
                'slug' => 'reset-monogram-oversized-tee',
                'category' => 'tees-and-tops',
                'sku_prefix' => 'RST-TEE-03',
                'tagline' => 'High-density micro silicone chest emblem.',
                'tagline_km' => 'ឡូហ្គោអក្សរកាត់ស៊ីលីកូនតូចលើដើមទ្រូងបែបប្រណិត។',
                'description' => 'Subtle branding for discerning wearers. Featuring a tonal rubberized emblem on the left chest and a relaxed silhouette with wide half-length sleeves.',
                'description_km' => 'បង្កប់ស្លាកសញ្ញាយ៉ាងទាក់ទាញ។ ផលិតពីក្រណាត់កប្បាសទន់ត្រជាក់ ស័ក្តិសមជាមួយអាកាសធាតុក្តៅនៅកម្ពុជា។',
                'base_price_cents' => 3600,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Charcoal Heather', 'code' => '#363738'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Acid Wash Distressed Tee',
                'name_km' => 'អាវយឺតពណ៌រសាត់បែប Vintage',
                'slug' => 'acid-wash-distressed-tee',
                'category' => 'tees-and-tops',
                'sku_prefix' => 'RST-TEE-04',
                'tagline' => 'Hand-processed mineral wash with individual character.',
                'tagline_km' => 'ឆ្លងកាត់ការបោកជ្រលក់បែបបុរាណ ផ្តល់ពណ៌ប្លែកដាច់គេគ្រប់អាវ។',
                'description' => 'Each piece undergoes a proprietary cold-pigment wash ensuring no two shirts are identical. Softened pre-shrunk cotton with micro-frayed ribbing.',
                'description_km' => 'អាវនីមួយៗទទួលបានការជ្រលក់ពណ៌ពិសេស។ សាច់ក្រណាត់មិនរួញ មិនបែកព្រុយ ងាយស្រួលថែទាំ។',
                'base_price_cents' => 4200,
                'compare_at_price_cents' => 4800,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Washed Black', 'code' => '#2B2B2B'],
                    ['name' => 'Clay Sand', 'code' => '#C4A482'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Heavy Long Sleeve Thermal Top',
                'name_km' => 'អាវដៃវែងក្រណាត់រឹងមាំ Thermal',
                'slug' => 'heavy-long-sleeve-thermal-top',
                'category' => 'tees-and-tops',
                'sku_prefix' => 'RST-TEE-05',
                'tagline' => 'Waffle knit texture with reinforced extended cuffing.',
                'tagline_km' => 'ក្រណាត់ត្បាញក្រឡា Waffle ផ្តល់កម្តៅល្មម និងខ្យល់ចេញចូលស្រួល។',
                'description' => 'A versatile layering essential. Knitted from 320 GSM honeycomb cotton waffle with elasticized thumbhole cuffs and a clean elongated hem.',
                'description_km' => 'អាវដៃវែងបែបទំនើប អាចពាក់តែឯង ឬពាក់ពីខាងក្នុងអាវក្រៅបានយ៉ាងឡូយឆាយ។',
                'base_price_cents' => 4400,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1581655353564-df123a1eb820?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Raw Silk Blend Mock Neck Top',
                'name_km' => 'អាវកកណ្តាលសរសៃសូត្រលាយកប្បាស',
                'slug' => 'raw-silk-blend-mock-neck-top',
                'category' => 'tees-and-tops',
                'sku_prefix' => 'RST-TEE-06',
                'tagline' => 'Subtle high collar with a relaxed, modern drape.',
                'tagline_km' => 'កអាវឡើងបន្តិចបែប Modern Mock Neck ទាន់សម័យខ្ពស់។',
                'description' => 'Elevating the everyday tee with an 80/20 organic cotton and raw silk blend. Features a 1.5-inch structured mock collar and seamless blind hem stitching.',
                'description_km' => 'ការរួមបញ្ចូលគ្នារវាងកប្បាសធម្មជាតិ និងសូត្រឆៅ ផ្តល់អារម្មណ៍ទន់រលោងពិសេសលើស្បែក។',
                'base_price_cents' => 4800,
                'compare_at_price_cents' => 5500,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1606167881508-751a9b0a9594?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1623183949963-0557c9532763?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Washed Sage', 'code' => '#7A8B7B'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],

            // Hoodies & Outerwear (6 products)
            [
                'name' => 'Heavyweight 450 GSM Boxy Hoodie',
                'name_km' => 'អាវរងា Hoodie សាច់ក្រាស់ 450 GSM',
                'slug' => 'heavyweight-450-gsm-boxy-hoodie',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-01',
                'tagline' => 'Ultra-dense French terry with double-layer crossover hood.',
                'tagline_km' => 'សាច់ក្រណាត់ French terry កម្រាស់ 450 GSM ជាមួយមួកពីរជាន់រឹងមាំ។',
                'description' => 'The ultimate cold-weather streetwear staple. Made from heavyweight 450 GSM French terry cotton. Free from drawstrings for a sleek minimalist look, featuring dropped shoulders, hidden kangaroo pocket entry, and 2x2 ribbed side panel inserts.',
                'description_km' => 'អាវរងាស្ទាយ streetwear ដ៏ពេញនិយមបំផុត។ គ្មានខ្សែទាញដើម្បីរក្សាភាពសាមញ្ញ ហោប៉ៅបង្កប់ និងសាច់ក្រណាត់ក្រាស់ធ្ងន់ល្អឥតខ្ចោះ។',
                'base_price_cents' => 7800,
                'compare_at_price_cents' => 8800,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Charcoal Slate', 'code' => '#2F353B'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Minimalist Half-Zip Wind Pullover',
                'name_km' => 'អាវក្រៅខ្សែរូតពាក់កណ្តាលការពារខ្យល់',
                'slug' => 'minimalist-half-zip-wind-pullover',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-02',
                'tagline' => 'Water-repellent ripstop nylon with matte metallic zipper.',
                'tagline_km' => 'ក្រណាត់ Ripstop មិនជ្រាបទឹកស្រាលស្រួល ជាមួយខ្សែរូតលោហធាតុម៉ាត់។',
                'description' => 'Engineered for unpredictable city weather. Ultra-lightweight Japanese ripstop nylon treated with durable water repellent (DWR). Stand collar with YKK matte finish half-zip and bungee adjustable waist.',
                'description_km' => 'ផលិតឡើងពីក្រណាត់នីឡុង Ripstop ជប៉ុនស្រាល មិនជ្រាបទឹក កអាវខ្ពស់ការពារខ្យល់ និងអាចរឹតចង្កេះបាន។',
                'base_price_cents' => 6800,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1542736619-c0911cb5eb12?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Desert Sand', 'code' => '#C4B59D'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Oversized Raw Crewneck Sweatshirt',
                'name_km' => 'អាវរងាកមូល Oversized សាច់ក្រាស់',
                'slug' => 'oversized-raw-crewneck-sweatshirt',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-03',
                'tagline' => 'Vintage looped French terry with heavy rib trims.',
                'tagline_km' => 'ក្រណាត់ French Terry 400 GSM ជាមួយការដេរជាយរឹងមាំមិនយារ។',
                'description' => 'Clean lines with vintage athletic proportions. Features a reinforced triangular collar insert, thick 3-inch ribbed cuffs, and an enzyme wash finish for a lived-in drape.',
                'description_km' => 'ភាពសាមញ្ញដ៏ស្រស់ស្អាត។ កាត់ដេរស្ទីលកីឡាបុរាណ ថ្នេរដេររឹងមាំ មិនយារធ្លាក់ពេលបោកគក់។',
                'base_price_cents' => 6400,
                'compare_at_price_cents' => 7200,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1523956468692-1e219561ea46?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1631747059938-98105dad94c6?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Heather Gray', 'code' => '#999A9C'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'RESET Structured Canvas Work Jacket',
                'name_km' => 'អាវក្រៅក្រណាត់ Canvas ស្ទីល Workwear',
                'slug' => 'reset-structured-canvas-work-jacket',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-04',
                'tagline' => '14oz duck canvas with corduroy collar detailing.',
                'tagline_km' => 'ក្រណាត់ Canvas 14oz ស្វិតធន់ ជាមួយកអាវក្រណាត់ corduroy ដ៏ថ្លៃថ្នូរ។',
                'description' => 'Utility redefined for modern street aesthetics. Crafted from heavy-duty 14oz cotton duck canvas, custom RESET antique silver snaps, and a soft corduroy spread collar.',
                'description_km' => 'អាវក្រៅស្ទីលជាងដ៏រឹងមាំ។ ក្រណាត់ Duck Canvas 14oz ជាប់ធន់ខ្លាំង គ្រាប់ចុចប្រាក់បុរាណ និងកអាវ Corduroy ទន់។',
                'base_price_cents' => 9500,
                'compare_at_price_cents' => 11000,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Washed Black', 'code' => '#222222'],
                    ['name' => 'Tobacco Tan', 'code' => '#8C5A2B'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Full-Zip Heavy Fleece Overshirt',
                'name_km' => 'អាវក្រៅរូត Fleece ផ្តល់កម្តៅពិសេស',
                'slug' => 'full-zip-heavy-fleece-overshirt',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-05',
                'tagline' => 'High-pile bonded sherpa fleece with nylon storm flap.',
                'tagline_km' => 'សាច់រោមចៀម Sherpa ក្រាស់ទន់ ជាមួយបន្ទះនីឡុងការពារខ្យល់។',
                'description' => 'Plush high-pile bonded fleece lined with breathable mesh. Features two angled welt chest pockets and dual-slider two-way YKK hardware.',
                'description_km' => 'រោមចៀមសំយោគគុណភាពខ្ពស់ ផ្តល់ភាពកក់ក្តៅ និងទន់ស្រួល ជាមួយខ្សែរូតពីរផ្លូវងាយស្រួលប្រើប្រាស់។',
                'base_price_cents' => 8400,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1622023585398-b3069fa2a1e1?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1790575547115-6c15faab4c05?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Technical Bomber Jacket',
                'name_km' => 'អាវក្រៅ Technical Bomber ទំនើប',
                'slug' => 'technical-bomber-jacket',
                'category' => 'hoodies-and-outerwear',
                'sku_prefix' => 'RST-HOD-06',
                'tagline' => 'Structured satin nylon shell with lightweight insulation.',
                'tagline_km' => 'សាច់ក្រណាត់ satin nylon រលោងថ្លៃថ្នូរ ជាមួយស្រទាប់ការពារត្រជាក់។',
                'description' => 'A clean reimagining of the classic MA-1 bomber. Minimalist monochromatic trims, hidden sleeve zip pocket, and water-repellent shell.',
                'description_km' => 'អាវ Bomber រចនាឡើងវិញដោយដកភាពរញ៉េរញ៉ៃចេញ។ រលោង ស្រាល មិនជ្រាបទឹក និងមើលទៅថ្លៃថ្នូរគ្រប់កាលៈទេសៈ។',
                'base_price_cents' => 9200,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1549399239-fb3c102d3d71?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Military Olive', 'code' => '#474D3A'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],

            // Pants & Bottoms (6 products)
            [
                'name' => 'Wide-Leg Pleated Cotton Trousers',
                'name_km' => 'ខោជើងធំផ្នត់មុខសាច់កប្បាស',
                'slug' => 'wide-leg-pleated-cotton-trousers',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-01',
                'tagline' => 'Double front pleats with a relaxed straight fluid drape.',
                'tagline_km' => 'ផ្នត់មុខពីរជាន់ ជើងធំត្រង់ទូលាយ ផ្តល់ភាពថ្លៃថ្នូរ និងទាន់សម័យ។',
                'description' => 'Tailored streetwear tailoring at its peak. Cut from durable 300 GSM cotton twill with deep front double pleats, interior drawstring waistband, and a relaxed wide leg profile.',
                'description_km' => 'ខោជើងធំដ៏ពេញនិយមក្នុងចំណោមយុវវ័យសម័យថ្មី។ សាច់ក្រណាត់ twill 300 GSM ធ្លាក់ស្អាត មានខ្សែចងខាងក្នុងចង្កេះ។',
                'base_price_cents' => 6500,
                'compare_at_price_cents' => 7500,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Deep Navy', 'code' => '#1C2331'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'RESET Modular Utility Cargo Pant',
                'name_km' => 'ខោការហ្គោ RESET មានហោប៉ៅច្រើន',
                'slug' => 'reset-modular-utility-cargo-pant',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-02',
                'tagline' => 'Bellowed 3D cargo pockets with concealed snap closures.',
                'tagline_km' => 'ហោប៉ៅ 3D ចំហៀងធំទូលាយ ជាមួយគ្រាប់ចុចលាក់ខាងក្នុង។',
                'description' => 'Tactical precision meets minimalist lines. Engineered from stretch-reinforced ripstop fabric with articulate darted knee paneling and adjustable toggle hems.',
                'description_km' => 'ខោការហ្គោស្ទាយ tactical បែបស្អាតសាមញ្ញ។ ក្រណាត់ ripstop យឺតស្រួល ជង្គង់កាត់ដេរងាយស្រួលបត់ និងជាយជើងមានគន្លឹះរឹតបាន។',
                'base_price_cents' => 7200,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1789110853303-b90571472139?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1789110853502-c92e0bde13d4?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Washed Charcoal', 'code' => '#333333'],
                    ['name' => 'Tactical Olive', 'code' => '#404537'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Heavy French Terry Lounge Sweatpant',
                'name_km' => 'ខោកីឡា Sweatpant សាច់ក្រាស់ទន់',
                'slug' => 'heavy-french-terry-lounge-sweatpant',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-03',
                'tagline' => '420 GSM looped cotton fleece with elasticized cuffed ankles.',
                'tagline_km' => 'សាច់កប្បាស 420 GSM ទន់កក់ក្តៅ និងជើងកៅស៊ូរឹតស្អាត។',
                'description' => 'Uncompromising comfort for studio days and travel. Elastic waistband with chunky natural cotton drawstrings, deep phone pockets, and back patch pocket.',
                'description_km' => 'ខោកីឡាដ៏មានផាសុកភាពបំផុត។ ខ្សែចងកប្បាសធម្មជាតិ ហោប៉ៅជ្រៅដាក់ទូរស័ព្ទមិនធ្លាក់ និងសាច់ក្រណាត់ក្រាស់ល្អ។',
                'base_price_cents' => 5800,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1789110853665-11a03e391f04?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1789110853737-e30e04cda364?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Heather Gray', 'code' => '#999A9C'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Relaxed Heavy Linen Drawstring Pant',
                'name_km' => 'ខោក្រណាត់កប្បាសលាយ Linen ត្រជាក់ស្រួល',
                'slug' => 'relaxed-heavy-linen-drawstring-pant',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-04',
                'tagline' => 'Breathable European linen blend tailored for tropical climates.',
                'tagline_km' => 'ក្រណាត់ Linen អឺរ៉ុប ត្រជាក់ស្រួល ស័ក្តិសមឥតខ្ចោះសម្រាប់អាកាសធាតុប្រទេសកម្ពុជា។',
                'description' => 'Natural breathability with substantial drape. Crafted from a 55% linen, 45% cotton blend with relaxed straight cut legs and horn button closures.',
                'description_km' => 'ការរួមបញ្ចូលរវាង Linen 55% និងកប្បាស 45% ផ្តល់ភាពត្រជាក់ស្រួល មិនហប់ និងមើលទៅទាន់សម័យបែបធម្មជាតិ។',
                'base_price_cents' => 6200,
                'compare_at_price_cents' => 6900,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1531366936337-7c912a4589a7?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Natural Flax', 'code' => '#DDD5C7'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Minimalist Everyday Chino Shorts',
                'name_km' => 'ខោខ្លី Chino សាមញ្ញទាន់សម័យ',
                'slug' => 'minimalist-everyday-chino-shorts',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-05',
                'tagline' => '7-inch inseam cotton twill with clean welt pockets.',
                'tagline_km' => 'ប្រវែង 7 អ៊ីញល្មមលើជង្គង់ សាច់ក្រណាត់ Twill ជាប់ធន់។',
                'description' => 'The definitive summer short. Clean above-the-knee 7-inch length, stretch cotton twill for mobility, and a tailored flat front waistband.',
                'description_km' => 'ខោខ្លីដ៏ល្អឥតខ្ចោះសម្រាប់រដូវក្តៅ។ ប្រវែងលើជង្គង់ 7 អ៊ីញ ក្រណាត់យឺតបន្តិចងាយស្រួលធ្វើចលនា។',
                'base_price_cents' => 4200,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1789110853802-905f3d5c654c?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Khaki Tan', 'code' => '#C3B091'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Heavy Terry Boxy Sweat Shorts',
                'name_km' => 'ខោខ្លី Sweatshirt សាច់ក្រាស់ទន់',
                'slug' => 'heavy-terry-boxy-sweat-shorts',
                'category' => 'pants-and-bottoms',
                'sku_prefix' => 'RST-PNT-06',
                'tagline' => '400 GSM unbrushed loopback cotton with raw-cut hem.',
                'tagline_km' => 'កប្បាស 400 GSM គែមឆៅបែប streetwear ទំនើប។',
                'description' => 'Cut wide and breezy. Heavyweight French terry with raw cut hems, extra-long natural drawstrings, and reinforced side pockets.',
                'description_km' => 'ខោខ្លីសាច់អាវរងា ជើងទូលាយស្រួលពាក់ គែមឆៅបែប streetwear ជាមួយខ្សែចងវែងស្អាត។',
                'base_price_cents' => 4500,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1789110853802-905f3d5c654c?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Raw Bone', 'code' => '#EAE6DF'],
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],

            // Accessories & Headwear (6 products)
            [
                'name' => 'RESET Heavy Canvas Daily Tote',
                'name_km' => 'កាបូបក្រណាត់ Canvas ស្វិតធន់ RESET',
                'slug' => 'reset-heavy-canvas-daily-tote',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-01',
                'tagline' => '16oz rugged organic canvas with reinforced boxed bottom.',
                'tagline_km' => 'ក្រណាត់ Canvas 16oz ស្វិតមាំខ្លាំង បាតកាបូបរឹងមាំផ្ទុកទំនិញបានច្រើន។',
                'description' => 'Engineered to carry your daily essentials without losing shape. Made from 16oz organic cotton duck canvas with dual shoulder straps, interior zipped laptop sleeve, and subtle matte tonal RESET screenprint.',
                'description_km' => 'កាបូបស្ពាយស្មាដ៏រឹងមាំសម្រាប់ប្រើប្រាស់រាល់ថ្ងៃ។ មានថតខាងក្នុងអាចដាក់កុំព្យូទ័រយួរដៃបាន ថ្នេរដេររឹងមាំពិសេស។',
                'base_price_cents' => 3200,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1535981444082-2a5dc0548ef3?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Raw Natural', 'code' => '#EDE7DC'],
                    ['name' => 'Pitch Black', 'code' => '#111111'],
                ],
                'sizes' => ['One Size'],
            ],
            [
                'name' => 'Unstructured 6-Panel Low Profile Cap',
                'name_km' => 'មួកកាតឹប 6-Panel បែបស្រាលស្រួលពាក់',
                'slug' => 'unstructured-6-panel-low-profile-cap',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-02',
                'tagline' => 'Washed cotton twill with brass slider strapback.',
                'tagline_km' => 'សាច់កប្បាសបោកទន់ ជាមួយខ្សែរឹតធ្វើពីលង្ហិនបុរាណ។',
                'description' => 'The perfect unstructured dad hat. Custom low-profile crown, pre-curved visor, and adjustable brass buckle closure with tuck-in leather strap.',
                'description_km' => 'មួកកាតឹបស្ទាយបុរាណទាន់សម័យ។ សាច់ក្រណាត់ទន់ស្រួលក្បាល ខ្សែរឹតស្បែក និងកិបលង្ហិនកែតម្រូវទំហំបាន។',
                'base_price_cents' => 2800,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Washed Olive', 'code' => '#555843'],
                    ['name' => 'Raw Chalk', 'code' => '#F4F2EB'],
                ],
                'sizes' => ['One Size'],
            ],
            [
                'name' => 'Merino Wool Ribbed Beanie',
                'name_km' => 'មួកអំបោះ Wool ត្បាញម៉ដ្ឋ',
                'slug' => 'merino-wool-ribbed-beanie',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-03',
                'tagline' => '100% extra-fine Merino wool with fold-over cuff.',
                'tagline_km' => 'អំបោះ Merino wool 100% ទន់កក់ក្តៅ មិនរមាស់។',
                'description' => 'Spun from ultra-soft 19.5-micron non-mulesed Merino wool. Naturally temperature-regulating and odor-resistant with a clean 3-inch foldover brim.',
                'description_km' => 'ត្បាញពីសរសៃអំបោះ Merino wool សុទ្ធ។ ផ្តល់ភាពកក់ក្តៅខ្ពស់ ទន់មិនរមាស់ និងមិនធុំក្លិន។',
                'base_price_cents' => 3000,
                'compare_at_price_cents' => 3600,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1576871337622-98d48d1cf531?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1683086934601-fcc3c13849c3?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Onyx Black', 'code' => '#111111'],
                    ['name' => 'Charcoal Heather', 'code' => '#363738'],
                ],
                'sizes' => ['One Size'],
            ],
            [
                'name' => 'RESET Minimalist Leather Cardholder',
                'name_km' => 'កាបូបកាតស្បែកសុទ្ធ RESET',
                'slug' => 'reset-minimalist-leather-cardholder',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-04',
                'tagline' => 'Full-grain vegetable-tanned leather with burnished edges.',
                'tagline_km' => 'ស្បែកគោសុទ្ធ vegetable-tanned ជាមួយគែមខាត់រលោងបែបហត្ថកម្ម។',
                'description' => 'Handcrafted from Italian full-grain vegetable-tanned leather that develops an organic patina over time. 4 exterior card slots and a central folded cash pocket.',
                'description_km' => 'ផលិតដោយដៃពីស្បែកគោអ៊ីតាលីសុទ្ធ។ រចនាឡើងយ៉ាងស្តើង ងាយស្រួលដាក់តាមហោប៉ៅ ដាក់កាតបាន 4 និងលុយក្រដាសនៅកណ្តាល។',
                'base_price_cents' => 3500,
                'compare_at_price_cents' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1676276549687-6f51f6e406b0?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Matte Black', 'code' => '#111111'],
                    ['name' => 'Caramel Brown', 'code' => '#7F4F24'],
                ],
                'sizes' => ['One Size'],
            ],
            [
                'name' => 'Technical Crossbody Sling Bag',
                'name_km' => 'កាបូបស្ពាយចំហៀង Technical Sling Bag',
                'slug' => 'technical-crossbody-sling-bag',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-05',
                'tagline' => 'Waterproof Cordura nylon with quick-release Fidlock buckle.',
                'tagline_km' => 'ក្រណាត់ Cordura មិនជ្រាបទឹក ជាមួយគន្លឹះម៉ាញ៉េទិច Fidlock ទំនើប។',
                'description' => 'Everyday carry elevated. Featuring weather-resistant Cordura 500D nylon, aquaguard waterproof zippers, and an ergonomic quick-adjust crossbody strap.',
                'description_km' => 'កាបូបស្ពាយចំហៀងដ៏ពេញនិយមសម្រាប់អ្នកដើរក្នុងទីក្រុង។ ក្រណាត់ Cordura មិនជ្រាបទឹក ខ្សែរូតបិទជិត និងគន្លឹះដោះរហ័ស។',
                'base_price_cents' => 4800,
                'compare_at_price_cents' => 5400,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_best_seller' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1605733513597-a8f8341084e6?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Stealth Black', 'code' => '#111111'],
                    ['name' => 'Battleship Gray', 'code' => '#6A7179'],
                ],
                'sizes' => ['One Size'],
            ],
            [
                'name' => 'Heavyweight Combed Ribbed Socks (3-Pack)',
                'name_km' => 'ស្រោមជើងសាច់កប្បាសក្រាស់ (កញ្ចប់ 3 គូ)',
                'slug' => 'heavyweight-combed-ribbed-socks-3-pack',
                'category' => 'accessories-and-headwear',
                'sku_prefix' => 'RST-ACC-06',
                'tagline' => 'Cushioned terry footbed with anti-slip arch support.',
                'tagline_km' => 'ទ្រនាប់បាតជើងក្រាស់ទន់ ជួយទប់លំនឹងមិនឱ្យរអិលពេលដើរ។',
                'description' => 'Luxury for your feet. Three pairs of cushioned crew socks made from combed long-staple cotton with reinforced heel/toe and breathable mesh arch venting.',
                'description_km' => 'កញ្ចប់ស្រោមជើង 3 គូ។ ផលិតពីកប្បាសទន់ក្រាស់ ទ្រនាប់បាតជើងទន់ និងកៅស៊ូស្វិតមិនងាយយារ។',
                'base_price_cents' => 2200,
                'compare_at_price_cents' => null,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_best_seller' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1586350977771-b3b0abd50c82?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1585499583264-491df5142e83?auto=format&fit=crop&w=1000&q=80',
                ],
                'colors' => [
                    ['name' => 'Chalk / Bone / Onyx', 'code' => '#EDE7DC'],
                ],
                'sizes' => ['One Size'],
            ],
        ];

        foreach ($productsData as $data) {
            $cat = $categories[$data['category']];

            $product = Product::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $cat->id,
                    'name' => $data['name'],
                    'name_km' => $data['name_km'],
                    'sku_prefix' => $data['sku_prefix'],
                    'tagline' => $data['tagline'],
                    'tagline_km' => $data['tagline_km'],
                    'description' => $data['description'],
                    'description_km' => $data['description_km'],
                    'details' => [
                        'fabric' => 'Heavyweight 100% Organic Combed Cotton / Japanese Technical Textiles',
                        'care' => 'Machine wash cold with like colors, inside out. Do not tumble dry. Cool iron if needed.',
                        'fit' => 'Modern relaxed boxy fit. Order your usual size for streetwear drape or size down for tailored fit.',
                    ],
                    'base_price_cents' => $data['slug'] === 'reset-raw-edge-heavyweight-tee' ? 100 : (int) round(($data['base_price_cents'] / 100) * 4100),
                    'compare_at_price_cents' => $data['compare_at_price_cents'] ? (int) round(($data['compare_at_price_cents'] / 100) * 4100) : null,
                    'is_featured' => $data['is_featured'],
                    'is_new_arrival' => $data['is_new_arrival'],
                    'is_best_seller' => $data['is_best_seller'],
                    'is_published' => true,
                ]
            );

            // Images
            ProductImage::where('product_id', $product->id)->delete();
            foreach ($data['images'] as $idx => $imgUrl) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imgUrl,
                    'alt_text' => "{$product->name} photo ".($idx + 1),
                    'is_primary' => $idx === 0,
                    'sort_order' => $idx,
                ]);
            }

            // Variants (Size x Color)
            foreach ($data['colors'] as $c) {
                foreach ($data['sizes'] as $sz) {
                    $sku = strtoupper($data['sku_prefix'].'-'.substr($sz, 0, 2).'-'.substr(Str::slug($c['name']), 0, 3));

                    ProductVariant::firstOrCreate(
                        ['sku' => $sku],
                        [
                            'product_id' => $product->id,
                            'size' => $sz,
                            'color' => $c['name'],
                            'color_code' => $c['code'],
                            'price_cents' => $product->base_price_cents,
                            'stock_quantity' => rand(15, 45),
                            'reserved_quantity' => 0,
                        ]
                    );
                }
            }

            // Sample Reviews
            Review::firstOrCreate(
                ['product_id' => $product->id, 'user_id' => $customer->id],
                [
                    'rating' => 5,
                    'title' => 'Incredible fabric weight and clean drape',
                    'body' => 'The 280 GSM cotton feels luxurious and holds its shape even after multiple washes. Fast delivery in Phnom Penh within 24 hours. Bakong KHQR payment was seamless!',
                    'is_verified_purchase' => true,
                    'is_approved' => true,
                ]
            );
        }
    }
}
