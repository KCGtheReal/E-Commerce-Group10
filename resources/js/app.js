import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global Cart Drawer Component
Alpine.data('cartDrawer', () => ({
    open: false,
    loading: false,
    itemCount: 0,
    items: [],
    subtotalCents: 0,
    formattedSubtotal: '0 ៛',
    formattedDiscount: '0 ៛',
    discountCode: '',
    formattedShipping: '0 ៛',
    formattedTotal: '0 ៛',
    freeShippingQualified: false,
    freeShippingRemainingFormatted: '200,000 ៛',
    promoCodeInput: '',
    promoError: '',

    get freeShippingProgress() {
        const threshold = window.RESET_STORE?.freeShippingThreshold || 200000;
        return Math.min(100, Math.round((this.subtotalCents / (threshold > 0 ? threshold : 1)) * 100));
    },

    init() {
        this.fetchCart();
        window.addEventListener('cart-updated', () => this.fetchCart());
        window.addEventListener('open-cart', () => {
            this.open = true;
            this.fetchCart();
        });
    },

    async fetchCart() {
        try {
            const response = await fetch('/cart/data', {
                headers: { 'Accept': 'application/json' }
            });
            if (response.ok) {
                const data = await response.json();
                this.updateState(data);
            }
        } catch (e) {
            console.error('Cart fetch error', e);
        }
    },

    updateState(data) {
        this.itemCount = data.item_count;
        this.items = data.items;
        this.subtotalCents = data.subtotal_cents || 0;
        this.formattedSubtotal = data.formatted_subtotal;
        this.formattedDiscount = data.formatted_discount;
        this.discountCode = data.discount_code || '';
        this.formattedShipping = data.formatted_shipping;
        this.formattedTotal = data.formatted_total;
        this.freeShippingQualified = data.free_shipping_qualified;
        this.freeShippingRemainingFormatted = data.free_shipping_remaining_formatted;
    },

    async updateQuantity(itemId, quantity) {
        this.loading = true;
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(`/cart/items/${itemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ quantity })
            });
            const data = await response.json();
            if (data.cart) {
                this.updateState(data.cart);
            }
        } catch (e) {
            console.error('Update quantity error', e);
        } finally {
            this.loading = false;
        }
    },

    async removeItem(itemId) {
        this.loading = true;
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(`/cart/items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                }
            });
            const data = await response.json();
            if (data.cart) {
                this.updateState(data.cart);
            }
        } catch (e) {
            console.error('Remove item error', e);
        } finally {
            this.loading = false;
        }
    },

    async applyPromo() {
        if (!this.promoCodeInput.trim()) return;
        this.loading = true;
        this.promoError = '';
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/cart/discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ code: this.promoCodeInput })
            });
            const data = await response.json();
            if (response.ok && data.cart) {
                this.updateState(data.cart);
                this.promoCodeInput = '';
            } else {
                this.promoError = data.message || 'Invalid promo code.';
            }
        } catch (e) {
            this.promoError = 'Failed to apply promo code.';
        } finally {
            this.loading = false;
        }
    },

    async removePromo() {
        this.loading = true;
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/cart/discount', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                }
            });
            const data = await response.json();
            if (data.cart) {
                this.updateState(data.cart);
            }
        } catch (e) {
            console.error('Remove promo error', e);
        } finally {
            this.loading = false;
        }
    }
}));

// Product Detail Page Component
Alpine.data('productPage', (config = {}) => ({
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
}));

// Bakong KHQR Live Status Polling & Countdown
Alpine.data('khqrPayment', (config) => ({
    secondsRemaining: config.initialSeconds || 300,
    status: 'pending',
    isChecking: false,
    pollingInterval: null,
    timerInterval: null,
    isExpired: false,
    isPaid: false,

    init() {
        this.startTimer();
        this.startPolling();

        // Immediately check status when user switches back to the tab from ABA/banking app
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !this.isExpired && !this.isPaid) {
                this.checkStatus();
            }
        });
        window.addEventListener('focus', () => {
            if (!this.isExpired && !this.isPaid) {
                this.checkStatus();
            }
        });
    },

    get formattedCountdown() {
        const mins = Math.floor(this.secondsRemaining / 60);
        const secs = this.secondsRemaining % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    },

    startTimer() {
        this.timerInterval = setInterval(() => {
            if (this.secondsRemaining > 0) {
                this.secondsRemaining--;
            } else {
                this.isExpired = true;
                this.status = 'expired';
                clearInterval(this.timerInterval);
                clearInterval(this.pollingInterval);
            }
        }, 1000);
    },

    async checkStatus() {
        if (this.isExpired || this.isPaid || this.isChecking) return;
        this.isChecking = true;

        try {
            const res = await fetch(config.statusUrl, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (data.status === 'paid' && data.redirect_url) {
                    this.isPaid = true;
                    this.status = 'paid';
                    clearInterval(this.timerInterval);
                    clearInterval(this.pollingInterval);
                    window.location.href = data.redirect_url;
                } else if (data.status === 'expired') {
                    this.isExpired = true;
                    this.status = 'expired';
                    clearInterval(this.timerInterval);
                    clearInterval(this.pollingInterval);
                }
            }
        } catch (e) {
            // Silently retry on next interval
        } finally {
            this.isChecking = false;
        }
    },

    startPolling() {
        // Poll every 3 seconds
        this.pollingInterval = setInterval(() => {
            this.checkStatus();
        }, 3000);
    },

    destroy() {
        if (this.timerInterval) clearInterval(this.timerInterval);
        if (this.pollingInterval) clearInterval(this.pollingInterval);
    }
}));

// Predictive Instant Search Component
Alpine.data('searchModal', () => ({
    isOpen: false,
    query: '',
    loading: false,
    results: {
        products: [],
        categories: [],
        total: 0,
        view_all_url: '',
    },
    popularSearches: [
        'Heavyweight Boxy Tee',
        'Hoodies & Outerwear',
        'Raw Edge Tee',
        'Tactical Cargo',
        'Oversized',
    ],
    popularCategories: [],
    debounceTimer: null,
    selectedIndex: -1,

    init() {
        // Keyboard shortcuts: / or Cmd+K or Ctrl+K to open, Escape to close
        window.addEventListener('keydown', (e) => {
            if ((e.key === '/' || ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k')) && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                e.preventDefault();
                this.openModal();
            } else if (e.key === 'Escape' && this.isOpen) {
                this.closeModal();
            }
        });

        // Listen for open-search event from anywhere (e.g. mobile search icon or desktop trigger)
        window.addEventListener('open-search', () => {
            this.openModal();
        });

        // Initial fetch of popular categories/suggestions
        this.fetchSuggestions('');
    },

    openModal() {
        this.isOpen = true;
        this.selectedIndex = -1;
        this.$nextTick(() => {
            const input = document.getElementById('search-modal-input');
            if (input) {
                input.focus();
                input.select();
            }
        });
    },

    closeModal() {
        this.isOpen = false;
        this.selectedIndex = -1;
    },

    setQuery(text) {
        this.query = text;
        this.onInput();
        this.$nextTick(() => {
            const input = document.getElementById('search-modal-input');
            if (input) input.focus();
        });
    },

    onInput() {
        clearTimeout(this.debounceTimer);
        this.selectedIndex = -1;
        const q = this.query.trim();

        if (q.length === 0) {
            this.results = { products: [], categories: [], total: 0, view_all_url: '' };
            this.loading = false;
            return;
        }

        this.loading = true;
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(q);
        }, 180);
    },

    async fetchSuggestions(q) {
        try {
            const res = await fetch(`/search/suggest?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (q === '') {
                    this.popularCategories = data.categories || [];
                    if (data.popular_searches) {
                        this.popularSearches = data.popular_searches;
                    }
                } else {
                    this.results = data;
                }
            }
        } catch (e) {
            console.error('Search error', e);
        } finally {
            this.loading = false;
        }
    },

    onKeyDown(e) {
        const items = this.results.products || [];
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (this.selectedIndex < items.length - 1) {
                this.selectedIndex++;
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            } else {
                this.selectedIndex = -1;
            }
        } else if (e.key === 'Enter') {
            if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                e.preventDefault();
                window.location.href = items[this.selectedIndex].url;
            }
        }
    }
}));

Alpine.start();

// -------------------------------------------------------------
// High-Performance UX: Instant Navigation Prefetcher & Progress Bar
// -------------------------------------------------------------
(function() {
    // 1. Instant Top Navigation Progress Bar
    let progressBar = null;

    function getProgressBar() {
        if (!progressBar) {
            progressBar = document.getElementById('page-progress-bar');
            if (!progressBar) {
                progressBar = document.createElement('div');
                progressBar.id = 'page-progress-bar';
                document.body.appendChild(progressBar);
            }
        }
        return progressBar;
    }

    function isEligibleLink(anchor) {
        if (!anchor || !anchor.href) return false;
        if (anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
        if (anchor.getAttribute('rel')?.includes('external')) return false;
        if (anchor.href.startsWith('javascript:') || anchor.href.includes('#')) return false;
        if (anchor.closest('[data-no-instant]')) return false;

        try {
            const dest = new URL(anchor.href, window.location.href);
            if (dest.origin !== window.location.origin) return false;
            // Never prefetch or fake progress on destructive/admin/auth-sensitive routes
            const path = dest.pathname.toLowerCase();
            if (path.startsWith('/admin') || path.startsWith('/logout') || path.includes('/simulate')) return false;
            return dest;
        } catch {
            return false;
        }
    }

    document.addEventListener('click', function(e) {
        const a = e.target.closest('a');
        const dest = isEligibleLink(a);
        if (!dest) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (dest.pathname === window.location.pathname && dest.search === window.location.search) return;

        const bar = getProgressBar();
        bar.style.opacity = '1';
        bar.style.width = '35%';
        setTimeout(() => { if (bar.style.width === '35%') bar.style.width = '75%'; }, 180);
    }, { passive: true });

    window.addEventListener('pageshow', function() {
        const bar = getProgressBar();
        if (bar && bar.style.opacity === '1') {
            bar.style.width = '100%';
            setTimeout(() => {
                bar.style.opacity = '0';
                setTimeout(() => { bar.style.width = '0%'; }, 250);
            }, 80);
        }
    });

    // 2. Instant Speculative Prefetcher (Instant.page architecture)
    const conn = navigator.connection;
    if (conn && (conn.saveData || (conn.effectiveType || '').includes('2g'))) {
        return; // Respect Data Saver / Slow 2G connections
    }

    const prefetchedUrls = new Set();

    function prefetchUrl(url) {
        if (!url || prefetchedUrls.has(url)) return;
        prefetchedUrls.add(url);

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        document.head.appendChild(link);
    }

    let hoverTimer = null;

    // Desktop: Prefetch on 65ms hover intent
    document.addEventListener('mouseover', function(e) {
        const a = e.target.closest('a');
        const dest = isEligibleLink(a);
        if (!dest) return;
        if (dest.pathname === window.location.pathname && dest.search === window.location.search) return;

        hoverTimer = setTimeout(() => {
            prefetchUrl(dest.href);
        }, 65);
    }, { passive: true });

    document.addEventListener('mouseout', function(e) {
        const a = e.target.closest('a');
        if (a && hoverTimer) {
            clearTimeout(hoverTimer);
            hoverTimer = null;
        }
    }, { passive: true });

    // Mobile: Prefetch on touchstart (fires ~100-250ms before tap click completes)
    document.addEventListener('touchstart', function(e) {
        const a = e.target.closest('a');
        const dest = isEligibleLink(a);
        if (!dest) return;
        if (dest.pathname === window.location.pathname && dest.search === window.location.search) return;

        prefetchUrl(dest.href);
    }, { passive: true });
})();
