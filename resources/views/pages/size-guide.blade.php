@extends('layouts.storefront', [
    'title' => __('store.footer_size_guide') . ' — ' . __('store.brand_name'),
    'description' => 'Complete sizing charts and flat garment measurements for RESET apparel collections.',
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="border-b border-black/10 pb-8 mb-12 text-center">
        <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.footer_size_guide')
        </h1>
        <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
            @lang('store.size_guide_desc')
        </p>
    </div>

    @if(app()->getLocale() === 'km')
        <div class="space-y-12 text-brand-black">
            <!-- Fit Philosophy Note -->
            <div class="p-6 bg-brand-surface border border-black/10 text-xs text-brand-black/80 leading-relaxed">
                <h2 class="text-xs font-bold uppercase tracking-wider text-brand-black mb-2">ទម្រង់ពិសេសរបស់យើង៖ រាងធូរល្មម និងទាន់សម័យ</h2>
                <p>សម្លៀកបំពាក់ RESET ត្រូវបានរចនាឡើងជាមួយនឹងទម្រង់ស្មាធ្លាក់ និងដើមទ្រូងធំទូលាយល្មម ដើម្បីផ្តល់នូវភាពទាន់សម័យ និងផាសុកភាព។ យើងសូមណែនាំឱ្យជ្រើសរើសទំហំពិតប្រាកដរបស់អ្នកសម្រាប់ទម្រង់បែបធូរស្រួល ឬជ្រើសរើសទំហំតូចជាងមួយលេខប្រសិនបើអ្នកចូលចិត្តទម្រង់រឹបរាងបែបបុរាណ។</p>
            </div>

            <!-- Table 1: T-Shirts & Long Sleeves -->
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-4">
                    អាវយឺតសាច់ក្រាស់ និងអាវដៃវែង (cm)
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3">@lang('store.size_guide_size')</th>
                                <th class="p-3">@lang('store.size_guide_chest')</th>
                                <th class="p-3">@lang('store.size_guide_length')</th>
                                <th class="p-3">@lang('store.size_guide_shoulder')</th>
                                <th class="p-3">ប្រវែងដៃអាវ (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 font-mono">
                            <tr>
                                <td class="p-3 font-bold font-sans">S</td>
                                <td class="p-3">54</td>
                                <td class="p-3">71</td>
                                <td class="p-3">50</td>
                                <td class="p-3">22</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">M</td>
                                <td class="p-3">57</td>
                                <td class="p-3">74</td>
                                <td class="p-3">53</td>
                                <td class="p-3">23</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">L</td>
                                <td class="p-3">60</td>
                                <td class="p-3">77</td>
                                <td class="p-3">56</td>
                                <td class="p-3">24</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">XL</td>
                                <td class="p-3">63</td>
                                <td class="p-3">80</td>
                                <td class="p-3">59</td>
                                <td class="p-3">25</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 2: Hoodies & Outerwear -->
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-4">
                    អាវរងា Hoodies & Crewnecks សាច់ក្រណាត់ French Terry (cm)
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3">@lang('store.size_guide_size')</th>
                                <th class="p-3">@lang('store.size_guide_chest')</th>
                                <th class="p-3">@lang('store.size_guide_length')</th>
                                <th class="p-3">@lang('store.size_guide_shoulder')</th>
                                <th class="p-3">ប្រវែងដៃអាវ (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 font-mono">
                            <tr>
                                <td class="p-3 font-bold font-sans">S</td>
                                <td class="p-3">58</td>
                                <td class="p-3">68</td>
                                <td class="p-3">54</td>
                                <td class="p-3">62</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">M</td>
                                <td class="p-3">61</td>
                                <td class="p-3">71</td>
                                <td class="p-3">57</td>
                                <td class="p-3">64</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">L</td>
                                <td class="p-3">64</td>
                                <td class="p-3">74</td>
                                <td class="p-3">60</td>
                                <td class="p-3">66</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">XL</td>
                                <td class="p-3">67</td>
                                <td class="p-3">77</td>
                                <td class="p-3">63</td>
                                <td class="p-3">68</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- How to Measure Instructions -->
            <div class="border border-black/10 p-6 bg-white space-y-3 text-xs">
                <h3 class="font-display font-bold uppercase tracking-wider text-brand-black">របៀបដែលយើងវាស់ទំហំរាបស្មើ</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-brand-black/70">
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">ដើមទ្រូង</span>
                        វាស់កាត់ត្រង់ពីថ្នេរក្លៀកម្ខាងទៅថ្នេរក្លៀកម្ខាងទៀត។
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">ប្រវែង</span>
                        វាស់ពីចំណុចខ្ពស់បំផុតនៃកអាវចុះមកក្រោមជាយអាវ។
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">ស្មា</span>
                        វាស់ពីថ្នេរស្មាម្ខាងទៅថ្នេរស្មាម្ខាងទៀតកាត់ខ្នង។
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">ដៃអាវ</span>
                        វាស់ពីថ្នេរស្មាខាងលើចុះមកដល់ចុងដៃអាវ។
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-12 text-brand-black">
            <!-- Fit Philosophy Note -->
            <div class="p-6 bg-brand-surface border border-black/10 text-xs text-brand-black/80 leading-relaxed">
                <h2 class="text-xs font-bold uppercase tracking-wider text-brand-black mb-2">Our Signature Fit: Relaxed & Architectural</h2>
                <p>RESET garments are engineered with relaxed, drop-shoulder silhouettes and wider chests for an effortless streetwear drape. We recommend choosing your true size for our intended relaxed look, or sizing down one size if you prefer a classic, more fitted aesthetic.</p>
            </div>

            <!-- Table 1: T-Shirts & Long Sleeves -->
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-4">
                    Heavyweight T-Shirts & Tops (cm)
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3">Size</th>
                                <th class="p-3">Chest Width (cm)</th>
                                <th class="p-3">Length (cm)</th>
                                <th class="p-3">Shoulder (cm)</th>
                                <th class="p-3">Sleeve Length (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 font-mono">
                            <tr>
                                <td class="p-3 font-bold font-sans">S</td>
                                <td class="p-3">54</td>
                                <td class="p-3">71</td>
                                <td class="p-3">50</td>
                                <td class="p-3">22</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">M</td>
                                <td class="p-3">57</td>
                                <td class="p-3">74</td>
                                <td class="p-3">53</td>
                                <td class="p-3">23</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">L</td>
                                <td class="p-3">60</td>
                                <td class="p-3">77</td>
                                <td class="p-3">56</td>
                                <td class="p-3">24</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">XL</td>
                                <td class="p-3">63</td>
                                <td class="p-3">80</td>
                                <td class="p-3">59</td>
                                <td class="p-3">25</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 2: Hoodies & Outerwear -->
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-4">
                    French Terry Hoodies & Crewnecks (cm)
                </h2>
                <div class="overflow-x-auto border border-black/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-brand-surface border-b border-black/10 uppercase tracking-wider text-brand-black font-bold">
                            <tr>
                                <th class="p-3">Size</th>
                                <th class="p-3">Chest Width (cm)</th>
                                <th class="p-3">Length (cm)</th>
                                <th class="p-3">Shoulder (cm)</th>
                                <th class="p-3">Sleeve Length (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 font-mono">
                            <tr>
                                <td class="p-3 font-bold font-sans">S</td>
                                <td class="p-3">58</td>
                                <td class="p-3">68</td>
                                <td class="p-3">54</td>
                                <td class="p-3">62</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">M</td>
                                <td class="p-3">61</td>
                                <td class="p-3">71</td>
                                <td class="p-3">57</td>
                                <td class="p-3">64</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">L</td>
                                <td class="p-3">64</td>
                                <td class="p-3">74</td>
                                <td class="p-3">60</td>
                                <td class="p-3">66</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold font-sans">XL</td>
                                <td class="p-3">67</td>
                                <td class="p-3">77</td>
                                <td class="p-3">63</td>
                                <td class="p-3">68</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- How to Measure Instructions -->
            <div class="border border-black/10 p-6 bg-white space-y-3 text-xs">
                <h3 class="font-display font-bold uppercase tracking-wider text-brand-black">How We Take Flat Measurements</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-brand-black/70">
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">Chest</span>
                        Measured straight across the chest from armpit seam to armpit seam.
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">Length</span>
                        Measured from the highest point of the shoulder collar seam down to the bottom hem.
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">Shoulder</span>
                        Measured from shoulder seam to shoulder seam straight across the back.
                    </div>
                    <div>
                        <span class="font-bold text-brand-black uppercase block mb-1">Sleeve</span>
                        Measured from top shoulder seam down to the outer cuff edge.
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
