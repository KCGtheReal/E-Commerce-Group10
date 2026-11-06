# RESET — Modern Streetwear Essentials & Bakong KHQR E-Commerce

> **"Start fresh."** — *Form. Substance. Renewal. Engineered in Phnom Penh.*

![RESET E-Commerce Platform](public/images/logo.svg)

A complete, production-ready e-commerce online clothing store built with **Laravel 11**, **Tailwind CSS**, **Alpine.js**, **Filament v3 Admin**, and native **Bakong KHQR** payment gateway integration certified by the National Bank of Cambodia (NBC).

---

## Table of Contents

1. [Brand & Design Identity](#1-brand--design-identity)
2. [Architecture & Features](#2-architecture--features)
3. [Payment Flow: Bakong KHQR Native](#3-payment-flow-bakong-khqr-native)
4. [Quick Start & Local Setup](#4-quick-start--local-setup)
5. [Environment & Secrets Configuration](#5-environment--secrets-configuration)
6. [Bakong KHQR Setup & Testing Guide](#6-bakong-khqr-setup--testing-guide)
7. [Background Daemons & Scheduled Tasks](#7-background-daemons--scheduled-tasks)
8. [Filament v3 Admin Panel](#8-filament-v3-admin-panel)
9. [Automated Test Suite](#9-automated-test-suite)
10. [Docker & Production Deployment](#10-docker--production-deployment)
11. [Pre-Launch Checklist](#11-pre-launch-checklist)

---

## 1. Brand & Design Identity

- **Brand Name**: RESET
- **Tagline**: *"Start fresh."*
  - *Alternative 1*: "Form. Substance. Renewal."
  - *Alternative 2*: "Strip away the noise."
  - *Alternative 3*: "Daily structural essentials."
- **Positioning**: Modern minimalist streetwear essentials. Boxy heavyweight organic cotton tees (240–280 GSM), French terry fleece hoodies, and relaxed architectural silhouettes designed in Phnom Penh.
- **Color Palette**:
  - **Onyx Black** (`#111111`): Primary dark tone, dominant editorial backdrop.
  - **Bone White** (`#F5F4F0`): Warm off-white surface tone.
  - **Off-White / Light Cream** (`#FAF9F5`): Clean secondary backdrop.
  - **Terracotta / Safety Orange** (`#E65100`): High-contrast functional accent for buttons, badges, and status pulses.
- **Typography**:
  - Latin Display: **Space Grotesk** (geometric, high-contrast, modern architectural sans).
  - Khmer Script: **Kantumruy Pro** & **Noto Sans Khmer** (legible, refined, balanced contrast).
- **Localization**:
  - Fully bilingual English (`en`) and Khmer (`km`).
  - Dynamic language switcher with session persistence.
  - Dual currency display: United States Dollar (`$ USD`) and Cambodian Riel (`៛ KHR`) using official NBC exchange rates.

---

## 2. Architecture & Features

- **Framework**: Laravel 11 (`laravel/framework` v13.32) running on PHP 8.4.
- **Frontend Stack**: Tailwind CSS v3 + Alpine.js v3 + Vite.
- **Admin Panel**: Filament v3 (`/admin`) with full resource CRUD, manual order verification modals, KHQR status checks, and KPI widgets.
- **Zero Mock Buttons**: Every button, status polling endpoint, variant selector, and discount code works end-to-end with real database transactions and stock reservations.
- **Security & Concurrency**:
  - Server-side price calculation in USD cents (prevents client-side price tampering).
  - Inventory reservation for 5 minutes during payment window.
  - Pessimistic locking (`lockForUpdate()`) ensures zero double-spending and prevents duplicate inventory decrements.
  - Rate limiting on status polling endpoints (`throttle:60,1`).
  - CSRF and signed URLs for order invoices.

---

## 3. Payment Flow: Bakong KHQR Native

```
[ Customer Cart ]
       │
       ▼ (Submits Shipping Details)
[ Order Created: Pending Payment ]
       │ ──► Reserves stock for 5 minutes (300s)
       ▼
[ Dynamic KHQR Generated ]
       │ ──► Encodes order number, exact amount, currency, merchant ID
       ▼
[ Payment Screen with Countdown Timer ]
       │
       ├──► Customer scans QR with ABA / ACLEDA / Bakong / Wing
       │
       ├──► Reactive Alpine.js polls GET /payments/{payment}/status every 3s
       │
       ▼ (Server verifies transaction via NBC MD5 hash)
[ Order Finalized: Status Paid ]
       │ ──► Stock permanently decremented
       │ ──► Inventory reservation released
       │ ──► Confirmation email dispatched
       │ ──► Optional Telegram alert sent to merchant
       │ ──► Auto-redirects to Order Success screen
       ▼
[ Printable Invoice & Tracking History ]
```

### Automatic Expiration & Inventory Restock
If the customer fails to pay within 300 seconds:
1. The payment status transitions to `expired`.
2. The order status transitions to `expired`.
3. Reserved inventory is immediately returned to available stock.
4. The customer can click **"Generate New QR Code"** to check live stock and re-attempt payment with a fresh 5-minute window.

---

## 4. Quick Start & Local Setup

### System Requirements
- PHP 8.4+ with `pdo`, `pdo_sqlite` or `pdo_mysql`, `bcmath`, `curl`, `mbstring`, `gd`, `zip`
- Node.js 20+ & npm
- Composer 2.8+

### Step 1: Clone and Install Dependencies
```bash
git clone <repository-url> reset-store
cd reset-store

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### Step 2: Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Database & Seeding
```bash
# Touch SQLite database (if using SQLite default)
touch database/database.sqlite

# Run migrations and seed complete product catalog
php artisan migrate:fresh --seed
```

The database seeder populates:
- **1 Administrator User**: `admin@reset.store` / password: `password`
- **1 Test Customer**: `customer@reset.store` / password: `password`
- **4 Collections**: Heavyweight Tees, Hoodies & Fleece, Pants & Bottoms, Caps & Headwear
- **24 Realistic Streetwear Products** with full color and size variants (XS through XXL)
- **3 Working Promo Codes**:
  - `RESET10`: 10% discount off total order
  - `FREESHIP`: Free shipping coupon
  - `STARTFRESH`: $5.00 fixed discount on orders over $30.00
- **Store Settings**: Exchange rate (4,100 KHR/$), province shipping fees, free shipping threshold ($50.00).

### Step 4: Build Assets & Start Dev Server
```bash
# Compile frontend
npm run build

# Start Laravel development server
php artisan serve
```

Visit the storefront at `http://localhost:8000` and the Admin Panel at `http://localhost:8000/admin`.

---

## 5. Environment & Secrets Configuration

All merchant credentials and secrets come exclusively from `.env` and are never hardcoded in source files:

```ini
APP_NAME=RESET
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (SQLite default; MySQL supported)
DB_CONNECTION=sqlite

# Bakong KHQR Configuration
BAKONG_MOCK=true                               # Set to 'false' in production!
BAKONG_TOKEN=your_nbc_production_token_here    # Issued by National Bank of Cambodia
BAKONG_ACCOUNT_ID=reset_store@devb             # Your Bakong account ID (e.g. username@bank)
BAKONG_ACCOUNT_TYPE=individual                 # 'individual' or 'merchant'
BAKONG_MERCHANT_NAME="RESET Store"
BAKONG_MERCHANT_CITY="Phnom Penh"
BAKONG_MERCHANT_ID=RESET001
BAKONG_ACQUIRING_BANK="Dev Bank"
BAKONG_CURRENCY=USD                            # Primary charge currency ('USD' or 'KHR')
BAKONG_QR_TTL_SECONDS=300                      # QR lifetime in seconds (5 minutes)

# Exchange Rate & Shipping
EXCHANGE_RATE=4100                             # KHR per 1 USD
FREE_SHIPPING_THRESHOLD=5000                   # $50.00 in USD cents

# Optional Telegram Payment Alerts
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

---

## 6. Bakong KHQR Setup & Testing Guide

### A. How to Obtain Production NBC Credentials
1. Register a corporate or merchant account with a Cambodian bank participating in the Bakong network (e.g., ABA Bank, ACLEDA, Canadia, Wing).
2. Apply for Bakong Merchant / Open API access through the National Bank of Cambodia (NBC) developer portal or your acquiring bank's digital banking integration division.
3. Obtain your **Production API Token** and your official **Bakong Account ID** (e.g., `reset_store@abab`).
4. Set `BAKONG_MOCK=false`, `BAKONG_TOKEN=<token>`, and `BAKONG_ACCOUNT_ID=<account_id>` in `.env`.

### B. Testing with Built-in Mock Simulator (`BAKONG_MOCK=true`)
In local development and staging environments:
1. `BAKONG_MOCK=true` generates deterministic, valid EMV-compliant KHQR payloads.
2. The payment screen displays a highlighted **"Dev / Test Mode"** badge.
3. You can test real 3-second status polling or click **"⚡ Simulate Instant Payment"** to immediately finalize the payment, decrement inventory, and transition to the order confirmation page.

### C. Testing Small Live Payments
1. Set `BAKONG_MOCK=false` and configure a live token.
2. In the Admin Panel, adjust a test product variant's price to `$0.50` (or `2,000 ៛`).
3. Scan the QR code using your actual **ABA Mobile**, **ACLEDA mobile**, or **Bakong App**.
4. Confirm the transaction. The web screen will detect payment settlement within 3 seconds and redirect to the invoice receipt.

---

## 7. Background Daemons & Scheduled Tasks

RESET includes two automated maintenance commands configured in `routes/console.php`:

| Command | Frequency | Description |
|---|---|---|
| `php artisan payments:check-pending` | Every 10 seconds | Queries NBC gateway for all active payments to catch background settlements. |
| `php artisan payments:expire-stale` | Every minute | Cancels expired orders and restores reserved inventory to public stock. |

### Running the Scheduler Locally
```bash
php artisan schedule:work
```

### Production Crontab Setup
On your Linux VPS, add the standard Laravel cron:
```bash
* * * * * cd /path-to-reset && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor Queue Worker (`/etc/supervisor/conf.d/reset-worker.conf`)
```ini
[program:reset-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/reset/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/reset/storage/logs/worker.log
```

---

## 8. Filament v3 Admin Panel

Access the administration panel at `/admin`.

**Administrator Credentials**:
- **Email**: `admin@reset.store`
- **Password**: `password`

### Capabilities:
- **Shop Management**:
  - **Products**: Create, edit, publish/unpublish products; manage size/color variants with SKU and stock counters; manage image galleries.
  - **Orders**:
    - Status workflow: `Pending Payment` → `Paid` → `Packed` → `Shipped` → `Delivered`.
    - **"Mark Paid"** modal: Requires a mandatory administrative memo/note for full auditability.
    - **"Check KHQR"** action: Re-checks settlement directly with the NBC gateway.
    - **"Fulfill / Ship"** action: Updates carrier name and tracking number, and dispatches an automated customer tracking email.
    - **"Invoice"** link: Opens a clean, printable tax invoice.
  - **Payments**: Audit log of all MD5 hashes, currency amounts, timestamps, and raw JSON gateway responses.
  - **Categories & Discounts**: Create coupon codes (percentage or fixed amount), usage limits, and expiration dates.
  - **Reviews**: Moderate and approve customer reviews.
- **Analytics & Widgets**:
  - Live settled revenue ($ USD and approximate ៛ KHR).
  - Settled orders count.
  - Pending Bakong payments count.
  - Low stock variant alerts (stock ≤ 5).

---

## 9. Automated Test Suite

The project includes **54 automated PHPUnit tests** with **153 assertions** covering all critical e-commerce, payment, and authorization flows.

```bash
# Run all tests
php artisan test

# Run individual test suites
php artisan test --filter=CartTest
php artisan test --filter=BakongPaymentTest
php artisan test --filter=CheckoutFlowTest
php artisan test --filter=StorefrontTest
php artisan test --filter=AdminPanelTest
```

### Test Coverage Highlights:
- ✅ Cart additions, stock limit validations, and item updates.
- ✅ Promo code validation (`RESET10` percentage, `FREESHIP`).
- ✅ Guest cart migration to customer account upon login.
- ✅ Dynamic KHQR generation, MD5 hash verification, and mock payment simulation.
- ✅ Automatic payment expiration and stock reservation release.
- ✅ Idempotent order finalization (preventing double inventory decrement).
- ✅ Delivery fee calculation across all 25 Cambodian provinces.
- ✅ Multi-language locale switching (`en` and `km`).
- ✅ Filament admin authorization and resource accessibility.

---

## 10. Docker & Production Deployment

### Quick Docker Compose Launch
```bash
# Build and run application and MySQL containers
docker compose up -d --build

# Run migrations inside the container
docker compose exec app php artisan migrate --force

# Seed database
docker compose exec app php artisan db:seed --force
```

The container runs **PHP 8.4**, **Nginx**, and **Supervisor** handling PHP-FPM, Nginx, Queue Workers, and the Cron scheduler simultaneously.

---

## 11. Pre-Launch Checklist

Before launching RESET in production:

- [ ] Run `composer install --no-dev --optimize-autoloader`.
- [ ] Run `npm run build` to ensure production Vite assets are compiled.
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
- [ ] Set `BAKONG_MOCK=false` in `.env`.
- [ ] Ensure `BAKONG_TOKEN` contains your validated NBC production token.
- [ ] Confirm `BAKONG_ACCOUNT_ID` matches your official merchant account ID.
- [ ] Configure `MAIL_*` credentials for automated dispatch notifications.
- [ ] Change the default administrator password in `/admin`.
- [ ] Verify SSL certificate (`https://`) on your domain.
- [ ] Execute a `$0.50` live test transaction using a physical banking app to verify end-to-end reconciliation.

---

**RESET Store** — Built with precision, integrity, and uncompromising dedication to Cambodian e-commerce excellence.
