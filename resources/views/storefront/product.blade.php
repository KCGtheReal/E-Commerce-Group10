@extends('layouts.storefront')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="productPage(productConfig)">

    <!-- Breadcrumb -->
    <nav class="text-xs text-neutral-400 mb-8 font-display uppercase tracking-widest flex items-center space-x-2">
        <a href="{{ route('home') }}" class="hover:text-brand-black">{{ __('store.nav_home') }}</a>
        <span>/</span>
        <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" class="hover:text-brand-black">{{ $product->category->localized_name }}</a>
        <span>/</span>
        <span class="text-brand-black font-semibold">{{ $product->localized_name }}</span>
    </nav>

    <!-- Product Grid: Gallery + Details -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        
        <!-- Left: Image Gallery (7 cols) -->
        <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-4">
            <!-- Thumbnails -->
            <div class="flex md:flex-col gap-3 overflow-x-auto md:w-20 shrink-0">
                @foreach($product->images as $img)
                    <button type="button" @click="activeImage = '{{ $img->image_url }}'" class="aspect-square w-16 md:w-full border rounded overflow-hidden transition" :class="activeImage === '{{ $img->image_url }}' ? 'border-brand-black ring-1 ring-brand-black' : 'border-brand-border opacity-70 hover:opacity-100'">
                        <img src="{{ $img->image_url }}" alt="{{ $img->alt_text }}" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>

            <!-- Main High-Res Image Display -->
            <div class="flex-1 aspect-[4/5] bg-white border border-brand-border rounded relative overflow-hidden group cursor-zoom-in" @click="zoomModal = true">
                <img :src="activeImage" src="{{ $product->primary_image_url }}" alt="{{ $product->localized_name }}" class="w-full h-full object-cover">
                <div class="absolute bottom-4 right-4 bg-brand-black/75 text-white text-[11px] font-display uppercase px-3 py-1.5 rounded backdrop-blur-sm opacity-0 group-hover:opacity-100 transition flex items-center space-x-1">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                    <span>{{ __('store.click_to_zoom') }}</span>
                </div>
            </div>
        </div>

        <!-- Right: Purchasing Controls (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <div>
                <span class="text-xs font-display uppercase tracking-widest text-neutral-400 block">{{ $product->category->localized_name }} • SKU: {{ $product->sku_prefix }}</span>
                <h1 class="text-2xl sm:text-3xl font-display font-extrabold uppercase tracking-tight text-brand-black mt-1">
                    {{ $product->localized_name }}
                </h1>
                <p class="text-xs text-neutral-500 mt-1 font-light">{{ $product->localized_tagline }}</p>
                
                <div class="mt-4 flex items-baseline space-x-3">
                    <span class="text-2xl font-display font-black text-brand-black" x-text="currentPrice">{{ $product->formatted_price }}</span>
                    @if($product->compare_at_price_cents)
                        <span class="text-sm text-neutral-400 line-through">{{ $product->formatted_compare_at_price }}</span>
                    @endif
                </div>
            </div>

            <hr class="border-brand-border">

            <!-- Color Selection -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-display uppercase tracking-wider font-bold text-brand-black">
                        {{ __('store.select_color') }}: <span class="font-normal text-neutral-600" x-text="selectedColor">{{ $product->variants->first()?->color }}</span>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    @php
                        $uniqueColors = $product->variants->unique('color');
                    @endphp
                    @foreach($uniqueColors as $v)
                        <button type="button" @click="selectedColor = '{{ $v->color }}'" class="flex items-center space-x-2 px-3 py-1.5 border rounded text-xs transition" :class="selectedColor === '{{ $v->color }}' ? 'border-brand-black ring-1 ring-brand-black font-bold' : 'border-brand-border hover:border-neutral-400'">
                            <span class="w-3.5 h-3.5 rounded-full border border-neutral-300" style="background-color: {{ $v->color_code }}"></span>
                            <span>{{ $v->color }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Size Selection -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-display uppercase tracking-wider font-bold text-brand-black">
                        {{ __('store.select_size') }}
                    </span>
                    <button type="button" @click="sizeGuideModal = true" class="text-xs text-brand-accent hover:underline flex items-center space-x-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>{{ __('store.size_guide') }}</span>
                    </button>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    @php
                        $uniqueSizes = $product->variants->unique('size')->pluck('size');
                    @endphp
                    @foreach($uniqueSizes as $sz)
                        <button type="button" @click="selectedSize = '{{ $sz }}'" class="py-2.5 border text-xs font-display font-bold uppercase rounded transition" :class="selectedSize === '{{ $sz }}' ? 'bg-brand-black text-white border-brand-black' : 'border-brand-border text-brand-black hover:border-brand-black'">
                            {{ $sz }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Live Stock Status Indicator -->
            <div class="text-xs flex items-center space-x-2">
                <template x-if="isInStock">
                    <div class="flex items-center space-x-1.5 text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ __('store.in_stock') }} (<span x-text="availableStock">{{ $product->total_stock }}</span> {{ __('store.stock_left') }})</span>
                    </div>
                </template>
                <template x-if="!isInStock">
                    <div class="flex items-center space-x-1.5 text-red-600">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        <span>{{ __('store.out_of_stock') }}</span>
                    </div>
                </template>
            </div>

            <!-- Quantity and Add to Cart -->
            <div class="flex items-center space-x-4 pt-2">
                <div class="flex items-center border border-brand-border rounded bg-white">
                    <button type="button" @click="if (quantity > 1) quantity--" class="px-3.5 py-3 text-neutral-600 hover:text-brand-black transition">-</button>
                    <span class="px-3 text-xs font-bold font-display" x-text="quantity">1</span>
                    <button type="button" @click="if (quantity < availableStock) quantity++" class="px-3.5 py-3 text-neutral-600 hover:text-brand-black transition">+</button>
                </div>

                <button type="button" @click="addToCart()" :disabled="!isInStock || isAdding" class="flex-1 bg-brand-black text-white py-3.5 px-6 rounded font-display uppercase tracking-widest text-xs font-bold hover:bg-brand-dark transition shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-text="buttonText">{{ __('store.add_to_cart') }}</span>
                </button>

                <!-- Wishlist Toggle -->
                <form action="{{ route('wishlist.toggle') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="p-3.5 border border-brand-border rounded hover:border-brand-black transition text-neutral-600 hover:text-brand-black" title="{{ __('store.wishlist_add') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </button>
                </form>
            </div>

            @guest
                <div class="pt-1 flex items-center space-x-1.5 text-[11px] text-neutral-500">
                    <svg class="h-3.5 w-3.5 text-brand-accent shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>
                        {{ __('store.cart_signin_required_notice') }}
                        <a href="{{ route('login') }}" class="font-bold underline text-brand-black hover:text-brand-accent">{{ __('store.nav_login') }}</a>
                    </span>
                </div>
            @endguest

            <!-- Bakong Badge Callout -->
            <div class="bg-brand-bone border border-brand-border rounded p-4 flex items-center space-x-3 text-xs">
                <img src="{{ asset('images/payments/khqr-logo.svg') }}" alt="KHQR" class="h-6 w-auto shrink-0">
                <p class="text-neutral-600 leading-tight">
                    {{ __('store.product_bakong_notice') }}
                </p>
            </div>

            <!-- Accordion Details Tabs -->
            <div class="pt-4 border-t border-brand-border divide-y divide-brand-border" x-data="{ tab: 'desc' }">
                <div>
                    <button type="button" @click="tab = tab === 'desc' ? '' : 'desc'" class="w-full py-3 flex justify-between items-center text-xs font-display font-bold uppercase tracking-wider text-brand-black">
                        <span>{{ __('store.details_tabs_desc') }}</span>
                        <span x-text="tab === 'desc' ? '−' : '+'"></span>
                    </button>
                    <div x-show="tab === 'desc'" x-cloak class="pb-4 text-xs text-neutral-600 leading-relaxed font-light">
                        {{ $product->localized_description }}
                    </div>
                </div>
                <div>
                    <button type="button" @click="tab = tab === 'fabric' ? '' : 'fabric'" class="w-full py-3 flex justify-between items-center text-xs font-display font-bold uppercase tracking-wider text-brand-black">
                        <span>{{ __('store.details_tabs_fabric') }}</span>
                        <span x-text="tab === 'fabric' ? '−' : '+'"></span>
                    </button>
                    <div x-show="tab === 'fabric'" x-cloak class="pb-4 text-xs text-neutral-600 leading-relaxed font-light">
                        {{ $product->details['fabric'] ?? '100% Heavyweight Organic Combed Cotton.' }}
                    </div>
                </div>
                <div>
                    <button type="button" @click="tab = tab === 'care' ? '' : 'care'" class="w-full py-3 flex justify-between items-center text-xs font-display font-bold uppercase tracking-wider text-brand-black">
                        <span>{{ __('store.details_tabs_care') }}</span>
                        <span x-text="tab === 'care' ? '−' : '+'"></span>
                    </button>
                    <div x-show="tab === 'care'" x-cloak class="pb-4 text-xs text-neutral-600 leading-relaxed font-light">
                        {{ $product->details['care'] ?? 'Machine wash cold with like colors, inside out. Do not tumble dry.' }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Size Guide Modal -->
    <div x-show="sizeGuideModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="sizeGuideModal = false" class="fixed inset-0 bg-brand-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-lg max-w-lg w-full p-6 shadow-2xl z-10">
            <div class="flex justify-between items-start pb-4 border-b border-brand-border">
                <div>
                    <h3 class="text-base font-display font-bold uppercase tracking-wider">{{ __('store.size_guide_title') }}</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ __('store.size_guide_desc') }}</p>
                </div>
                <button @click="sizeGuideModal = false" class="text-neutral-400 hover:text-brand-black">✕</button>
            </div>
            <div class="py-4 overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="border-b border-brand-border text-neutral-400 uppercase font-display">
                            <th class="py-2">{{ __('store.size_guide_size') }}</th>
                            <th class="py-2">{{ __('store.size_guide_chest') }}</th>
                            <th class="py-2">{{ __('store.size_guide_shoulder') }}</th>
                            <th class="py-2">{{ __('store.size_guide_length') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 font-mono">
                        <tr><td class="py-2 font-bold font-sans">S</td><td>56</td><td>52</td><td>72</td></tr>
                        <tr><td class="py-2 font-bold font-sans">M</td><td>59</td><td>55</td><td>74</td></tr>
                        <tr><td class="py-2 font-bold font-sans">L</td><td>62</td><td>58</td><td>76</td></tr>
                        <tr><td class="py-2 font-bold font-sans">XL</td><td>65</td><td>61</td><td>78</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-neutral-400 italic">{{ __('store.size_guide_tip') }}</p>
        </div>
    </div>

    <!-- Click-to-Zoom Modal -->
    <div x-show="zoomModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-brand-black/90 backdrop-blur-md" @click.self="zoomModal = false">
        <button @click="zoomModal = false" class="absolute top-6 right-6 text-white text-2xl font-bold p-2 hover:opacity-75">✕</button>
        <img :src="activeImage" src="{{ $product->primary_image_url }}" alt="Zoomed view" class="max-h-[90vh] max-w-[90vw] object-contain rounded shadow-2xl">
    </div>

    <!-- Customer Reviews Section -->
    <section class="mt-24 pt-12 border-t border-brand-border">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black">
                    {{ __('store.customer_reviews') }} ({{ $product->review_count }})
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="flex text-amber-500 text-sm">
                        @for($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= round($product->average_rating) ? '★' : '☆' }}</span>
                        @endfor
                    </div>
                    <span class="text-xs text-neutral-500">{{ __('store.rating_out_of_five', ['rating' => number_format($product->average_rating, 1)]) }}</span>
                </div>
            </div>

            @auth
                <button @click="reviewModal = true" class="mt-4 sm:mt-0 bg-brand-black text-white px-5 py-2.5 text-xs font-display font-bold uppercase tracking-wider rounded hover:bg-brand-dark transition">
                    {{ __('store.write_review') }}
                </button>
            @else
                <a href="{{ route('login') }}" class="mt-4 sm:mt-0 text-xs font-bold text-brand-accent underline">
                    {{ __('store.review_signin_prompt') }}
                </a>
            @endauth
        </div>

        @if($product->reviews->isEmpty())
            <div class="text-center py-12 bg-white border border-brand-border rounded p-6">
                <p class="text-xs text-neutral-500">{{ __('store.no_reviews_yet') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($product->reviews as $review)
                    <div class="bg-white border border-brand-border p-5 rounded space-y-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-bold text-xs text-brand-black">{{ $review->user->name }}</span>
                                @if($review->is_verified_purchase)
                                    <span class="ml-2 inline-flex items-center text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-medium">
                                        ✓ {{ __('store.review_verified') }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-amber-500 text-xs">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                        </div>
                        @if($review->title)
                            <h4 class="text-xs font-bold text-brand-black">{{ $review->title }}</h4>
                        @endif
                        <p class="text-xs text-neutral-600 leading-relaxed font-light">{{ $review->body }}</p>
                        <span class="text-[10px] text-neutral-400 block pt-1">{{ $review->created_at->format('M d, Y') }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Write Review Modal -->
        @auth
            <div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="reviewModal = false" class="fixed inset-0 bg-brand-black/60 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-lg max-w-md w-full p-6 shadow-2xl z-10">
                    <div class="flex justify-between items-start pb-4 border-b border-brand-border mb-4">
                        <h3 class="text-base font-display font-bold uppercase tracking-wider">{{ __('store.write_review') }}</h3>
                        <button @click="reviewModal = false" class="text-neutral-400 hover:text-brand-black">✕</button>
                    </div>
                    <form action="{{ route('product.review.store', $product->slug) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">{{ __('store.review_rating') }}</label>
                            <select name="rating" required class="w-full text-xs border border-brand-border rounded py-2 px-3">
                                <option value="5">{{ __('store.rating_5_stars') }}</option>
                                <option value="4">{{ __('store.rating_4_stars') }}</option>
                                <option value="3">{{ __('store.rating_3_stars') }}</option>
                                <option value="2">{{ __('store.rating_2_stars') }}</option>
                                <option value="1">{{ __('store.rating_1_star') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">{{ __('store.review_title') }}</label>
                            <input type="text" name="title" placeholder="{{ __('store.review_title_placeholder') }}" class="w-full text-xs border border-brand-border rounded py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">{{ __('store.review_body') }}</label>
                            <textarea name="body" rows="4" required placeholder="{{ __('store.review_body_placeholder') }}" class="w-full text-xs border border-brand-border rounded py-2 px-3"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-brand-black text-white text-xs font-display font-bold uppercase py-3 rounded hover:bg-brand-dark transition">
                            {{ __('store.review_submit') }}
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </section>

    <!-- Related Products -->
    @if($relatedProducts->isNotEmpty())
        <section class="mt-24 pt-12 border-t border-brand-border">
            <h2 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black mb-8">
                {{ __('store.related_products') }}
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $rel)
                    <div class="group flex flex-col justify-between">
                        <a href="{{ route('product.show', $rel->slug) }}" class="block aspect-[3/4] bg-white overflow-hidden rounded relative border border-brand-border">
                            <img src="{{ $rel->primary_image_url }}" alt="{{ $rel->localized_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </a>
                        <div class="pt-4 flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-brand-black group-hover:text-brand-accent transition">
                                    <a href="{{ route('product.show', $rel->slug) }}">{{ $rel->localized_name }}</a>
                                </h3>
                                <p class="text-xs text-neutral-500 mt-0.5">{{ $rel->localized_tagline }}</p>
                            </div>
                            <span class="text-sm font-display font-bold text-brand-black ml-2">{{ $rel->formatted_price }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
<script>
window.productConfig = {
    initialImage: @json($product->primary_image_url),
    initialColor: @json($product->variants->first()?->color ?? ''),
    initialSize: @json($product->variants->first()?->size ?? ''),
    initialPrice: @json($product->formatted_price),
    inStockText: @json(__('store.add_to_cart')),
    outOfStockText: @json(__('store.out_of_stock')),
    addingText: @json(__('store.adding_to_cart')),
    addUrl: @json(route('cart.add')),
    loginUrl: @json(route('login')),
    isAuthenticated: @json(auth()->check()),
    variants: @json($product->variants)
};

if (!window.productPage) {
    window.productPage = function(config = {}) {
        return {
            activeImage: config.initialImage || '',
            zoomModal: false,
            sizeGuideModal: false,
            reviewModal: false,
            selectedColor: config.initialColor || '',
            selectedSize: config.initialSize || '',
            quantity: 1,
            isAdding: false,
            variants: config.variants || [],
            isAuthenticated: Boolean(config.isAuthenticated),

            get activeVariant() {
                return this.variants.find(v => v.color === this.selectedColor && v.size === this.selectedSize) || this.variants[0];
            },

            get availableStock() {
                return this.activeVariant ? Math.max(0, this.activeVariant.stock_quantity - (this.activeVariant.reserved_quantity || 0)) : 0;
            },

            get isInStock() {
                return this.availableStock > 0;
            },

            get currentPrice() {
                if (!this.activeVariant) return config.initialPrice || '0 ៛';
                const currency = window.RESET_STORE?.currency || 'KHR';
                const rate = window.RESET_STORE?.exchangeRate || 4100;
                if (currency === 'USD') {
                    return '$' + (this.activeVariant.price_cents / rate).toFixed(2);
                }
                return Number(this.activeVariant.price_cents).toLocaleString() + ' ៛';
            },

            get buttonText() {
                if (this.isAdding) return config.addingText || 'ADDING...';
                return this.isInStock ? (config.inStockText || 'ADD TO CART') : (config.outOfStockText || 'OUT OF STOCK');
            },

            async addToCart() {
                if (!this.isInStock || this.isAdding) return;

                if (!this.isAuthenticated) {
                    window.location.href = config.loginUrl || '/login';
                    return;
                }

                this.isAdding = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(config.addUrl || '/cart/add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            product_variant_id: this.activeVariant.id,
                            quantity: this.quantity
                        })
                    });

                    if (response.status === 401) {
                        window.location.href = config.loginUrl || '/login';
                        return;
                    }

                    const data = await response.json();
                    if (data.require_login) {
                        window.location.href = data.redirect || config.loginUrl || '/login';
                        return;
                    }

                    if (data.success) {
                        window.dispatchEvent(new CustomEvent('open-cart'));
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                    } else {
                        alert(data.message || 'Error adding item to cart.');
                    }
                } catch (err) {
                    console.error('Add to cart error:', err);
                } finally {
                    this.isAdding = false;
                }
            }
        };
    };
}
</script>
@endsection
