@extends('layouts.storefront', [
    'title' => __('store.footer_faq') . ' — ' . __('store.brand_name'),
    'description' => 'Frequently asked questions about payments, Bakong KHQR, nationwide shipping, and sizing.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    @if(app()->getLocale() === 'km')
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_faq')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                អ្វីគ្រប់យ៉ាងដែលអ្នកត្រូវដឹងអំពីការទិញទំនិញ ការទូទាត់ប្រាក់តាម KHQR និងការដឹកជញ្ជូន។
            </p>
        </div>

        <div class="space-y-10 text-brand-black" x-data="{ active: null }">
            <!-- Section 1: Bakong KHQR Payments -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    01. ការទូទាត់ប្រាក់តាមបាគង KHQR
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើការទូទាត់ប្រាក់តាមបាគង KHQR ដំណើរការដូចម្តេច?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            នៅពេលអ្នកចូលទៅដល់ទំព័រទូទាត់ប្រាក់ កូដបាគង KHQR ផ្លូវការនឹងត្រូវបានបង្កើតឡើងដោយស្វ័យប្រវត្តិនូវចំនួនទឹកប្រាក់សរុបនៃការបញ្ជាទិញរបស់អ្នក។ គ្រាន់តែបើកកម្មវិធីធនាគារចល័តរបស់អ្នក (ដូចជា ABA Mobile, ACLEDA mobile, Wing Bank, Sathapana ឬ Bakong), ជ្រើសរើស "Scan QR", ផ្ទៀងផ្ទាត់ឈ្មោះអាជីវករ "RESET Store" និងបញ្ជាក់ការផ្ទេរប្រាក់។ ប្រព័ន្ធរបស់យើងនឹងទទួលស្គាល់ការទូទាត់របស់អ្នកភ្លាមៗតាមរយៈច្រកទូទាត់ប្រាក់របស់ធនាគារជាតិនៃកម្ពុជា។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើខ្ញុំអាចប្រើប្រាស់ធនាគារ ឬកាបូបអេឡិចត្រូនិចណាខ្លះ?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            គ្រប់ធនាគារ និងគ្រឹះស្ថានហិរញ្ញវត្ថុដែលជាសមាជិកនៃបណ្តាញបាគងរបស់ធនាគារជាតិនៃកម្ពុជា ត្រូវបានគាំទ្រទាំងអស់ រួមមាន ABA Bank, ACLEDA Bank, Wing Bank, Sathapana Bank, Canadia Bank, Chip Mong Bank, Prince Bank, Phillip Bank, TrueMoney និងជាច្រើនទៀត។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើខ្ញុំអាចទូទាត់ជាប្រាក់រៀល (KHR) ឬប្រាក់ដុល្លារ (USD) បានទេ?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            បាន! ទំព័រទូទាត់ប្រាក់របស់យើងបង្ហាញទាំងចំនួនទឹកប្រាក់ជារៀល និងដុល្លារ ដោយយោងតាមអត្រាប្តូរប្រាក់ផ្លូវការ។ អ្នកអាចស្កេន និងទូទាត់ពីគណនីធនាគារជាប្រាក់ដុល្លារ ឬប្រាក់រៀលរបស់អ្នកបានដោយសេរី។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើមានអ្វីកើតឡើងប្រសិនបើរយៈពេល 5 នាទីនៃកូដ QR ផុតកំណត់?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            រយៈពេលកំណត់ 5 នាទីត្រូវបានរចនាឡើងដើម្បីរក្សាទុកទំនិញក្នុងស្តុកសម្រាប់អ្នក។ ប្រសិនបើកូដ QR ផុតកំណត់ អ្នកគ្រាន់តែចុចលើ "បង្កើតកូដ QR ថ្មី" នៅលើទំព័រទូទាត់ ដើម្បីពិនិត្យស្តុក និងបង្កើតកូដថ្មីភ្លាមៗ។
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Delivery & Shipping -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    02. ការដឹកជញ្ជូន និងចែកចាយ
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើការដឹកជញ្ជូននៅរាជធានីភ្នំពេញចំណាយពេលប៉ុន្មាន?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            ការដឹកជញ្ជូនធម្មតានៅភ្នំពេញចំណាយពេល ១ ថ្ងៃនៃថ្ងៃធ្វើការ ($1.50)។ យើងក៏មានសេវាដឹកជញ្ជូនរហ័សក្នុងថ្ងៃតែមួយ ($3.00) សម្រាប់ការបញ្ជាទិញមុនម៉ោង ២:០០ រសៀល ដែលនឹងដឹកដល់ដៃអ្នកក្នុងរង្វង់ ៣ ទៅ ៤ ម៉ោង។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើមានការដឹកជញ្ជូនទៅគ្រប់ ២៥ រាជធានី-ខេត្តដែរឬទេ?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            បាទ/ចាស! យើងដឹកជញ្ជូនទៅគ្រប់ ២៥ រាជធានី-ខេត្តទូទាំងប្រទេសកម្ពុជា (រួមមាន សៀមរាប បាត់ដំបង ព្រះសីហនុ កំពត កណ្តាល ។ល។) តាមរយៈដៃគូដឹកជញ្ជូនដែលអាចទុកចិត្តបាន (ដូចជា J&T Express, វីរៈប៊ុនថាំ ឬ កាពីតូល)។ ការដឹកជញ្ជូនតាមខេត្តចំណាយពេល ១ ទៅ ៣ ថ្ងៃនៃថ្ងៃធ្វើការ ($2.50)។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើធ្វើដូចម្តេចដើម្បីទទួលបានការដឹកជញ្ជូនឥតគិតថ្លៃ?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            ការបញ្ជាទិញចាប់ពី $50.00 ឡើងទៅ នឹងទទួលបានការដឹកជញ្ជូនតាមស្តង់ដារទូទាំងប្រទេសដោយឥតគិតថ្លៃ!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Sizing & Exchanges -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    03. ទំហំ ការប្តូរទំនិញ និងការប្រគល់ត្រឡប់
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើសម្លៀកបំពាក់ RESET មានទម្រង់បែបណា?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            សម្លៀកបំពាក់របស់យើងត្រូវបានកាត់ដេរក្នុងទម្រង់ streetwear បែបធូរល្មម និងទាន់សម័យ (relaxed & boxy) ជាមួយនឹងស្មាធ្លាក់បន្តិច។ ប្រសិនបើអ្នកចូលចិត្តរាងរឹបល្មម យើងសូមណែនាំឱ្យជ្រើសរើសទំហំតូចជាងមួយលេខ។ សូមពិនិត្យមើល <a href="{{ route('page.size-guide') }}" class="underline font-semibold hover:text-brand-orange">@lang('store.footer_size_guide')</a> របស់យើងសម្រាប់រង្វាស់លម្អិតគិតជាសង់ទីម៉ែត្រ។
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            តើខ្ញុំអាចប្តូរទំហំបានទេប្រសិនបើស្លៀកមិនត្រូវ?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            បាន! យើងទទួលការប្តូរទំហំក្នុងរយៈពេល ៧ ថ្ងៃបន្ទាប់ពីទទួលបានទំនិញ ដោយទំនិញត្រូវតែមិនទាន់បោកគក់ មិនទាន់ប្រើប្រាស់ និងនៅរក្សាស្លាកសញ្ញាដើមទាំងអស់។ សូមទាក់ទងមកកាន់យើងតាមតេឡេក្រាម ឬអ៊ីមែលដើម្បីធ្វើការផ្លាស់ប្តូរ។
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_faq')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                Everything you need to know about shopping, KHQR payments, and delivery.
            </p>
        </div>

        <div class="space-y-10 text-brand-black" x-data="{ active: null }">
            <!-- Section 1: Bakong KHQR Payments -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    01. Bakong KHQR & Payments
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            How does Bakong KHQR payment work?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            When you reach the checkout screen, an official dynamic Bakong KHQR code is generated with the exact total of your order. Simply open your mobile banking app (such as ABA Mobile, ACLEDA mobile, Wing Bank, Sathapana, or Bakong), select "Scan QR", verify the merchant name "RESET Store", and confirm the transfer. Our server detects your payment in real time via the National Bank of Cambodia payment gateway.
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            Which banks and mobile wallets can I use?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            All member banks and financial institutions participating in the National Bank of Cambodia Bakong network are supported. This includes ABA Bank, ACLEDA Bank, Wing Bank, Sathapana Bank, Canadia Bank, Chip Mong Bank, Prince Bank, Phillip Bank, TrueMoney, and many more.
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            Can I pay in Khmer Riel (KHR) or US Dollars (USD)?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Yes! Our checkout displays both USD and KHR amounts using the official NBC-backed exchange rate. You can scan and settle the payment from either your USD or KHR bank account.
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            What happens if the 5-minute QR countdown expires?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            The 5-minute limit is designed to reserve inventory for you while preventing items from being held indefinitely. If your QR expires, simply click "Generate New QR Code" on the payment page to check stock availability and generate a fresh code instantly.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Delivery & Shipping -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    02. Shipping & Delivery
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            How fast do you deliver in Phnom Penh?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Standard delivery within Phnom Penh takes 1 business day ($1.50). We also offer Same-Day Express Courier ($3.00) for orders placed before 2:00 PM, delivered to your door within 3 to 4 hours.
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            Do you deliver to all 25 provinces across Cambodia?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Yes. We deliver to all 25 provinces across Cambodia (including Siem Reap, Battambang, Sihanoukville, Kampot, Kandal, etc.) via trusted national courier partners (e.g., J&T Express, Virak Buntham, or Capitol). Provincial delivery takes 1 to 3 business days ($2.50).
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            How do I qualify for Free Shipping?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Orders of $50.00 or more automatically unlock free nationwide standard delivery anywhere in Cambodia!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Sizing & Exchanges -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-orange mb-4">
                    03. Fit, Exchanges & Returns
                </h2>
                <div class="space-y-4">
                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            How do RESET garments fit?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Our pieces are cut in contemporary relaxed and boxy streetwear proportions with slightly dropped shoulders. If you prefer a tailored or snug look, we recommend sizing down one size. Check our detailed <a href="{{ route('page.size-guide') }}" class="underline font-semibold hover:text-brand-orange">Size Guide</a> for exact garment measurements in centimeters.
                        </p>
                    </div>

                    <div class="border border-black/10 p-5 bg-white">
                        <h3 class="text-sm font-display font-bold uppercase text-brand-black mb-2">
                            Can I exchange sizes if it doesn't fit?
                        </h3>
                        <p class="text-xs sm:text-sm text-brand-black/70 leading-relaxed">
                            Yes. We accept size exchanges within 7 days of delivery, provided the item is unwashed, unworn, and retains its original tags and packaging. Contact us via Telegram or email to initiate an exchange.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
