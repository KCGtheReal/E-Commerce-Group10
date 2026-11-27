<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class StoreContentService
{
    /**
     * @var array<int, string>
     */
    public const VALID_PAGE_SLUGS = [
        'about',
        'faq',
        'shipping',
        'size-guide',
        'privacy',
        'terms',
    ];

    /**
     * Build the 5 localized product detail tabs with graceful defaults.
     *
     * @return array<int, array{key: string, title: string, content: string}>
     */
    public function productDetailTabs(Product $product, ?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale);
        $details = is_array($product->details) ? $product->details : [];
        $isKm = $locale === 'km';

        $description = ($isKm && ! empty($product->description_km))
            ? (string) $product->description_km
            : (string) $product->description;

        $fabricDefault = $isKm
            ? 'កប្បាសសរីរាង្គសាច់ក្រាស់ ១០០% កម្រិតខ្ពស់ និងផ្តល់ផាសុកភាព។'
            : '100% Heavyweight Organic Combed Cotton engineered for lasting structure.';

        $careDefault = $isKm
            ? 'បោកគក់ដោយទឹកត្រជាក់ជាមួយពណ៌ស្រដៀងគ្នា និងហាលក្នុងម្លប់។ កុំប្រើម៉ាស៊ីនសម្ងួតក្តៅ។'
            : 'Machine wash cold with like colors, inside out. Hang dry in shade; do not tumble dry.';

        $fitDefault = $isKm
            ? 'ទម្រង់បែបធូរល្មម (Boxy & Relaxed Fit) ស្មាធ្លាក់បន្តិច។ ជ្រើសរើសទំហំតូចជាងមួយលេខប្រសិនបើចូលចិត្តរាងរឹប។'
            : 'Relaxed architectural streetwear cut with slightly dropped shoulders. Size down for a closer fit.';

        $freeThreshold = CurrencyService::formatCents(
            (int) Setting::get('free_shipping_threshold', 200000),
            CurrencyService::current()
        );
        $shippingDefault = $isKm
            ? "ដឹកជញ្ជូនធម្មតា 0 ៛ ទូទាំង ២៥ រាជធានី-ខេត្ត (ឥតគិតថ្លៃលើការបញ្ជាទិញចាប់ពី {$freeThreshold})។ ដឹកជញ្ជូនរហ័សក្នុងភ្នំពេញ 12,000 ៛។"
            : "Standard nationwide delivery across all 25 Cambodian provinces is 0 ៛ (free shipping threshold {$freeThreshold}). Express same-day delivery in Phnom Penh is 12,000 ៛.";

        return [
            [
                'key' => 'description',
                'title' => __('store.details_tabs_desc', [], $locale),
                'content' => $description,
            ],
            [
                'key' => 'fabric',
                'title' => __('store.details_tabs_fabric', [], $locale),
                'content' => ! empty($details['fabric']) ? (string) $details['fabric'] : $fabricDefault,
            ],
            [
                'key' => 'care',
                'title' => __('store.details_tabs_care', [], $locale),
                'content' => ! empty($details['care']) ? (string) $details['care'] : $careDefault,
            ],
            [
                'key' => 'fit',
                'title' => __('store.details_tabs_fit', [], $locale),
                'content' => ! empty($details['fit']) ? (string) $details['fit'] : $fitDefault,
            ],
            [
                'key' => 'shipping',
                'title' => __('store.details_tabs_shipping', [], $locale),
                'content' => ! empty($details['shipping']) ? (string) $details['shipping'] : $shippingDefault,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sizeGuide(?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale);
        $isKm = $locale === 'km';

        return [
            'title' => __('store.size_guide_title', [], $locale),
            'subtitle' => __('store.size_guide_desc', [], $locale),
            'fit_note_title' => $isKm
                ? 'ទម្រង់ពិសេសរបស់យើង៖ រាងធូរល្មម និងទាន់សម័យ'
                : 'Our Signature Fit: Relaxed & Architectural',
            'fit_note_body' => $isKm
                ? 'សម្លៀកបំពាក់ RESET ត្រូវបានរចនាឡើងជាមួយនឹងទម្រង់ស្មាធ្លាក់ និងដើមទ្រូងធំទូលាយល្មម ដើម្បីផ្តល់នូវភាពទាន់សម័យ និងផាសុកភាព។ យើងសូមណែនាំឱ្យជ្រើសរើសទំហំពិតប្រាកដរបស់អ្នកសម្រាប់ទម្រង់បែបធូរស្រួល ឬជ្រើសរើសទំហំតូចជាងមួយលេខប្រសិនបើអ្នកចូលចិត្តទម្រង់រឹបរាងបែបបុរាណ។'
                : 'RESET garments are engineered with relaxed, drop-shoulder silhouettes and wider chests for an effortless streetwear drape. We recommend choosing your true size for our intended relaxed look, or sizing down one size if you prefer a classic, more fitted aesthetic.',
            'tip' => __('store.size_guide_tip', [], $locale),
            'tables' => [
                [
                    'id' => 'tees',
                    'title' => $isKm
                        ? 'អាវយឺតសាច់ក្រាស់ និងអាវដៃវែង (cm)'
                        : 'Heavyweight T-Shirts & Tops (cm)',
                    'columns' => [
                        'size' => __('store.size_guide_size', [], $locale),
                        'chest' => __('store.size_guide_chest', [], $locale),
                        'length' => __('store.size_guide_length', [], $locale),
                        'shoulder' => __('store.size_guide_shoulder', [], $locale),
                        'sleeve' => $isKm ? 'ប្រវែងដៃអាវ (cm)' : 'Sleeve Length (cm)',
                    ],
                    'rows' => [
                        ['size' => 'S', 'chest_cm' => 54, 'length_cm' => 71, 'shoulder_cm' => 50, 'sleeve_cm' => 22],
                        ['size' => 'M', 'chest_cm' => 57, 'length_cm' => 74, 'shoulder_cm' => 53, 'sleeve_cm' => 23],
                        ['size' => 'L', 'chest_cm' => 60, 'length_cm' => 77, 'shoulder_cm' => 56, 'sleeve_cm' => 24],
                        ['size' => 'XL', 'chest_cm' => 63, 'length_cm' => 80, 'shoulder_cm' => 59, 'sleeve_cm' => 25],
                    ],
                ],
                [
                    'id' => 'hoodies',
                    'title' => $isKm
                        ? 'អាវរងា Hoodies & Crewnecks សាច់ក្រណាត់ French Terry (cm)'
                        : 'French Terry Hoodies & Crewnecks (cm)',
                    'columns' => [
                        'size' => __('store.size_guide_size', [], $locale),
                        'chest' => __('store.size_guide_chest', [], $locale),
                        'length' => __('store.size_guide_length', [], $locale),
                        'shoulder' => __('store.size_guide_shoulder', [], $locale),
                        'sleeve' => $isKm ? 'ប្រវែងដៃអាវ (cm)' : 'Sleeve Length (cm)',
                    ],
                    'rows' => [
                        ['size' => 'S', 'chest_cm' => 58, 'length_cm' => 68, 'shoulder_cm' => 54, 'sleeve_cm' => 62],
                        ['size' => 'M', 'chest_cm' => 61, 'length_cm' => 71, 'shoulder_cm' => 57, 'sleeve_cm' => 64],
                        ['size' => 'L', 'chest_cm' => 64, 'length_cm' => 74, 'shoulder_cm' => 60, 'sleeve_cm' => 66],
                        ['size' => 'XL', 'chest_cm' => 67, 'length_cm' => 77, 'shoulder_cm' => 63, 'sleeve_cm' => 68],
                    ],
                ],
            ],
            'how_to_measure_title' => $isKm
                ? 'របៀបដែលយើងវាស់ទំហំរាបស្មើ'
                : 'How We Take Flat Measurements',
            'how_to_measure' => [
                [
                    'key' => 'chest',
                    'label' => $isKm ? 'ដើមទ្រូង' : 'Chest',
                    'description' => $isKm
                        ? 'វាស់កាត់ត្រង់ពីថ្នេរក្លៀកម្ខាងទៅថ្នេរក្លៀកម្ខាងទៀត។'
                        : 'Measured straight across the chest from armpit seam to armpit seam.',
                ],
                [
                    'key' => 'length',
                    'label' => $isKm ? 'ប្រវែង' : 'Length',
                    'description' => $isKm
                        ? 'វាស់ពីចំណុចខ្ពស់បំផុតនៃកអាវចុះមកក្រោមជាយអាវ។'
                        : 'Measured from the highest point of the shoulder collar seam down to the bottom hem.',
                ],
                [
                    'key' => 'shoulder',
                    'label' => $isKm ? 'ស្មា' : 'Shoulder',
                    'description' => $isKm
                        ? 'វាស់ពីថ្នេរស្មាម្ខាងទៅថ្នេរស្មាម្ខាងទៀតកាត់ខ្នង។'
                        : 'Measured from shoulder seam to shoulder seam straight across the back.',
                ],
                [
                    'key' => 'sleeve',
                    'label' => $isKm ? 'ដៃអាវ' : 'Sleeve',
                    'description' => $isKm
                        ? 'វាស់ពីថ្នេរស្មាខាងលើចុះមកដល់ចុងដៃអាវ។'
                        : 'Measured from top shoulder seam down to the outer cuff edge.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{slug: string, title: string, subtitle: ?string, endpoint: string}>
     */
    public function pageSummaries(?string $locale = null): array
    {
        $locale = $this->normalizeLocale($locale);
        $isKm = $locale === 'km';

        return [
            [
                'slug' => 'about',
                'title' => __('store.footer_about', [], $locale),
                'subtitle' => $isKm ? 'រាជធានីភ្នំពេញ ប្រទេសកម្ពុជា • បង្កើតឡើងក្នុងឆ្នាំ ២០២៦' : 'Phnom Penh, Cambodia • Established 2026',
                'endpoint' => '/api/v1/pages/about',
            ],
            [
                'slug' => 'faq',
                'title' => __('store.footer_faq', [], $locale),
                'subtitle' => $isKm ? 'សំណួរញឹកញាប់អំពី KHQR និងការដឹកជញ្ជូន' : 'Bakong KHQR payments, nationwide shipping & sizing',
                'endpoint' => '/api/v1/pages/faq',
            ],
            [
                'slug' => 'shipping',
                'title' => __('store.footer_shipping', [], $locale),
                'subtitle' => $isKm ? 'គ្របដណ្តប់លើ ២៥ រាជធានី-ខេត្ត' : 'Nationwide coverage across all 25 Cambodian provinces',
                'endpoint' => '/api/v1/pages/shipping',
            ],
            [
                'slug' => 'size-guide',
                'title' => __('store.footer_size_guide', [], $locale),
                'subtitle' => __('store.size_guide_desc', [], $locale),
                'endpoint' => '/api/v1/pages/size-guide',
            ],
            [
                'slug' => 'privacy',
                'title' => __('store.footer_privacy', [], $locale),
                'subtitle' => $isKm ? 'កាលបរិច្ឆេទធ្វើបច្ចុប្បន្នភាពចុងក្រោយ៖ ខែមករា ឆ្នាំ ២០២៦' : 'Last Updated: January 2026',
                'endpoint' => '/api/v1/pages/privacy',
            ],
            [
                'slug' => 'terms',
                'title' => __('store.footer_terms', [], $locale),
                'subtitle' => $isKm ? 'លក្ខខណ្ឌទូទៅនៃការលក់ និងការប្រើប្រាស់' : 'Standard Conditions of Sale & Use',
                'endpoint' => '/api/v1/pages/terms',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function page(string $slug, ?string $locale = null): ?array
    {
        $locale = $this->normalizeLocale($locale);
        $isKm = $locale === 'km';
        $studioInfo = [
            'studio_title' => __('store.contact_studio_title', [], $locale),
            'street' => 'Street 178, Daun Penh',
            'city_country' => __('store.contact_phnom_penh', [], $locale),
            'hours' => __('store.contact_hours', [], $locale),
            'telegram_handle' => '@reset_store_kh',
            'telegram_url' => 'https://t.me/reset_store_kh',
            'email' => (string) Setting::get('store_email', 'support@reset.store'),
            'phone' => (string) Setting::get('store_phone', '+855 23 999 123'),
        ];

        return match ($slug) {
            'about' => [
                'slug' => 'about',
                'locale' => $locale,
                'badge' => $isKm ? 'ទស្សនវិស័យរបស់យើង' : 'Our Manifesto',
                'title' => $isKm ? 'ទម្រង់។ គុណភាព។ ភាពស្រស់បំព្រង។' : 'Form. Substance. Renewal.',
                'subtitle' => $isKm ? 'រាជធានីភ្នំពេញ ប្រទេសកម្ពុជា • បង្កើតឡើងក្នុងឆ្នាំ ២០២៦' : 'Phnom Penh, Cambodia • Established 2026',
                'updated_at' => '2026-01',
                'sections' => [
                    [
                        'id' => 'origin',
                        'eyebrow' => null,
                        'title' => $isKm ? 'ប្រភពដើមនៃ RESET' : 'The Origin of RESET',
                        'paragraphs' => $isKm
                            ? [
                                'RESET ត្រូវបានបង្កើតឡើងពីបំណងប្រាថ្នាក្នុងការដកភាពរញ៉េរញ៉ៃចេញពីការស្លៀកពាក់បែប fast fashion។ នៅក្នុងយុគសម័យដែលពោរពេញដោយការប្រើប្រាស់លឿន និងក្រណាត់សំយោគ យើងមានអារម្មណ៍ថាចាំបាច់ត្រូវចាប់ផ្តើមសារជាថ្មី។',
                                'មានមូលដ្ឋាននៅរាជធានីភ្នំពេញ ស្ទូឌីយោរបស់យើងបង្កើតនូវទម្រង់សម្លៀកបំពាក់ streetwear យូរអង្វែងដែលត្រូវនឹងអាកាសធាតុត្រូពិក និងរបៀបរស់នៅក្នុងទីក្រុង។',
                            ]
                            : [
                                'RESET was born out of dissatisfaction with the frantic cycles of fast fashion and disposable trends. In an era dominated by hyper-speed consumption and synthetic blends, we felt an urgent need to wipe the slate clean—to reset our approach to garment design and personal expression.',
                                'Based in Phnom Penh, our studio creates permanent streetwear silhouettes engineered for the modern tropical climate and urban lifestyle. We obsess over structural fabric weights, architectural proportions, and subtle tailoring details.',
                            ],
                    ],
                    [
                        'id' => 'pillars',
                        'eyebrow' => null,
                        'title' => $isKm ? 'សសរស្តម្ភសំខាន់ៗរបស់យើង' : 'Our Core Pillars',
                        'items' => [
                            [
                                'label' => $isKm ? '01 / ទម្ងន់ក្រណាត់' : '01 / WEIGHT',
                                'title' => $isKm ? 'កប្បាសធ្ងន់ 240+ GSM' : '240+ GSM Organic Cotton',
                                'description' => $isKm
                                    ? 'សាច់ក្រណាត់កប្បាសសរីរាង្គទម្ងន់ 240+ GSM និង French terry fleece ដែលរក្សាទ្រង់ទ្រាយបានល្អទោះបីបោកគក់ច្រើនដង។'
                                    : 'Heavyweight 240+ GSM combed organic cottons and French terry fleece that hold structure across repeated washes.',
                            ],
                            [
                                'label' => $isKm ? '02 / ទម្រង់រចនា' : '02 / SILHOUETTE',
                                'title' => $isKm ? 'ទម្រង់ស្មាធ្លាក់ និងកខ្ពស់' : 'Architectural Proportions',
                                'description' => $isKm
                                    ? 'ទម្រង់ស្មាធ្លាក់ កខ្ពស់ និងរាងធូរស្រួល ដែលផ្តល់ផាសុកភាពដោយមិនបាត់បង់ភាពទាន់សម័យ។'
                                    : 'Drop-shoulder cuts, high-rib collars, and relaxed boxy drape crafted for comfort without sacrificing sharpness.',
                            ],
                            [
                                'label' => $isKm ? '03 / ទូទាត់តាម KHQR' : '03 / KHQR NATIVE',
                                'title' => $isKm ? 'បាគង KHQR រហ័ស' : 'Bakong KHQR Native',
                                'description' => $isKm
                                    ? 'ការទូទាត់ប្រាក់ឌីជីថលយ៉ាងរលូនជាមួយបាគង KHQR ធ្វើឱ្យការទិញទំនិញកាន់តែងាយស្រួល និងមានតម្លាភាពនៅទូទាំងប្រទេសកម្ពុជា។'
                                    : 'Frictionless digital payments with Bakong KHQR, making modern e-commerce accessible and transparent across Cambodia.',
                            ],
                        ],
                    ],
                    [
                        'id' => 'craft',
                        'eyebrow' => null,
                        'title' => $isKm ? 'រចនាក្នុងស្រុក ផលិតសម្រាប់គ្រប់ទីកន្លែង' : 'Designed Locally, Built for Everywhere',
                        'paragraphs' => $isKm
                            ? ['គ្រប់សម្លៀកបំពាក់ក្នុងបណ្តុំ RESET ត្រូវបានបង្កើតឡើងនៅកម្ពុជា។ យើងសហការយ៉ាងជិតស្និទ្ធជាមួយអ្នកច្នៃម៉ូដ និងជាងកាត់ដេរក្នុងស្រុក ដើម្បីធានាបាននូវស្តង់ដារផលិតកម្មប្រកបដោយក្រមសីលធម៌ និងគុណភាពខ្ពស់។']
                            : ['Every piece in the RESET collection is developed in Cambodia. We collaborate closely with local patternmakers, screen printers, and garment artisans to ensure ethical production standards and exceptional finishing. We do not design for seasons; we design for longevity.'],
                    ],
                ],
                'tables' => [],
                'size_guide' => null,
                'studio_info' => $studioInfo,
            ],
            'faq' => [
                'slug' => 'faq',
                'locale' => $locale,
                'badge' => 'FAQ',
                'title' => __('store.footer_faq', [], $locale),
                'subtitle' => $isKm
                    ? 'អ្វីគ្រប់យ៉ាងដែលអ្នកត្រូវដឹងអំពីការទិញទំនិញ ការទូទាត់ប្រាក់តាម KHQR និងការដឹកជញ្ជូន។'
                    : 'Everything you need to know about shopping, KHQR payments, and delivery.',
                'updated_at' => '2026-01',
                'sections' => [
                    [
                        'id' => 'khqr',
                        'eyebrow' => '01',
                        'title' => $isKm ? '01. ការទូទាត់ប្រាក់តាមបាគង KHQR' : '01. Bakong KHQR & Payments',
                        'items' => [
                            [
                                'question' => $isKm ? 'តើការទូទាត់ប្រាក់តាមបាគង KHQR ដំណើរការដូចម្តេច?' : 'How does Bakong KHQR payment work?',
                                'answer' => $isKm
                                    ? 'នៅពេលអ្នកចូលទៅដល់ទំព័រទូទាត់ប្រាក់ កូដបាគង KHQR ផ្លូវការនឹងត្រូវបានបង្កើតឡើងដោយស្វ័យប្រវត្តិនូវចំនួនទឹកប្រាក់សរុបនៃការបញ្ជាទិញរបស់អ្នក។ បើកកម្មវិធីធនាគាររបស់អ្នក ជ្រើសរើស Scan QR ផ្ទៀងផ្ទាត់ឈ្មោះ RESET Store និងបញ្ជាក់ការផ្ទេរប្រាក់។'
                                    : 'When you reach checkout, an official dynamic Bakong KHQR code is generated with your exact order total. Open your mobile banking app (ABA, ACLEDA, Wing, Sathapana, or Bakong), select Scan QR, verify "RESET Store", and confirm.',
                            ],
                            [
                                'question' => $isKm ? 'តើខ្ញុំអាចប្រើប្រាស់ធនាគារ ឬកាបូបអេឡិចត្រូនិចណាខ្លះ?' : 'Which banks and mobile wallets can I use?',
                                'answer' => $isKm
                                    ? 'គ្រប់ធនាគារ និងគ្រឹះស្ថានហិរញ្ញវត្ថុដែលជាសមាជិកបាគង ត្រូវបានគាំទ្រទាំងអស់ រួមមាន ABA, ACLEDA, Wing, Sathapana, Canadia, Chip Mong និងជាច្រើនទៀត។'
                                    : 'All member banks and financial institutions participating in the National Bank of Cambodia Bakong network are supported, including ABA, ACLEDA, Wing, Sathapana, Canadia, Chip Mong, and Bakong.',
                            ],
                            [
                                'question' => $isKm ? 'តើមានអ្វីកើតឡើងប្រសិនបើរយៈពេលកូដ QR ផុតកំណត់?' : 'What happens if the QR countdown expires?',
                                'answer' => $isKm
                                    ? 'រយៈពេលកំណត់ត្រូវបានរចនាឡើងដើម្បីរក្សាទុកស្តុកសម្រាប់អ្នក។ ប្រសិនបើកូដ QR ផុតកំណត់ អ្នកគ្រាន់តែចុចលើ "បង្កើតកូដ QR ថ្មី" ដើម្បីពិនិត្យស្តុក និងបង្កើតកូដថ្មីភ្លាមៗ។'
                                    : 'The timer holds inventory for you during checkout. If your QR expires, click "Generate New QR Code" on the payment screen to re-verify stock and issue a fresh KHQR code.',
                            ],
                        ],
                    ],
                    [
                        'id' => 'delivery',
                        'eyebrow' => '02',
                        'title' => $isKm ? '02. ការដឹកជញ្ជូន និងចែកចាយ' : '02. Shipping & Delivery',
                        'items' => [
                            [
                                'question' => $isKm ? 'តើការដឹកជញ្ជូននៅភ្នំពេញ និងខេត្តចំណាយពេលប៉ុន្មាន?' : 'How fast is delivery in Phnom Penh and all 25 provinces?',
                                'answer' => $isKm
                                    ? 'ការដឹកជញ្ជូនធម្មតា 0 ៛ ទូទាំង ២៥ រាជធានី-ខេត្ត ចំណាយពេល ១ ទៅ ៣ ថ្ងៃធ្វើការ។ សេវាដឹកជញ្ជូនរហ័សក្នុងថ្ងៃតែមួយនៅភ្នំពេញមានតម្លៃ 12,000 ៛ (៣-៤ ម៉ោង)។'
                                    : 'Standard nationwide delivery across all 25 Cambodian provinces is 0 ៛ (1 business day in Phnom Penh, 1–3 business days to provinces). Same-Day Express Courier in Phnom Penh is 12,000 ៛ (3–4 hours).',
                            ],
                            [
                                'question' => $isKm ? 'តើខ្ញុំអាចប្តូរទំហំបានទេប្រសិនបើស្លៀកមិនត្រូវ?' : 'Can I exchange sizes if it does not fit?',
                                'answer' => $isKm
                                    ? 'បាន! យើងទទួលការប្តូរទំហំក្នុងរយៈពេល ៧ ថ្ងៃបន្ទាប់ពីទទួលបានទំនិញ ដោយទំនិញត្រូវតែមិនទាន់បោកគក់ និងនៅរក្សាស្លាកសញ្ញាដើមទាំងអស់។'
                                    : 'Yes. We accept size exchanges within 7 days of delivery provided the item is unwashed, unworn, and retains all original tags and packaging.',
                            ],
                        ],
                    ],
                ],
                'tables' => [],
                'size_guide' => null,
                'studio_info' => null,
            ],
            'shipping' => [
                'slug' => 'shipping',
                'locale' => $locale,
                'badge' => $isKm ? 'ការដឹកជញ្ជូនទូទាំងប្រទេស' : 'Nationwide Coverage',
                'title' => __('store.footer_shipping', [], $locale),
                'subtitle' => $isKm ? 'ការដឹកជញ្ជូនទូទាំងប្រទេសគ្របដណ្តប់លើ ២៥ រាជធានី-ខេត្ត។' : 'Nationwide coverage across all 25 Cambodian provinces.',
                'updated_at' => '2026-01',
                'sections' => [
                    [
                        'id' => 'processing',
                        'eyebrow' => null,
                        'title' => $isKm ? 'ដំណើរការរៀបចំកញ្ចប់ និងតាមដានការដឹកជញ្ជូន' : 'Order Processing & Tracking',
                        'paragraphs' => $isKm
                            ? [
                                'គ្រប់ការបញ្ជាទិញដែលបានទូទាត់ប្រាក់តាមបាគង KHQR ត្រូវបានបញ្ជាក់ដោយស្វ័យប្រវត្តិក្នុងប្រព័ន្ធរបស់យើង។ កញ្ចប់ទំនិញត្រូវបានត្រួតពិនិត្យ និងបញ្ជូនចេញពីមជ្ឈមណ្ឌលនៅភ្នំពេញក្នុងរយៈពេល ២៤ ម៉ោង។',
                                'នៅពេលប្រគល់ជូនដៃគូដឹកជញ្ជូន អ្នកនឹងទទួលបានលេខកូដតាមដានកញ្ចប់ទំនិញរបស់អ្នក។',
                            ]
                            : [
                                'All orders paid via Bakong KHQR are automatically confirmed in our system. Packages are inspected, folded, and dispatched from our central Phnom Penh fulfillment facility within 24 hours (excluding public holidays).',
                                'Once handed to our delivery partner, you will receive an automated notification with your carrier name and tracking reference number.',
                            ],
                    ],
                    [
                        'id' => 'exchanges',
                        'eyebrow' => null,
                        'title' => $isKm ? 'គោលការណ៍ប្តូរទំនិញក្នុងរយៈពេល ៧ ថ្ងៃ' : '7-Day Exchange Policy',
                        'paragraphs' => $isKm
                            ? ['ប្រសិនបើទំហំដែលអ្នកបានជ្រើសរើសមិនសមល្មម អ្នកអាចស្នើសុំប្តូរទំហំក្នុងរយៈពេល ៧ ថ្ងៃបន្ទាប់ពីទទួលបានទំនិញ។']
                            : ['If your selected size is not optimal, you may request a size exchange within 7 days of delivery under the following conditions:'],
                        'bullets' => $isKm
                            ? [
                                'ទំនិញត្រូវតែថ្មី មិនទាន់បោកគក់ និងមិនទាន់ប្រើប្រាស់។',
                                'ស្លាកសញ្ញា និងការវេចខ្ចប់ដើមទាំងអស់ត្រូវតែនៅរក្សាភាពដើម។',
                                'អតិថិជនជាអ្នកទទួលបន្ទុកថ្លៃសេវាដឹកជញ្ជូនប្តូរទំនិញ លើកលែងតែករណីទំនិញមានបញ្ហាបច្ចេកទេស។',
                            ]
                            : [
                                'Item must be in brand new, unwashed, and unworn condition.',
                                'All original woven labels, hangtags, and packaging must remain intact.',
                                'Customer is responsible for standard exchange courier fees unless the item arrived defective.',
                            ],
                    ],
                ],
                'tables' => [
                    [
                        'title' => $isKm ? 'តម្លៃ និងរយៈពេលដឹកជញ្ជូន' : 'Shipping Rates & Transit Times',
                        'headers' => $isKm
                            ? ['តំបន់ / គោលដៅ', 'រយៈពេលប៉ាន់ស្មាន', 'តម្លៃដឹកជញ្ជូន', 'ឥតគិតថ្លៃ']
                            : ['Zone / Destination', 'Estimated Time', 'Rate', 'Free Shipping'],
                        'rows' => $isKm
                            ? [
                                ['រាជធានីភ្នំពេញ (ដឹកជញ្ជូនធម្មតា)', '១ ថ្ងៃធ្វើការ', '0 ៛', 'រួមបញ្ចូលស្រាប់'],
                                ['រាជធានីភ្នំពេញ (រហ័សក្នុងថ្ងៃតែមួយ)', '៣–៤ ម៉ោង', '12,000 ៛', '—'],
                                ['បណ្តា ២៤ ខេត្ត (សៀមរាប, បាត់ដំបង ។ល។)', '១–៣ ថ្ងៃធ្វើការ', '0 ៛', 'រួមបញ្ចូលស្រាប់'],
                            ]
                            : [
                                ['Phnom Penh (Standard)', '1 Business Day', '0 ៛', 'Included'],
                                ['Phnom Penh (Same-Day Express)', '3–4 Hours', '12,000 ៛', '—'],
                                ['All 24 Provinces (Siem Reap, Battambang, etc.)', '1–3 Business Days', '0 ៛', 'Included'],
                            ],
                    ],
                ],
                'size_guide' => null,
                'studio_info' => $studioInfo,
            ],
            'size-guide' => [
                'slug' => 'size-guide',
                'locale' => $locale,
                'badge' => 'CM CHART',
                'title' => __('store.footer_size_guide', [], $locale),
                'subtitle' => __('store.size_guide_desc', [], $locale),
                'updated_at' => '2026-01',
                'sections' => [],
                'tables' => [],
                'size_guide' => $this->sizeGuide($locale),
                'studio_info' => null,
            ],
            'privacy' => [
                'slug' => 'privacy',
                'locale' => $locale,
                'badge' => $isKm ? 'ឯកជនភាព' : 'Privacy',
                'title' => __('store.footer_privacy', [], $locale),
                'subtitle' => $isKm ? 'កាលបរិច្ឆេទធ្វើបច្ចុប្បន្នភាពចុងក្រោយ៖ ខែមករា ឆ្នាំ ២០២៦' : 'Last Updated: January 2026',
                'updated_at' => '2026-01',
                'sections' => [
                    [
                        'id' => 'overview',
                        'title' => $isKm ? '១. ទិដ្ឋភាពទូទៅ' : '1. Overview',
                        'paragraphs' => $isKm
                            ? ['នៅ RESET Store ("RESET", "យើងខ្ញុំ") យើងខ្ញុំឱ្យតម្លៃ និងការពារឯកជនភាពរបស់អ្នក។ គោលការណ៍នេះពិពណ៌នាអំពីរបៀបដែលព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នកត្រូវបានប្រមូល ប្រើប្រាស់ និងការពារ។']
                            : ['At RESET Store ("RESET", "we", "us"), we value and protect your privacy. This policy outlines how your personal information is collected, utilized, and safeguarded when you visit our website or complete purchases using our platform.'],
                    ],
                    [
                        'id' => 'collection',
                        'title' => $isKm ? '២. ព័ត៌មានដែលយើងប្រមូល' : '2. Information We Collect',
                        'paragraphs' => $isKm
                            ? ['ដើម្បីដំណើរការ និងដឹកជញ្ជូនការបញ្ជាទិញសម្លៀកបំពាក់របស់អ្នកនៅក្នុងប្រទេសកម្ពុជា យើងប្រមូលព័ត៌មានទំនាក់ទំនង និងព័ត៌មានដឹកជញ្ជូនចាំបាច់ រួមមាន៖']
                            : ['To process and deliver your apparel orders within Cambodia, we collect necessary contact and delivery details, including:'],
                        'bullets' => $isKm
                            ? [
                                'ឈ្មោះពេញ និងឈ្មោះអ្នកទទួលទំនិញ។',
                                'លេខទូរស័ព្ទកម្ពុជាត្រឹមត្រូវ (ចាំបាច់សម្រាប់ការសម្របសម្រួលជាមួយអ្នកដឹកជញ្ជូន)។',
                                'អាសយដ្ឋានអ៊ីមែលសម្រាប់ការផ្ញើវិក្កយបត្រ និងដំណឹងតាមដានការបញ្ជូនទំនិញ។',
                                'អាសយដ្ឋានដឹកជញ្ជូន រួមមាន ខេត្ត/រាជធានី ខណ្ឌ/ស្រុក សង្កាត់/ឃុំ និងទីតាំងចំណាំ។',
                            ]
                            : [
                                'Full name and recipient name.',
                                'Valid Cambodian telephone number (essential for courier coordination).',
                                'Email address for order receipts and dispatch tracking updates.',
                                'Shipping address including province, district, commune, and landmarks.',
                            ],
                    ],
                    [
                        'id' => 'payment-security',
                        'title' => $isKm ? '៣. សុវត្ថិភាពការទូទាត់ប្រាក់ និងបាគង KHQR' : '3. Payment Security & Bakong KHQR',
                        'paragraphs' => $isKm
                            ? ['យើងខ្ញុំមិនរក្សាទុក ឬមានសិទ្ធិចូលមើលលេខគណនីធនាគារ កាតឥណទាន ឬលេខសម្ងាត់ PIN របស់អ្នកឡើយ។ ការទូទាត់ប្រាក់ទាំងអស់ត្រូវបានអនុវត្តតាមរយៈប្រព័ន្ធបាគង KHQR របស់ធនាគារជាតិនៃកម្ពុជា។']
                            : ['We do NOT store or have access to your bank account numbers, credit cards, or PINs. All payments are conducted via the National Bank of Cambodia’s Bakong KHQR protocol directly through your authorized financial institution.'],
                    ],
                    [
                        'id' => 'contact',
                        'title' => $isKm ? '៤. ទំនាក់ទំនង' : '4. Contact',
                        'paragraphs' => $isKm
                            ? ['ប្រសិនបើអ្នកមានសំណួរអំពីទិន្នន័យរបស់អ្នក ឬចង់ស្នើសុំលុបទិន្នន័យ សូមទាក់ទងមកកាន់ support@reset.store។']
                            : ['If you have any questions regarding your data or wish to request data removal, please contact our support team at support@reset.store.'],
                    ],
                ],
                'tables' => [],
                'size_guide' => null,
                'studio_info' => null,
            ],
            'terms' => [
                'slug' => 'terms',
                'locale' => $locale,
                'badge' => $isKm ? 'លក្ខខណ្ឌ' : 'Terms',
                'title' => __('store.footer_terms', [], $locale),
                'subtitle' => $isKm ? 'លក្ខខណ្ឌទូទៅនៃការលក់ និងការប្រើប្រាស់' : 'Standard Conditions of Sale & Use',
                'updated_at' => '2026-01',
                'sections' => [
                    [
                        'id' => 'agreement',
                        'title' => $isKm ? '១. ការយល់ព្រមលើលក្ខខណ្ឌ' : '1. Agreement to Terms',
                        'paragraphs' => $isKm
                            ? ['តាមរយៈការចូលមើល ឬការទិញទំនិញពី RESET Store អ្នកយល់ព្រមអនុវត្តតាមលក្ខខណ្ឌប្រើប្រាស់ទាំងនេះ និងច្បាប់នៃព្រះរាជាណាចក្រកម្ពុជា។']
                            : ['By accessing or purchasing from RESET Store, you agree to be bound by these Terms of Service and all applicable laws and regulations of the Kingdom of Cambodia.'],
                    ],
                    [
                        'id' => 'pricing',
                        'title' => $isKm ? '២. តម្លៃ និងការប្តូររូបិយប័ណ្ណ' : '2. Pricing & Currency Conversion',
                        'paragraphs' => $isKm
                            ? ['តម្លៃទំនិញទាំងអស់ត្រូវបានកំណត់ជាប្រាក់រៀល (KHR) ឬប្រាក់ដុល្លារ (USD) ដោយគណនាតាមអត្រាប្តូរប្រាក់ផ្លូវការនៅលើអេក្រង់ទូទាត់។']
                            : ['All product prices are stored in Cambodian Riel (KHR) and may be displayed or charged in KHR or USD using the official exchange rate shown at checkout.'],
                    ],
                    [
                        'id' => 'reservation',
                        'title' => $isKm ? '៣. ការរក្សាទុកស្តុកទំនិញ និងការផុតកំណត់នៃការទូទាត់' : '3. Inventory Reservation & Payment Expiration',
                        'paragraphs' => $isKm
                            ? ['បន្ទាប់ពីបង្កើតកូដទូទាត់ប្រាក់បាគង KHQR ស្តុកទំនិញសម្រាប់សម្លៀកបំពាក់ដែលអ្នកបានជ្រើសរើស ត្រូវបានរក្សាទុកអំឡុងពេលទូទាត់។ ប្រសិនបើការទូទាត់មិនត្រូវបានផ្ទៀងផ្ទាត់ទាន់ពេលទេ ការបញ្ជាទិញនឹងផុតកំណត់ ហើយស្តុកត្រូវបានបញ្ចេញវិញដោយស្វ័យប្រវត្តិ។']
                            : ['Upon generating a Bakong KHQR payment code, inventory for your selected garments is reserved for the active QR timer window. If payment is not verified within this window, the pending order expires and inventory is automatically released back to public stock.'],
                    ],
                    [
                        'id' => 'ip',
                        'title' => $isKm ? '៤. កម្មសិទ្ធិបញ្ញា និងច្បាប់គ្រប់គ្រង' : '4. Intellectual Property & Governing Law',
                        'paragraphs' => $isKm
                            ? ['រាល់ការរចនា និងនិមិត្តសញ្ញាជាកម្មសិទ្ធិបញ្ញារបស់ RESET Store។ លក្ខខណ្ឌទាំងនេះស្ថិតនៅក្រោមច្បាប់នៃព្រះរាជាណាចក្រកម្ពុជា។']
                            : ['All apparel designs, photographic materials, wordmarks, and logos are the intellectual property of RESET Store. These terms are governed by the laws of the Kingdom of Cambodia.'],
                    ],
                ],
                'tables' => [],
                'size_guide' => null,
                'studio_info' => null,
            ],
            default => null,
        };
    }

    private function normalizeLocale(?string $locale): string
    {
        $resolved = strtolower(trim((string) ($locale ?: app()->getLocale())));

        return in_array($resolved, ['en', 'km'], true) ? $resolved : 'en';
    }
}
