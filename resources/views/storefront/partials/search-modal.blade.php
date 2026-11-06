<!-- Predictive Instant Search Modal (Alpine.js) -->
<div x-data="searchModal" x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.window.escape="closeModal()">
    <!-- Backdrop Blur Overlay -->
    <div @click="closeModal()" 
         x-show="isOpen" 
         x-transition:enter="ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-brand-black/60 backdrop-blur-sm transition-opacity"></div>

    <!-- Centered Modal Container -->
    <div class="min-h-screen px-4 pt-12 sm:pt-20 pb-20 text-center flex items-start justify-center">
        <div x-show="isOpen" 
             @click.away="closeModal()"
             x-transition:enter="ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-brand-border text-left">

            <!-- Search Input Header -->
            <form action="{{ route('shop.index') }}" method="GET" @submit="if (!query.trim()) $event.preventDefault()" class="relative border-b border-brand-border bg-brand-offwhite">
                <div class="flex items-center px-4 py-3.5 sm:py-4">
                    <svg class="h-5 w-5 text-neutral-400 shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input id="search-modal-input" 
                           type="text" 
                           name="q" 
                           x-model="query" 
                           @input="onInput()" 
                           @keydown="onKeyDown($event)"
                           autocomplete="off" 
                           placeholder="{{ __('store.search_placeholder') }}" 
                           class="w-full bg-transparent text-sm sm:text-base font-display font-medium text-brand-black placeholder-neutral-400 focus:outline-none border-0 p-0 ring-0 focus:ring-0">
                    
                    <!-- Loading Spinner -->
                    <div x-show="loading" x-cloak class="mr-2">
                        <svg class="animate-spin h-4 w-4 text-brand-accent" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>

                    <!-- Clear input button -->
                    <button x-show="query.length > 0" @click="query = ''; onInput()" type="button" class="p-1 text-neutral-400 hover:text-brand-black mr-2 text-xs font-bold leading-none" aria-label="Clear">
                        ✕
                    </button>

                    <!-- ESC shortcut badge -->
                    <button @click="closeModal()" type="button" class="px-2 py-1 text-[10px] font-mono font-bold uppercase bg-neutral-200 hover:bg-neutral-300 rounded text-neutral-600 transition" aria-label="Close">
                        ESC
                    </button>
                </div>
            </form>

            <!-- Search Modal Body -->
            <div class="max-h-[60vh] overflow-y-auto divide-y divide-brand-border">

                <!-- 1. EMPTY QUERY STATE: Popular searches & Quick Categories -->
                <div x-show="!query.trim()" class="p-5 sm:p-6 space-y-6">
                    <div>
                        <h4 class="text-[11px] font-display uppercase tracking-widest font-bold text-neutral-400 mb-3">
                            Popular Searches
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in popularSearches" :key="item">
                                <button type="button" 
                                        @click="setQuery(item)" 
                                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-display bg-brand-bone hover:bg-brand-black hover:text-white text-brand-black border border-brand-border transition-colors">
                                    <svg class="w-3 h-3 mr-1.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="popularCategories.length > 0">
                        <h4 class="text-[11px] font-display uppercase tracking-widest font-bold text-neutral-400 mb-3">
                            {{ __('store.filter_category') }}
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="cat in popularCategories" :key="cat.slug">
                                <a :href="cat.url" class="p-3 bg-neutral-50 hover:bg-neutral-100 border border-brand-border rounded-lg text-xs font-display font-semibold uppercase tracking-wider text-brand-black transition flex items-center justify-between group">
                                    <span x-text="cat.name"></span>
                                    <span class="text-neutral-400 group-hover:text-brand-accent group-hover:translate-x-0.5 transition-transform">→</span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 2. HAS QUERY STATE: Category chips + Product Results -->
                <div x-show="query.trim().length > 0">
                    
                    <!-- Matching Categories (if any) -->
                    <template x-if="results.categories && results.categories.length > 0">
                        <div class="px-5 py-3 bg-brand-card-bg border-b border-brand-border flex items-center gap-2 flex-wrap text-xs">
                            <span class="text-neutral-400 uppercase font-display tracking-wider text-[11px]">{{ __('store.filter_category') }}:</span>
                            <template x-for="cat in results.categories" :key="cat.slug">
                                <a :href="cat.url" class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-brand-black hover:text-white border border-brand-border rounded-full text-brand-black font-display font-semibold uppercase tracking-wider transition text-[11px]">
                                    <span x-text="cat.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <!-- Product Results List -->
                    <template x-if="results.products && results.products.length > 0">
                        <div>
                            <div class="px-5 py-2.5 text-[11px] font-display uppercase tracking-wider font-bold text-neutral-400 bg-neutral-50/50">
                                {{ __('store.products_found') }}: <span class="text-brand-black" x-text="results.total"></span>
                            </div>
                            <ul class="divide-y divide-brand-border/60">
                                <template x-for="(prod, idx) in results.products" :key="prod.id">
                                    <li>
                                        <a :href="prod.url" 
                                           :class="{'bg-neutral-100': selectedIndex === idx, 'hover:bg-neutral-50': selectedIndex !== idx}"
                                           class="flex items-center justify-between p-4 transition group">
                                            <div class="flex items-center space-x-3.5 min-w-0">
                                                <img :src="prod.image_url" :alt="prod.name" class="w-12 h-12 sm:w-14 sm:h-14 object-cover bg-neutral-100 rounded border border-brand-border shrink-0">
                                                <div class="min-w-0">
                                                    <span class="block text-[11px] uppercase tracking-wider text-neutral-400 font-display font-semibold" x-text="prod.category"></span>
                                                    <h5 class="text-sm font-display font-bold uppercase text-brand-black group-hover:text-brand-accent transition truncate" x-text="prod.name"></h5>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs font-semibold text-brand-black" x-text="prod.price"></span>
                                                        <span x-show="prod.compare_at_price" class="text-[11px] text-neutral-400 line-through" x-text="prod.compare_at_price"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="shrink-0 flex items-center space-x-2 pl-3">
                                                <span x-show="prod.is_in_stock" class="hidden sm:inline-block text-[10px] font-display uppercase tracking-wider font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                                    In Stock
                                                </span>
                                                <span class="text-neutral-300 group-hover:text-brand-black group-hover:translate-x-1 transition-transform">→</span>
                                            </div>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Empty State (No matches found) -->
                    <template x-if="!loading && results.products && results.products.length === 0">
                        <div class="p-8 text-center space-y-4">
                            <div class="w-12 h-12 mx-auto rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black">
                                    No products found for "<span x-text="query"></span>"
                                </h4>
                                <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                                    Check your spelling or try searching for generic silhouettes like "tee", "hoodie", or "pants".
                                </p>
                            </div>
                            <div class="pt-2">
                                <span class="text-xs font-display uppercase tracking-wider text-neutral-400 font-semibold block mb-2">Try searching for:</span>
                                <div class="flex flex-wrap justify-center gap-1.5">
                                    <button type="button" @click="setQuery('Tee')" class="px-2.5 py-1 text-xs bg-neutral-100 hover:bg-neutral-200 rounded-full font-display uppercase text-brand-black transition">Tees</button>
                                    <button type="button" @click="setQuery('Hoodie')" class="px-2.5 py-1 text-xs bg-neutral-100 hover:bg-neutral-200 rounded-full font-display uppercase text-brand-black transition">Hoodies</button>
                                    <button type="button" @click="setQuery('Pants')" class="px-2.5 py-1 text-xs bg-neutral-100 hover:bg-neutral-200 rounded-full font-display uppercase text-brand-black transition">Pants</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Footer: View all results button -->
            <template x-if="query.trim().length > 0 && results.total > 0">
                <div class="p-3.5 bg-brand-offwhite border-t border-brand-border flex items-center justify-between text-xs">
                    <span class="text-neutral-500 font-display">
                        Showing top <strong class="text-brand-black" x-text="Math.min(6, results.total)"></strong> of <strong class="text-brand-black" x-text="results.total"></strong>
                    </span>
                    <a :href="results.view_all_url" class="inline-flex items-center gap-1 font-display font-bold uppercase tracking-wider text-brand-accent hover:text-brand-black transition">
                        <span>View all <span x-text="results.total"></span> results</span>
                        <span>→</span>
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>
