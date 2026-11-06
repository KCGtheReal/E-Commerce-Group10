@extends('layouts.storefront', [
    'title' => __('store.footer_terms') . ' — ' . __('store.brand_name'),
    'description' => 'Terms of Service and purchasing conditions for RESET clothing store.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    @if(app()->getLocale() === 'km')
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_terms')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                លក្ខខណ្ឌទូទៅនៃការលក់ និងការប្រើប្រាស់
            </p>
        </div>

        <div class="space-y-8 text-brand-black/80 text-sm leading-relaxed">
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">១. ការយល់ព្រមលើលក្ខខណ្ឌ</h2>
                <p>តាមរយៈការចូលមើល ឬការទិញទំនិញពី RESET Store អ្នកយល់ព្រមអនុវត្តតាមលក្ខខណ្ឌប្រើប្រាស់ទាំងនេះ និងច្បាប់ និងបទប្បញ្ញត្តិជាធរមានទាំងអស់នៃព្រះរាជាណាចក្រកម្ពុជា។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">២. តម្លៃ និងការប្តូររូបិយប័ណ្ណ</h2>
                <p>តម្លៃទំនិញទាំងអស់ត្រូវបានកំណត់ជាប្រាក់រៀល (KHR) ឬប្រាក់ដុល្លារ (USD)។ នៅពេលទូទាត់ប្រាក់ជាមួយបាគង KHQR ចំនួនទឹកប្រាក់ត្រូវបានគណនាដោយយោងតាមអត្រាប្តូរប្រាក់ផ្លូវការដែលបានបង្ហាញនៅលើអេក្រង់ទូទាត់។ យើងខ្ញុំសូមរក្សាសិទ្ធិក្នុងការកែប្រែតម្លៃដោយមិនបាច់ជូនដំណឹងជាមុន។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៣. ការរក្សាទុកស្តុកទំនិញ និងការផុតកំណត់នៃការទូទាត់</h2>
                <p>បន្ទាប់ពីបង្កើតកូដទូទាត់ប្រាក់បាគង KHQR ស្តុកទំនិញសម្រាប់សម្លៀកបំពាក់ដែលអ្នកបានជ្រើសរើស ត្រូវបានរក្សាទុកក្នុងរយៈពេល 5 នាទី (300 វិនាទី)។ ប្រសិនបើការទូទាត់មិនត្រូវបានផ្ទៀងផ្ទាត់ដោយជោគជ័យក្នុងអំឡុងពេលនេះទេ ការបញ្ជាទិញនឹងផុតកំណត់ ហើយទំនិញនឹងត្រូវបញ្ចេញទៅក្នុងស្តុកសាធារណៈវិញដោយស្វ័យប្រវត្តិ។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៤. កម្មសិទ្ធិបញ្ញា</h2>
                <p>រាល់ការរចនាសម្លៀកបំពាក់ រូបថត ក្រាហ្វិក និងនិមិត្តសញ្ញា គឺជាកម្មសិទ្ធិបញ្ញារបស់ RESET Store។ ការចម្លង ការចែកចាយ ឬការកេងប្រវ័ញ្ចពាណិជ្ជកម្មដោយគ្មានការអនុញ្ញាត ត្រូវបានហាមឃាត់យ៉ាងតឹងរ៉ឹង។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៥. ច្បាប់គ្រប់គ្រង</h2>
                <p>លក្ខខណ្ឌទាំងនេះ និងកិច្ចសន្យាណាមួយដែលបានបង្កើតឡើងនៅទីនេះ ត្រូវស្ថិតនៅក្រោម និងបកស្រាយស្របតាមច្បាប់នៃព្រះរាជាណាចក្រកម្ពុជា។</p>
            </div>
        </div>
    @else
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_terms')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                Standard Conditions of Sale & Use
            </p>
        </div>

        <div class="space-y-8 text-brand-black/80 text-sm leading-relaxed">
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">1. Agreement to Terms</h2>
                <p>By accessing or purchasing from RESET Store, you agree to be bound by these Terms of Service and all applicable laws and regulations of the Kingdom of Cambodia.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">2. Pricing & Currency Conversion</h2>
                <p>All product prices are stated in United States Dollars (USD). When paying with Bakong KHQR, Cambodian Riel (KHR) amounts are calculated using the official exchange rate shown on the payment screen. We reserve the right to modify prices without prior notice.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">3. Inventory Reservation & Payment Expiration</h2>
                <p>Upon generating a Bakong KHQR payment code, inventory for your selected garments is held for a duration of 5 minutes (300 seconds). If payment is not verified by the Bakong gateway within this window, the pending order expires and inventory is automatically released back to public stock.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">4. Intellectual Property</h2>
                <p>All apparel designs, photographic materials, graphics, wordmarks, and logos are the intellectual property of RESET Store. Unauthorized reproduction, distribution, or commercial exploitation is strictly prohibited.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">5. Governing Law</h2>
                <p>These terms and any contracts formed hereunder shall be governed by and construed in accordance with the laws of the Kingdom of Cambodia.</p>
            </div>
        </div>
    @endif
</div>
@endsection
