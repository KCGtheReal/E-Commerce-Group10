@extends('layouts.storefront', [
    'title' => __('store.footer_about') . ' — ' . __('store.brand_name'),
    'description' => 'Learn about the philosophy, materials, and design ethos behind RESET clothing in Phnom Penh.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    @if(app()->getLocale() === 'km')
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-orange">
                ទស្សនវិស័យរបស់យើង
            </span>
            <h1 class="text-3xl sm:text-5xl font-display font-bold uppercase tracking-tight text-brand-black mt-2">
                ទម្រង់។ គុណភាព។ ភាពស្រស់បំព្រង។
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                រាជធានីភ្នំពេញ ប្រទេសកម្ពុជា • បង្កើតឡើងក្នុងឆ្នាំ ២០២៦
            </p>
        </div>

        <div class="space-y-12 text-brand-black/80 text-sm sm:text-base leading-relaxed">
            <div>
                <h2 class="text-xl font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    ប្រភពដើមនៃ RESET
                </h2>
                <p>
                    RESET ត្រូវបានបង្កើតឡើងពីបំណងប្រាថ្នាក្នុងការដកភាពរញ៉េរញ៉ៃចេញពីការស្លៀកពាក់បែប fast fashion។ នៅក្នុងយុគសម័យដែលពោរពេញដោយការប្រើប្រាស់លឿន និងក្រណាត់សំយោគ យើងមានអារម្មណ៍ថាចាំបាច់ត្រូវចាប់ផ្តើមសារជាថ្មី—ដើម្បី <em>reset</em> របៀបរចនាសម្លៀកបំពាក់ និងការបង្ហាញពីអត្តសញ្ញាណផ្ទាល់ខ្លួន។
                </p>
                <p class="mt-4">
                    មានមូលដ្ឋាននៅរាជធានីភ្នំពេញ ស្ទូឌីយោរបស់យើងបង្កើតនូវទម្រង់សម្លៀកបំពាក់ streetwear យូរអង្វែងដែលត្រូវនឹងអាកាសធាតុត្រូពិក និងរបៀបរស់នៅក្នុងទីក្រុង។ យើងផ្តោតខ្លាំងលើទម្ងន់ក្រណាត់ ទម្រង់សមមាត្រ និងការកាត់ដេរយ៉ាងផ្ចិតផ្ចង់ ដែលធ្វើឱ្យការស្លៀកពាក់ប្រចាំថ្ងៃកាន់តែមានទំនុកចិត្ត។
                </p>
            </div>

            <div class="p-8 bg-brand-surface border border-black/10">
                <h3 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-3">
                    សសរស្តម្ភសំខាន់ៗរបស់យើង
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs uppercase tracking-wider">
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">01 / ទម្ងន់ក្រណាត់</span>
                        <p class="text-brand-black/70 normal-case">សាច់ក្រណាត់កប្បាសសរីរាង្គទម្ងន់ 240+ GSM និង French terry fleece ដែលរក្សាទ្រង់ទ្រាយបានល្អទោះបីបោកគក់ច្រើនដង។</p>
                    </div>
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">02 / ទម្រង់រចនា</span>
                        <p class="text-brand-black/70 normal-case">ទម្រង់ស្មាធ្លាក់ កខ្ពស់ និងរាងធូរស្រួល ដែលផ្តល់ផាសុកភាពដោយមិនបាត់បង់ភាពទាន់សម័យ។</p>
                    </div>
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">03 / ទូទាត់តាម KHQR</span>
                        <p class="text-brand-black/70 normal-case">ការទូទាត់ប្រាក់ឌីជីថលយ៉ាងរលូនជាមួយបាគង KHQR ធ្វើឱ្យការទិញទំនិញកាន់តែងាយស្រួល និងមានតម្លាភាពនៅទូទាំងប្រទេសកម្ពុជា។</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    រចនាក្នុងស្រុក ផលិតសម្រាប់គ្រប់ទីកន្លែង
                </h2>
                <p>
                    គ្រប់សម្លៀកបំពាក់ក្នុងបណ្តុំ RESET ត្រូវបានបង្កើតឡើងនៅកម្ពុជា។ យើងសហការយ៉ាងជិតស្និទ្ធជាមួយអ្នកច្នៃម៉ូដ អ្នកបោះពុម្ពពុម្ព និងជាងកាត់ដេរក្នុងស្រុក ដើម្បីធានាបាននូវស្តង់ដារផលិតកម្មប្រកបដោយក្រមសីលធម៌ និងគុណភាពខ្ពស់។ យើងមិនរចនាសម្រាប់រដូវកាលទេ យើងរចនាដើម្បីភាពយូរអង្វែង។
                </p>
            </div>

            <div class="pt-8 border-t border-black/10 flex justify-center">
                <a href="{{ route('shop.index') }}" class="px-8 py-4 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition duration-200">
                    @lang('store.hero_cta_shop') →
                </a>
            </div>
        </div>
    @else
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-orange">
                Our Manifesto
            </span>
            <h1 class="text-3xl sm:text-5xl font-display font-bold uppercase tracking-tight text-brand-black mt-2">
                Form. Substance. Renewal.
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                Phnom Penh, Cambodia • Established 2026
            </p>
        </div>

        <div class="space-y-12 text-brand-black/80 text-sm sm:text-base leading-relaxed">
            <div>
                <h2 class="text-xl font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    The Origin of RESET
                </h2>
                <p>
                    RESET was born out of dissatisfaction with the frantic cycles of fast fashion and disposable trends. In an era dominated by hyper-speed consumption and synthetic blends, we felt an urgent need to wipe the slate clean—to <em>reset</em> our approach to garment design and personal expression.
                </p>
                <p class="mt-4">
                    Based in Phnom Penh, our studio creates permanent streetwear silhouettes engineered for the modern tropical climate and urban lifestyle. We obsess over structural fabric weights, architectural proportions, and subtle tailoring details that make daily dressing effortless and intentional.
                </p>
            </div>

            <div class="p-8 bg-brand-surface border border-black/10">
                <h3 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-3">
                    Our Core Pillars
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs uppercase tracking-wider">
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">01 / WEIGHT</span>
                        <p class="text-brand-black/70 normal-case">Heavyweight 240+ GSM combed organic cottons and French terry fleece that hold structure across repeated washes.</p>
                    </div>
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">02 / SILHOUETTE</span>
                        <p class="text-brand-black/70 normal-case">Drop-shoulder cuts, high-rib collars, and relaxed boxy drape crafted for comfort without sacrificing sharpness.</p>
                    </div>
                    <div>
                        <span class="text-brand-orange font-mono font-bold block mb-1">03 / KHQR NATIVE</span>
                        <p class="text-brand-black/70 normal-case">Frictionless digital payments with Bakong KHQR, making modern e-commerce accessible and transparent across Cambodia.</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    Designed Locally, Built for Everywhere
                </h2>
                <p>
                    Every piece in the RESET collection is developed in Cambodia. We collaborate closely with local patternmakers, screen printers, and garment artisans to ensure ethical production standards and exceptional finishing. We do not design for seasons; we design for longevity.
                </p>
            </div>

            <div class="pt-8 border-t border-black/10 flex justify-center">
                <a href="{{ route('shop.index') }}" class="px-8 py-4 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition duration-200">
                    @lang('store.hero_cta_shop') →
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
