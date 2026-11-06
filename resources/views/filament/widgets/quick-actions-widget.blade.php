<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">
            <!-- 1. Top Meta Bar: Brand Identity & Live Gateway Status -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                        RESET STUDIO
                    </span>
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        Phnom Penh Flagship
                    </span>
                    <span class="hidden sm:inline text-gray-300 dark:text-gray-600">•</span>
                    <span class="hidden sm:inline text-xs font-mono text-gray-500 dark:text-gray-400">
                        {{ $todayDate }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::badge
                        :color="$isBakongMock ? 'warning' : 'success'"
                        icon="heroicon-m-check-badge"
                        size="sm"
                    >
                        {{ $isBakongMock ? 'Bakong Dev Mock' : 'Bakong KHQR Live' }}
                    </x-filament::badge>

                    <x-filament::badge
                        color="gray"
                        icon="heroicon-m-banknotes"
                        size="sm"
                    >
                        1 USD = {{ number_format($exchangeRate, 0) }} ៛ KHR
                    </x-filament::badge>

                    <x-filament::badge
                        color="primary"
                        icon="heroicon-m-truck"
                        size="sm"
                    >
                        Delivery: {{ $standardShippingFee === 0 ? '0 ៛ Free Promo' : number_format($standardShippingFee, 0) . ' ៛' }}
                    </x-filament::badge>
                </div>
            </div>

            <!-- 2. Middle Section: Greeting & Quick Action Buttons -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 py-4">
                <div class="space-y-1 max-w-2xl">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Welcome back, {{ $adminName }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Phnom Penh studio control center. Oversee Bakong KHQR settlements, fulfill courier dispatches, and manage garment stock.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        tag="a"
                        :href="route('filament.admin.resources.products.create')"
                        color="primary"
                        icon="heroicon-m-plus"
                        size="md"
                    >
                        New Product
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="route('filament.admin.resources.discounts.create')"
                        color="gray"
                        icon="heroicon-m-ticket"
                        size="md"
                        outlined
                    >
                        New Promo
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="route('filament.admin.resources.settings.index')"
                        color="gray"
                        icon="heroicon-m-cog-6-tooth"
                        size="md"
                        outlined
                    >
                        Settings
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="url('/')"
                        color="gray"
                        icon="heroicon-m-arrow-top-right-on-square"
                        size="md"
                        outlined
                        target="_blank"
                    >
                        Storefront
                    </x-filament::button>
                </div>
            </div>

            <!-- 3. Bottom Operational Pulse Row (Live Actionable Cards) -->
            <div class="border-t border-gray-100 dark:border-white/10 pt-4">
                <x-filament::grid :default="1" :sm="2" :lg="4" class="gap-3">
                    <!-- 1. Orders to Fulfill -->
                    <a href="{{ route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'paid']) }}"
                       class="rounded-lg p-3 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 border border-gray-200 dark:border-white/5 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg text-white"
                                 style="{{ $readyToShipCount > 0 ? 'background-color: #0284c7;' : 'background-color: #71717a;' }}">
                                <x-heroicon-m-truck class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Fulfillment</div>
                                <div class="text-xs font-bold text-gray-950 dark:text-white">{{ $readyToShipCount > 0 ? $readyToShipCount . ' Orders to Ship' : 'Queue Cleared' }}</div>
                            </div>
                        </div>
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                    </a>

                    <!-- 2. Pending Bakong KHQR -->
                    <a href="{{ route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending_payment']) }}"
                       class="rounded-lg p-3 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 border border-gray-200 dark:border-white/5 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg text-white"
                                 style="{{ $pendingPaymentsCount > 0 ? 'background-color: #d97706;' : 'background-color: #71717a;' }}">
                                <x-heroicon-m-qr-code class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bakong KHQR</div>
                                <div class="text-xs font-bold text-gray-950 dark:text-white">{{ $pendingPaymentsCount > 0 ? $pendingPaymentsCount . ' Pending Scans' : 'All Settled' }}</div>
                            </div>
                        </div>
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                    </a>

                    <!-- 3. Critical Low Stock -->
                    <a href="{{ route('filament.admin.resources.products.index') }}"
                       class="rounded-lg p-3 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 border border-gray-200 dark:border-white/5 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg text-white"
                                 style="{{ $lowStockCount > 0 ? 'background-color: #e11d48;' : 'background-color: #71717a;' }}">
                                <x-heroicon-m-exclamation-triangle class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Inventory Health</div>
                                <div class="text-xs font-bold text-gray-950 dark:text-white">{{ $lowStockCount > 0 ? $lowStockCount . ' Low Stock' : 'Stock Healthy' }}</div>
                            </div>
                        </div>
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                    </a>

                    <!-- 4. Customer Inquiries -->
                    <a href="{{ route('filament.admin.resources.contact-messages.index') }}"
                       class="rounded-lg p-3 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 border border-gray-200 dark:border-white/5 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg text-white"
                                 style="{{ $unreadInquiriesCount > 0 ? 'background-color: #7c3aed;' : 'background-color: #71717a;' }}">
                                <x-heroicon-m-chat-bubble-left-right class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Inquiries</div>
                                <div class="text-xs font-bold text-gray-950 dark:text-white">{{ $unreadInquiriesCount > 0 ? $unreadInquiriesCount . ' Unread' : 'No Inquiries' }}</div>
                            </div>
                        </div>
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                    </a>
                </x-filament::grid>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
