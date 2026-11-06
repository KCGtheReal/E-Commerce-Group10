@extends('layouts.storefront', [
    'title' => __('store.footer_privacy') . ' — ' . __('store.brand_name'),
    'description' => 'Privacy Policy and data protection guidelines for RESET online store.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    @if(app()->getLocale() === 'km')
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_privacy')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                កាលបរិច្ឆេទធ្វើបច្ចុប្បន្នភាពចុងក្រោយ៖ ខែមករា ឆ្នាំ ២០២៦
            </p>
        </div>

        <div class="space-y-8 text-brand-black/80 text-sm leading-relaxed">
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">១. ទិដ្ឋភាពទូទៅ</h2>
                <p>នៅ RESET Store ("RESET", "យើងខ្ញុំ") យើងខ្ញុំឱ្យតម្លៃ និងការពារឯកជនភាពរបស់អ្នក។ គោលការណ៍នេះពិពណ៌នាអំពីរបៀបដែលព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នកត្រូវបានប្រមូល ប្រើប្រាស់ និងការពារនៅពេលអ្នកចូលមើលគេហទំព័ររបស់យើង ឬធ្វើការទិញទំនិញនៅលើប្រព័ន្ធរបស់យើង។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">២. ព័ត៌មានដែលយើងប្រមូល</h2>
                <p>ដើម្បីដំណើរការ និងដឹកជញ្ជូនការបញ្ជាទិញសម្លៀកបំពាក់របស់អ្នកនៅក្នុងប្រទេសកម្ពុជា យើងប្រមូលព័ត៌មានទំនាក់ទំនង និងព័ត៌មានដឹកជញ្ជូនចាំបាច់ រួមមាន៖</p>
                <ul class="list-disc list-inside mt-2 text-xs space-y-1 text-brand-black/70">
                    <li>ឈ្មោះពេញ និងឈ្មោះអ្នកទទួលទំនិញ។</li>
                    <li>លេខទូរស័ព្ទកម្ពុជាត្រឹមត្រូវ (ចាំបាច់សម្រាប់ការសម្របសម្រួលជាមួយអ្នកដឹកជញ្ជូន)។</li>
                    <li>អាសយដ្ឋានអ៊ីមែលសម្រាប់ការផ្ញើវិក្កយបត្រ និងដំណឹងតាមដានការបញ្ជូនទំនិញ។</li>
                    <li>អាសយដ្ឋានដឹកជញ្ជូន រួមមាន ខេត្ត/រាជធានី ខណ្ឌ/ស្រុក សង្កាត់/ឃុំ និងទីតាំងចំណាំ។</li>
                </ul>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៣. សុវត្ថិភាពការទូទាត់ប្រាក់ និងបាគង KHQR</h2>
                <p>យើងខ្ញុំមិនរក្សាទុក ឬមានសិទ្ធិចូលមើលលេខគណនីធនាគារ កាតឥណទាន ឬលេខសម្ងាត់ PIN របស់អ្នកឡើយ។ ការទូទាត់ប្រាក់ទាំងអស់ត្រូវបានអនុវត្តតាមរយៈប្រព័ន្ធបាគង KHQR របស់ធនាគារជាតិនៃកម្ពុជា។ នៅពេលអ្នកស្កេនកូដ QR របស់យើង ប្រតិបត្តិការត្រូវបានដំណើរការដោយផ្ទាល់តាមរយៈកម្មវិធីធនាគារដែលមានសុវត្ថិភាពខ្ពស់របស់អ្នក។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៤. ខូគី និងចំណង់ចំណូលចិត្ត (Cookies & Preferences)</h2>
                <p>គេហទំព័ររបស់យើងប្រើប្រាស់ខូគីសម័យចាំបាច់ដើម្បីចងចាំទំនិញក្នុងកន្ត្រករបស់អ្នក ភាសាដែលបានជ្រើសរើស (អង់គ្លេស ឬខ្មែរ) និងរូបិយប័ណ្ណបង្ហាញ។ យើងខ្ញុំមិនលក់ព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នកទៅកាន់ភាគីទីបីណាមួយឡើយ។</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">៥. ទំនាក់ទំនង</h2>
                <p>ប្រសិនបើអ្នកមានសំណួរអំពីទិន្នន័យរបស់អ្នក ឬចង់ស្នើសុំលុបទិន្នន័យ សូមទាក់ទងមកកាន់ក្រុមការងារគាំទ្ររបស់យើងតាមរយៈ <a href="mailto:support@reset.store" class="underline font-semibold">support@reset.store</a>។</p>
            </div>
        </div>
    @else
        <div class="border-b border-black/10 pb-8 mb-12 text-center">
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.footer_privacy')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
                Last Updated: January 2026
            </p>
        </div>

        <div class="space-y-8 text-brand-black/80 text-sm leading-relaxed">
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">1. Overview</h2>
                <p>At RESET Store ("RESET", "we", "us"), we value and protect your privacy. This policy outlines how your personal information is collected, utilized, and safeguarded when you visit our website or complete purchases using our platform.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">2. Information We Collect</h2>
                <p>To process and deliver your apparel orders within Cambodia, we collect necessary contact and delivery details, including:</p>
                <ul class="list-disc list-inside mt-2 text-xs space-y-1 text-brand-black/70">
                    <li>Full name and recipient name.</li>
                    <li>Valid Cambodian telephone number (essential for courier coordination).</li>
                    <li>Email address for order receipts and dispatch tracking updates.</li>
                    <li>Shipping address including province, district, commune, and landmarks.</li>
                </ul>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">3. Payment Security & Bakong KHQR</h2>
                <p>We do NOT store or have access to your bank account numbers, credit cards, or PINs. All payments are conducted via the National Bank of Cambodia’s Bakong KHQR protocol. When you scan our dynamic QR code, the transaction is processed directly through your authorized financial institution's encrypted application.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">4. Cookies & Preferences</h2>
                <p>Our application uses essential session cookies to preserve your shopping cart contents, preferred language (English or Khmer), and currency display selection. We do not sell your personal data to third-party advertisers.</p>
            </div>

            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-2">5. Contact</h2>
                <p>If you have any questions regarding your data or wish to request data removal, please contact our support team at <a href="mailto:support@reset.store" class="underline font-semibold">support@reset.store</a>.</p>
            </div>
        </div>
    @endif
</div>
@endsection
