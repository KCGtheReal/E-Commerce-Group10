@extends('layouts.storefront', [
    'title' => __('store.footer_shipping') . ' — ' . __('store.brand_name'),
    'description' => 'Delivery rates, turnaround times, and exchange policy for RESET online store.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    @if(app()->getLocale() === 'km')
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_shipping')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                ការដឹកជញ្ជូនទូទាំងប្រទេសគ្របដណ្តប់លើ ២៥ រាជធានី-ខេត្ត។
            </p>
        </div>

        <div class="space-y-12 text-brand-black/80 text-sm leading-relaxed">
            <!-- Delivery Table -->
            <div>
                <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    តម្លៃ និងរយៈពេលដឹកជញ្ជូន
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3.5">តំបន់ / គោលដៅ</th>
                                <th class="p-3.5">រយៈពេលប៉ាន់ស្មាន</th>
                                <th class="p-3.5">តម្លៃដឹកជញ្ជូន</th>
                                <th class="p-3.5">ការដឹកជញ្ជូនឥតគិតថ្លៃ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 uppercase font-medium">
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">រាជធានីភ្នំពេញ (ក្នុងក្រុង)</td>
                                <td class="p-3.5">១ ថ្ងៃនៃថ្ងៃធ្វើការ</td>
                                <td class="p-3.5">$1.50</td>
                                <td class="p-3.5 text-green-700 font-bold">បញ្ជាទិញ $50+</td>
                            </tr>
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">រាជធានីភ្នំពេញ (រហ័សក្នុងថ្ងៃតែមួយ)</td>
                                <td class="p-3.5">៣–៤ ម៉ោង</td>
                                <td class="p-3.5">$3.00</td>
                                <td class="p-3.5">—</td>
                            </tr>
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">បណ្តា ២៤ ខេត្ត (សៀមរាប, បាត់ដំបង ។ល។)</td>
                                <td class="p-3.5">១–៣ ថ្ងៃនៃថ្ងៃធ្វើការ</td>
                                <td class="p-3.5">$2.50</td>
                                <td class="p-3.5 text-green-700 font-bold">បញ្ជាទិញ $50+</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fulfillment Process -->
            <div>
                <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black mb-3">
                    ដំណើរការរៀបចំកញ្ចប់ និងតាមដានការដឹកជញ្ជូន
                </h2>
                <p>
                    គ្រប់ការបញ្ជាទិញដែលបានទូទាត់ប្រាក់តាមបាគង KHQR ត្រូវបានបញ្ជាក់ដោយស្វ័យប្រវត្តិក្នុងប្រព័ន្ធរបស់យើង។ កញ្ចប់ទំនិញត្រូវបានត្រួតពិនិត្យ វេចខ្ចប់ និងបញ្ជូនចេញពីមជ្ឈមណ្ឌលចែកចាយនៅភ្នំពេញក្នុងរយៈពេល ២៤ ម៉ោង (លើកលែងថ្ងៃឈប់សម្រាកបុណ្យជាតិ)។
                </p>
                <p class="mt-3">
                    នៅពេលប្រគល់ជូនដៃគូដឹកជញ្ជូន អ្នកនឹងទទួលបានសារដំណឹងអំពីឈ្មោះក្រុមហ៊ុនដឹកជញ្ជូន និងលេខកូដតាមដានកញ្ចប់ទំនិញរបស់អ្នក។
                </p>
            </div>

            <!-- Returns & Exchanges -->
            <div class="p-6 bg-brand-surface border border-black/10">
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-3">
                    គោលការណ៍ប្តូរទំនិញក្នុងរយៈពេល ៧ ថ្ងៃ
                </h2>
                <p class="text-xs text-brand-black/70 mb-3 leading-relaxed">
                    យើងចង់ឱ្យអ្នកស្លៀកសម្លៀកបំពាក់ដែលត្រូវទំហំ និងមានទំនុកចិត្តបំផុត។ ប្រសិនបើទំហំដែលអ្នកបានជ្រើសរើសមិនសមល្មម អ្នកអាចស្នើសុំប្តូរទំហំក្នុងរយៈពេល ៧ ថ្ងៃបន្ទាប់ពីទទួលបានទំនិញ ដោយយោងតាមលក្ខខណ្ឌដូចខាងក្រោម៖
                </p>
                <ul class="text-xs text-brand-black/70 list-disc list-inside space-y-1">
                    <li>ទំនិញត្រូវតែថ្មី មិនទាន់បោកគក់ និងមិនទាន់ប្រើប្រាស់។</li>
                    <li>ស្លាកសញ្ញា ផ្លាកយីហោ និងការវេចខ្ចប់ដើមទាំងអស់ត្រូវតែនៅរក្សាភាពដើម។</li>
                    <li>អតិថិជនជាអ្នកទទួលបន្ទុកថ្លៃសេវាដឹកជញ្ជូនប្តូរទំនិញ លើកលែងតែករណីទំនិញមានបញ្ហាបច្ចេកទេសតាំងពីដើម។</li>
                </ul>
            </div>
        </div>
    @else
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_shipping')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                Nationwide coverage across all 25 Cambodian provinces.
            </p>
        </div>

        <div class="space-y-12 text-brand-black/80 text-sm leading-relaxed">
            <!-- Delivery Table -->
            <div>
                <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black mb-4">
                    Shipping Rates & Transit Times
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3.5">Zone / Destination</th>
                                <th class="p-3.5">Estimated Time</th>
                                <th class="p-3.5">Standard Rate</th>
                                <th class="p-3.5">Free Shipping</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 uppercase font-medium">
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">Phnom Penh (Urban)</td>
                                <td class="p-3.5">1 Business Day</td>
                                <td class="p-3.5">$1.50</td>
                                <td class="p-3.5 text-green-700 font-bold">Orders $50+</td>
                            </tr>
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">Phnom Penh (Same-Day Express)</td>
                                <td class="p-3.5">3–4 Hours</td>
                                <td class="p-3.5">$3.00</td>
                                <td class="p-3.5">—</td>
                            </tr>
                            <tr>
                                <td class="p-3.5 font-bold text-brand-black">All 24 Provinces (Siem Reap, Battambang, etc.)</td>
                                <td class="p-3.5">1–3 Business Days</td>
                                <td class="p-3.5">$2.50</td>
                                <td class="p-3.5 text-green-700 font-bold">Orders $50+</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fulfillment Process -->
            <div>
                <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black mb-3">
                    Order Processing & Tracking
                </h2>
                <p>
                    All orders paid via Bakong KHQR are automatically confirmed in our system. Packages are inspected, folded, and dispatched from our central Phnom Penh fulfillment facility within 24 hours (excluding national public holidays).
                </p>
                <p class="mt-3">
                    Once handed to our delivery partner, you will receive an automated dispatch notification with your carrier name and tracking reference number.
                </p>
            </div>

            <!-- Returns & Exchanges -->
            <div class="p-6 bg-brand-surface border border-black/10">
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-3">
                    7-Day Exchange Policy
                </h2>
                <p class="text-xs text-brand-black/70 mb-3 leading-relaxed">
                    We want you to wear garments that fit you with total confidence. If your selected size is not optimal, you may request a size exchange within 7 days of delivery under the following conditions:
                </p>
                <ul class="text-xs text-brand-black/70 list-disc list-inside space-y-1">
                    <li>Item must be in brand new, unwashed, and unworn condition.</li>
                    <li>All original woven labels, hangtags, and packaging must remain intact.</li>
                    <li>Customer is responsible for standard exchange courier fees unless the item arrived defective.</li>
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
