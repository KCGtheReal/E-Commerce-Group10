# Product Requirements Document (PRD): RESET Store — Unified Backend, Flutter Client & Firebase Firestore Sync

**Document Status:** Approved for Architecture & Implementation  
**Owner:** `@pm` (Product Manager) & `@architect` (System Architect)  
**Target Release:** Unified Platform Release (`Website Project` Laravel Web + Filament v3 `/admin` + `/api/v1` + Firebase Firestore Sync & `Mobile Project` Flutter Client)  
**Workspace Topology:** Split Frontend/Backend Workspace (`Website Project/` unified server + `Mobile Project/` Flutter-only client)

---

## 1. Problem Statement

RESET Store is an established bilingual (English / Khmer) minimalist streetwear e-commerce platform built on Laravel 13, SQLite, Tailwind CSS, Alpine.js, Filament v3 Admin (`/admin`), and native National Bank of Cambodia (NBC) Bakong KHQR payment processing.

Previously, `Mobile Project/` contained a duplicated Laravel backend, a second Filament `/admin`, and a separate `database.sqlite` file alongside the Flutter app, while `Website Project/` hosted the Blade storefront, Filament `/admin`, and the authoritative `Website Project/database/database.sqlite` (with 97/97 PHPUnit tests passing) without `/api/v1/*`. Maintaining two diverging backends and two SQLite databases risks inventory split-brain, duplicated administration, and configuration drift.

To eliminate backend duplication while delivering seamless multi-channel shopping and real-time cloud observability, RESET Store must:
1. **Unify the entire Laravel backend inside `Website Project/`** — hosting the Blade storefront (`routes/web.php`), the **ONE shared Filament v3 Admin Panel (`/admin`)**, and the stateless **`/api/v1/*` REST API + OpenAPI (`/api/openapi.json`, `/api/docs`)** over the preserved `Website Project/database/database.sqlite`.
2. **Clean `Mobile Project/` to Flutter-only** — removing all duplicated Laravel, Filament, and SQLite files from `Mobile Project/` and pointing the Flutter client (`lib/`, `pubspec.yaml`, `android/`, `ios/`, `web/`) and `start-flutter-app.bat` directly to `Website Project` (`http://127.0.0.1:8000` / `http://10.0.2.2:8000`).
3. **Integrate Firebase Cloud Firestore Sync** — keeping `Website Project/database/database.sqlite` as the ACID transactional system of record for Filament v3 and Bakong KHQR `lockForUpdate()` operations, while adding a pure-PHP `FirebaseFirestoreService` (RS256 JWT via `openssl_sign` -> Firestore REST API v1), `config/firebase.php`, `php artisan app:firebase-sync`, and `DB::afterCommit` model sync hooks (`Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, `Payment`) without exposing PII in public documents (`FIREBASE_ENABLED` defaults to `false`).

---

## 2. Binding Stakeholder Decisions & Constraints

These decisions were confirmed directly with the Executive Stakeholder and are non-negotiable boundaries for all engineering phases:

1. **Unified Backend in `Website Project/` & Preserved SQLite Data:**
   - Consolidate Blade storefront (`routes/web.php`), Filament v3 Admin (`/admin`), and stateless `/api/v1/*` + OpenAPI (`/api/openapi.json`, `/api/docs`) inside `Website Project/`.
   - Preserve `Website Project/database/database.sqlite` and all existing baseline rows (**4 categories, 24 products, 168 variants, 48 images, 5 users, 3 discounts, 11 settings, 24 reviews, 1 address, 1 wishlist**). Never run `migrate:fresh` against `database/database.sqlite`.
   - Apply only the 3 non-destructive additive migrations (`api_tokens`, `add_api_performance_indexes`, `add_perf_covering_indexes`).
2. **Flutter-Only `Mobile Project/`:**
   - Remove duplicated Laravel/Filament/SQLite directories and files (`app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, `artisan`, `composer.json`, etc.) from `Mobile Project/` so `Mobile Project/` remains strictly a clean Flutter application (`pubspec.yaml`, `lib/`, `test/`, `android/`, `ios/`, `web/`).
   - Point `Mobile Project` Flutter API configuration and `start-flutter-app.bat` directly to `Website Project` (`http://127.0.0.1:8000` for Web/Desktop/iOS and `http://10.0.2.2:8000` for Android Emulator).
3. **Web-Only Filament v3 Admin Panel (`/admin`):**
   - Keep the **ONE shared Filament v3 Admin Panel (`/admin`)** exclusively inside `Website Project/`. Do **NOT** duplicate Filament admin screens inside Flutter.
4. **Firebase Cloud Firestore Sync (Pure-PHP REST + `FIREBASE_ENABLED=false` Default):**
   - Retain SQLite in `Website Project/` for Filament v3 + Eloquent ACID Bakong KHQR transactions (`lockForUpdate()`).
   - Add pure-PHP `FirebaseFirestoreService` (Service Account RS256 JWT via PHP `openssl_sign` -> Google OAuth2 token -> Firestore REST API v1) + `config/firebase.php` + `php artisan app:firebase-sync` + `DB::afterCommit` sync hooks for `Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, and `Payment`.
   - Strictly exclude PII (`users.password`, `remember_token`, `api_tokens`, customer emails/phones/street addresses, raw `bakong_response`) from public Firestore documents.
   - `FIREBASE_ENABLED` MUST default to `false` until Firebase CLI login + Project ID selection is completed by the stakeholder and `firestore.rules` is authored by `@firestore-rules-author`.
5. **No Remote Git Push:**
   - **NEVER execute `git push`** or push code to GitHub. Local files/verification only.

---

## 3. Codebase Data & Parity Facts (Verified Baseline)

Engineering teams MUST respect these verified conventions from the existing `Website Project` and `/api/v1` baseline:

- **Monetary Base Unit (`*_cents` columns):** All integer price/amount fields in SQLite (`base_price_cents`, `compare_at_price_cents`, `price_cents`, `subtotal_cents`, `discount_cents`, `shipping_fee_cents`, `total_cents`, `min_spend_cents`, `free_shipping_threshold`) store **base KHR (៛)** integer amounts (e.g., `free_shipping_threshold = 200000` = `200,000 ៛`; `RESET10` `min_spend_cents = 100000` = `100,000 ៛`; `STARTFRESH` `value = 60000` = `60,000 ៛` on `min_spend_cents = 250000` = `250,000 ៛`). USD display and USD charge conversion divide base KHR by live `settings.exchange_rate` (default `4100`).
- **Bakong Configuration (`config/bakong.php`):** Default currency (`KHR` or `USD`), `qr_ttl_seconds` (configurable 300–600s / 5–10 minutes), `mock` mode (`BAKONG_MOCK=true` and `APP_ENV !== 'production'`).
- **Express Delivery Parity Rule:** `standard` delivery is `0 ៛` across all 25 Cambodian provinces and `express` delivery is `12,000 ៛` exclusively when `province === 'Phnom Penh'`. Both Blade checkout and `/api/v1` checkout (`CartService` / `OrderService`) MUST enforce this server-side and reject non-Phnom Penh `express` requests on `/api/v1/checkout` with HTTP `422`.
- **Product Detail Tabs Parity:** `Product` stores `details` JSON (`fabric`, `care`, plus optional `fit` and `shipping` keys) and `lang/en|km/store.php` defines 5 expandable tabs (`Description`, `Fabric & Material`, `Care Instructions`, `Fit & Sizing`, `Shipping & Delivery`). API responses must return all 5 localized `detail_tabs` with clean EN/KM fallback defaults when `fit` or `shipping` are absent in SQLite.

---

## 4. Target Personas & Use Cases

| Persona | Description | Primary Goals |
| :--- | :--- | :--- |
| **P1: Storefront & Mobile Shopper (Guest)** | Urban Cambodian Gen-Z / millennial browsing via Flutter (`Mobile Project`) or Blade Web (`Website Project`) without an account. | Browse collections, search with instant suggestions, switch EN/KM & KHR/USD, toggle dark mode, manage guest cart (`X-Cart-Token` / session), complete guest checkout, pay via Bakong KHQR, and view receipt/invoice. |
| **P2: Registered RESET Member** | Returning customer (`customer@reset.store`, `kongchhunyt@gmail.com`) shopping across web and mobile. | Sign in (merging any guest cart automatically), save favorite items to wishlist, write verified buyer reviews, manage saved shipping addresses, track order fulfillment statuses, re-pay pending/expired orders, and view tax invoices. |
| **P3: Store Administrator** | RESET staff managing catalog, settings, inventory, and KHQR reconciliation exclusively via `Website Project` Web Filament v3 Admin (`/admin`). | Edits products, stock, settings (`exchange_rate`, banners), and order fulfillment statuses (`paid` -> `packed` -> `shipped` -> `delivered`, tracking numbers). Changes reflect immediately in Blade, `/api/v1/*` (Flutter), and—when `FIREBASE_ENABLED=true`—Cloud Firestore. |

---

## 5. User Stories & Binary Acceptance Criteria

### Module 1: App Shell, Brand Identity, Localization, Currency & Theming

**User Stories:**
- *As a shopper, I want the app to reflect RESET's minimalist editorial aesthetic in both Light and Dark modes so that browsing feels cohesive across devices.*
- *As a Cambodian or international shopper, I want to switch between English (`en`) and Khmer (`km`), and between KHR (`៛`) and USD (`$`), with live store settings.*

**Binary Acceptance Criteria:**
- [ ] **AC-1.1:** App loads store bootstrap settings from `GET /api/v1/bootstrap`, retrieving `store_name`, `store_tagline`, `store_tagline_km`, `exchange_rate`, `free_shipping_threshold`, `announcement_banner_en`, `announcement_banner_km`, `province_shipping_fees` (25 provinces), `express_shipping_fee_khr`, `express_shipping_province`, `bakong_mock`, `bakong_currency`, and `qr_ttl_seconds`.
- [ ] **AC-1.2:** UI uses RESET exact brand palette tokens: Onyx Black (`#111111`), Bone White (`#F5F4F0`), Off-White (`#FAF9F5`), Terracotta (`#E65100`), Dark Mode background (`#0D0D0D`), and KHQR Red (`#E21A1A`), paired with `Space Grotesk` (Latin display/headings), `Kantumruy Pro` / `Noto Sans Khmer` (Khmer script), and monospace tabular figures.
- [ ] **AC-1.3:** Top announcement bar renders the localized `announcement_banner_en` or `announcement_banner_km` from live `settings`.
- [ ] **AC-1.4:** Theme toggle switches immediately between Light Mode (`#FAF9F5` / `#F5F4F0`) and Dark Mode (`#0D0D0D`) and persists selection locally across app restarts.
- [ ] **AC-1.5:** Language toggle switches all UI copy, category names, product names/taglines/descriptions, and info pages between English (`en`) and Khmer (`km`), sends `X-Locale: en|km` / `Accept-Language: en|km` to `/api/v1`, and persists locally.
- [ ] **AC-1.6:** Currency toggle switches all displayed prices, cart totals, free shipping threshold hints, and filter bounds between `KHR` (formatted `#,### ៛`) and `USD` (formatted `$#.##`, calculated as `base_khr / settings.exchange_rate`), and sends `X-Currency: KHR|USD` on API requests.

---

### Module 2: Storefront Home & Newsletter

**User Stories:**
- *As a shopper, I want an editorial home screen showcasing collections, new arrivals, RESET's story, and best sellers so I can discover drops immediately.*
- *As a visitor, I want to subscribe to the RESET newsletter to receive private release updates.*

**Binary Acceptance Criteria:**
- [ ] **AC-2.1:** Home screen consumes `GET /api/v1/home` and renders in order: (1) Hero Banner ("Start Fresh" headline, capsule badge, "Explore Collection" CTA -> Shop, "Read Our Story" CTA -> About Us), (2) 3-item Trust Banner (Bakong KHQR Certified, Free Delivery over live `free_shipping_threshold`, Heavyweight Organic Cotton), (3) Featured Collections (4 active categories sorted by `sort_order`), (4) New Arrivals showcase, (5) Brand Editorial Story block with CTA to About Us, (6) Best Sellers showcase.
- [ ] **AC-2.2:** Each product card displays primary image, localized category name, localized product name, localized tagline, price in active currency, strikethrough `compare_at_price` + `SALE` badge when `is_on_sale`, and `BESTSELLER` badge when `is_best_seller`.
- [ ] **AC-2.3:** Tapping any category card navigates to Shop filtered by that category slug; tapping any product card navigates to Product Detail by slug.
- [ ] **AC-2.4:** Newsletter form validates email format and submits `POST /api/v1/newsletter` `{ "email": "..." }`; API normalizes email to lowercase/trimmed, persists via `firstOrCreate` in `newsletter_subscribers`, and returns HTTP 200 with localized confirmation message. Invalid emails return HTTP 422 validation error displayed inline.

---

### Module 3: Shop Catalog, Multi-Term Search, Predictive Autocomplete & Filtering

**User Stories:**
- *As a shopper, I want instant predictive search suggestions as I type so I can jump straight to matching products or categories.*
- *As a shopper, I want to filter products by category, size, color, currency-aware price range, and in-stock status, and sort results.*

**Binary Acceptance Criteria:**
- [ ] **AC-3.1:** Search modal/bar calls `GET /api/v1/search/suggest?q=<query>` with `250ms` debounce. When `q` is empty, API returns 5 `popular_searches` strings and top 4 active `categories`. When `q` is non-empty, API performs multi-term matching across product `name`, `name_km`, `tagline`, `tagline_km`, `description`, `sku_prefix`, category names/slug, and variant `color`/`sku`/`size`, returning up to 3 matching `categories`, up to 6 matching `products`, and `total` match count.
- [ ] **AC-3.2:** Shop catalog (`GET /api/v1/products`) supports query parameters: `q`, `category` (slug), `size` (single or array), `color`, `min_price` & `max_price` (currency-aware in active `X-Currency`), `in_stock` (`true`/`1`), `sort` (`featured` | `newest` | `price_low` | `price_high`), `page`, and `per_page` (default `12`).
- [ ] **AC-3.3:** Shop screen renders active filter count badge, filter sheet/drawer with available categories (with product counts), ordered sizes (`XXS`..`3XL`, `ONE SIZE`), distinct colors with hex swatches, currency-aware min/max price inputs, In-Stock Only toggle, and Clear All Filters action.
- [ ] **AC-3.4:** Empty search/filter state displays a dedicated "No products match your selected filters" view with a one-tap "Clear All Filters" button.
- [ ] **AC-3.5:** Pagination loads subsequent pages cleanly without losing active filters or sort order.

---

### Module 4: Product Detail, Variants, Gallery Zoom, Size Guide & Reviews

**User Stories:**
- *As a shopper, I want to inspect high-res product images with pinch/click zoom, select color and size, see real-time available stock, and add items to my cart or wishlist.*
- *As a buyer, I want to check cm measurements in a Size Guide modal and read or submit verified product reviews.*

**Binary Acceptance Criteria:**
- [ ] **AC-4.1:** Product Detail screen fetches `GET /api/v1/products/{slug}`, returning product details, ordered gallery `images`, all `variants`, approved `reviews`, `average_rating`, `review_count`, `has_purchased`, `in_wishlist`, and up to 4 `related_products` from the same category.
- [ ] **AC-4.2:** Image gallery allows switching thumbnails/slides; tapping main image opens a full-screen zoom modal supporting pinch-to-zoom and pan (`InteractiveViewer`).
- [ ] **AC-4.3:** Selecting a Color swatch and Size pill resolves the exact matching `ProductVariant` and dynamically updates displayed SKU, price, live `available_stock` indicator, and clamps quantity stepper to `[1, min(available_stock, 20)]`.
- [ ] **AC-4.4:** "Add to Cart" button is disabled when `available_stock <= 0` or request is in flight; on tap, calls `POST /api/v1/cart/items` (`product_variant_id`, `quantity`), updates cart badge immediately, and opens Cart drawer/sheet or shows confirmation.
- [ ] **AC-4.5:** Wishlist heart icon toggles via `POST /api/v1/wishlist/toggle` when authenticated. If tapped while unauthenticated, API returns HTTP 401 and app prompts user to Sign In.
- [ ] **AC-4.6:** "Size Guide" button opens a modal containing flat-garment measurement tables in centimeters (`cm`) for Heavyweight Tees and French Terry Hoodies, localized in EN/KM.
- [ ] **AC-4.7:** Expandable accordion sections display all 5 tabs: **Description**, **Fabric & Material**, **Care Instructions**, **Fit & Sizing**, and **Shipping & Delivery**.
- [ ] **AC-4.8:** Customer Reviews section displays rating stats and review cards with **"Verified Buyer"** badge when `is_verified_purchase === true`. Authenticated users submit reviews via `POST /api/v1/products/{slug}/reviews` (upsert per `(product_id, user_id)`).

---

### Module 5: Persistent Shopping Cart & Promo Codes (Guest + Authenticated Merge)

**User Stories:**
- *As a guest shopper, I want my cart to persist across app restarts and automatically merge into my account when I sign in or register.*
- *As a shopper, I want to see my progress toward free shipping and apply promo codes like `RESET10`, `FREESHIP`, and `STARTFRESH`.*

**Binary Acceptance Criteria:**
- [ ] **AC-5.1:** On first cart action (or app init), the Flutter app persists a guest cart token (`X-Cart-Token`) mapped server-side to `carts.session_id` without touching PHP sessions.
- [ ] **AC-5.2:** When a user logs in (`POST /api/v1/auth/login`) or registers (`POST /api/v1/auth/register`) while passing `X-Cart-Token`, the backend atomically merges guest cart items into the user's `user_id` cart (clamped to `available_stock` and max `20`), carries over active `discount_id` if user cart had none, and deletes the guest cart.
- [ ] **AC-5.3:** Cart endpoints support `GET /api/v1/cart`, `POST /api/v1/cart/items`, `PATCH /api/v1/cart/items/{item}`, `DELETE /api/v1/cart/items/{item}`, `POST /api/v1/cart/discount`, and `DELETE /api/v1/cart/discount`. Exceeding `available_stock` returns HTTP 422.
- [ ] **AC-5.4:** Cart UI displays Free Shipping progress bar computed from live `settings.free_shipping_threshold` (`200,000 ៛`).
- [ ] **AC-5.5:** Promo codes normalize uppercase/trimmed and apply `RESET10` (10% off >= `100,000 ៛`), `FREESHIP` (`min_spend = 0`), and `STARTFRESH` (`60,000 ៛` off >= `250,000 ៛`), returning HTTP 422 on invalid/expired/exhausted/below-min-spend codes.
- [ ] **AC-5.6:** Empty cart renders an empty state view with "Continue Shopping" CTA.

---

### Module 6: Checkout & Inventory Reservation

**User Stories:**
- *As an authenticated user, I want my default saved shipping address pre-filled at checkout to finish in seconds.*
- *As a customer anywhere in Cambodia, I want to select my province and delivery speed and reserve stock safely while paying.*

**Binary Acceptance Criteria:**
- [ ] **AC-6.1:** Checkout is accessible to both Guest and Authenticated users whenever cart has >= 1 item; empty cart returns HTTP 422 on API checkout.
- [ ] **AC-6.2:** When authenticated, Checkout pre-fills from `default_shipping_address` (falling back to `user.name`, `user.email`, `user.phone`).
- [ ] **AC-6.3:** Form validation requires valid `name`/`first_name`, `email`, `phone` (8–20 chars), `province` (25 provinces), `street_address`, and `shipping_method` (`standard` | `express`).
- [ ] **AC-6.4:** Delivery Method supports `standard` (`0 ៛` across all 25 provinces) and `express` (`12,000 ៛`, strictly for `province === 'Phnom Penh'`). Non-Phnom Penh `express` checkout requests return HTTP 422.
- [ ] **AC-6.5:** Checkout summary supports inline promo code apply/remove and live totals recalculation.
- [ ] **AC-6.6:** `POST /api/v1/checkout` acquires a concurrency lock (`Cache::lock`), verifies stock with `lockForUpdate()`, recomputes totals server-side, creates `Order` (`RST-YYYY-XXXXX`, `pending_payment`), reserves `ProductVariant.reserved_quantity`, increments `discount.used_count`, generates dynamic Bakong KHQR, clears the cart, and returns `order`, `payment`, and HMAC-SHA256 `order_access_token`.

---

### Module 7: Bakong KHQR Payment Flow, Order Success & Tax Invoice

**User Stories:**
- *As a customer, I want to scan a crisp native Bakong KHQR code or open my banking app directly with one tap, see dual KHR & USD amounts, and have my order confirm automatically within seconds of paying.*
- *As a developer/QA tester, I want to simulate instant payment in local mock mode (`BAKONG_MOCK=true`), and recover cleanly if a QR expires.*

**Binary Acceptance Criteria:**
- [ ] **AC-7.1:** Payment screen renders the branded KHQR card (`#E21A1A` header, `RESET Store`, `#RST-YYYY-XXXXX`, primary charged amount + dual-currency equivalent).
- [ ] **AC-7.2:** Flutter renders `qr_string` natively via `qr_flutter` on a `#FFFFFF` canvas even in Dark Mode.
- [ ] **AC-7.3:** Live `MM:SS` countdown synced to `expires_at` + 3-second polling to `GET /api/v1/payments/{id}/status` with clean lifecycle/timer management.
- [ ] **AC-7.4:** Action buttons provide "Open in Banking App" (`bakong://qr?data=...`), "Copy QR Code", and "Save / Download QR".
- [ ] **AC-7.5:** "Simulate Instant Payment" (`POST /api/v1/payments/{id}/simulate-pay`) is displayed ONLY when `bakong_mock === true`; backend strictly returns HTTP 403 when `config('bakong.mock') === false`.
- [ ] **AC-7.6:** Expired QR overlays trigger `POST /api/v1/orders/{number}/regenerate-qr` to re-verify stock, re-reserve inventory, and issue a fresh KHQR.
- [ ] **AC-7.7:** Paid transition navigates to Order Success with itemized breakdown, dual-currency totals, and Invoice/Orders/Shop CTAs.
- [ ] **AC-7.8:** Tax Invoice (`GET /api/v1/orders/{number}/invoice`) displays store details, `TAX INVOICE` header, Bill To / Ship To, itemized lines, dual-currency totals, and Bakong settlement metadata (`payment.id`, `md5`, `account_id`).

---

### Module 8: Authentication & Customer Account Portal

**User Stories:**
- *As a customer, I want to register, sign in, reset a forgotten password, and stay logged in securely via Bearer tokens.*
- *As a member, I want to manage my profile, saved addresses, wishlist, and track every order's status and shipment.*

**Binary Acceptance Criteria:**
- [ ] **AC-8.1:** `POST /api/v1/auth/register` creates `UserRole::Customer`, merges `X-Cart-Token` guest cart, stores SHA-256 `token_hash` in `api_tokens`, and returns `{ token, token_type: 'Bearer', expires_at, user, cart }`.
- [ ] **AC-8.2:** `POST /api/v1/auth/login` authenticates credentials (with rate limiting), merges `X-Cart-Token` cart, issues SHA-256 `api_tokens` entry, and returns `{ token, token_type: 'Bearer', expires_at, user, cart }`.
- [ ] **AC-8.3:** `POST /api/v1/auth/forgot-password` triggers Laravel password reset link and returns JSON status.
- [ ] **AC-8.4:** `POST /api/v1/auth/logout` deletes active `api_tokens` row; `GET /api/v1/auth/me` and `PUT /api/v1/auth/password` manage session user & password updates.
- [ ] **AC-8.5:** Profile endpoints (`GET/PATCH /api/v1/account/profile`) display read-only email and update `name`/`phone`.
- [ ] **AC-8.6:** Order endpoints (`GET /api/v1/account/orders`, `GET /api/v1/account/orders/{number}`) return paginated order summaries, 5-step `status_timeline`, `shipping_carrier`/`tracking_number`, `can_pay_now`, and `order_access_token`.
- [ ] **AC-8.7:** Saved Addresses CRUD (`GET/POST /api/v1/account/addresses`, `PUT/DELETE /api/v1/account/addresses/{id}`) enforces user ownership and single `is_default` invariant.
- [ ] **AC-8.8:** Wishlist (`GET /api/v1/wishlist`, `POST /api/v1/wishlist/toggle`) manages saved products for authenticated customers.

---

### Module 9: Info & Support Pages

**User Stories:**
- *As a shopper, I want to read About Us, FAQ, Shipping & Returns, Size Guide, Privacy Policy, and Terms of Service in my selected language, and contact RESET support directly in the app.*

**Binary Acceptance Criteria:**
- [ ] **AC-9.1:** `GET /api/v1/pages/{slug}` serves structured bilingual data for `about`, `faq`, `shipping`, `size-guide`, `privacy`, and `terms`.
- [ ] **AC-9.2:** All 6 info screens render full EN & KM content matching the storefront web views.
- [ ] **AC-9.3:** `POST /api/v1/contact` persists `ContactMessage` rows in SQLite and returns confirmation banner (`contact_message_id`).

---

### Module 10: Unified Backend & Firebase Cloud Firestore Sync

**User Stories:**
- *As the store owner, I want a single Laravel backend and single Filament `/admin` inside `Website Project` serving both Web and Flutter Mobile, with automated non-PII synchronization to Firebase Cloud Firestore.*

**Binary Acceptance Criteria:**
- [ ] **AC-10.1 (Unified Backend):** `Website Project` serves Blade storefront (`/`), Filament v3 Admin (`/admin`), `/api/v1/*`, `/api/openapi.json`, and `/api/docs` over `Website Project/database/database.sqlite` with all 97 existing web/admin tests plus `/api/v1/*` and Firebase sync tests passing.
- [ ] **AC-10.2 (Flutter-Only Mobile Project):** `Mobile Project` contains zero Laravel/PHP/Filament/`database.sqlite` backend files; `Mobile Project/start-flutter-app.bat` boots Laravel from `Website Project` (`127.0.0.1:8000`) and Flutter from `Mobile Project`.
- [ ] **AC-10.3 (Opt-In Feature Flag):** `config/firebase.php` configures `enabled` (`FIREBASE_ENABLED`, defaulting strictly to `false`), `project_id`, `database_id` (`(default)`), `credentials_path`, `collection_prefix`, and `timeout`. When `enabled === false`, all model hooks exit immediately with zero outbound network calls.
- [ ] **AC-10.4 (Pure-PHP Firestore Service):** `FirebaseFirestoreService` generates RS256 Service Account JWTs via PHP `openssl_sign`, exchanges for a cached Google OAuth2 access token, and performs Firestore REST API v1 upserts/deletes with zero new Composer dependencies or `ext-grpc` requirements.
- [ ] **AC-10.5 (Command & `DB::afterCommit` Coverage):** `php artisan app:firebase-sync [--only=] [--dry-run]` and `DB::afterCommit` model sync hooks cover `Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, and `Payment` so catalog, settings, reviews, and order/payment statuses stay mirrored after SQLite transaction commits.
- [ ] **AC-10.6 (Strict Zero-PII & Fail-Open Safety):** Synced Firestore documents NEVER contain `password`, `remember_token`, `api_tokens`, customer `email`, `phone`, `street_address`, `customer_notes`, raw `bakong_response`, or `qr_string`/`md5` secrets. Transient Firestore errors are logged without failing local SQLite checkout or `/admin` writes.

---

## 6. Edge Cases & Error Handling

1. **Stateless Guest Order Access (`X-Order-Token`):** Unauthenticated checkouts issue HMAC-SHA256 `order_access_token` bound to `order_number`, `order_id`, `sha256(email)`, and expiry so guests cannot enumerate other orders or call `simulate-pay` / `regenerate-qr` without matching token (`HTTP 403`).
2. **Concurrent Checkout & Stock Depletion:** `OrderService` pessimistic locking (`lockForUpdate()`) rejects oversold requests (`HTTP 422`) and keeps SQLite inventory consistent.
3. **Firestore Transaction Rollback Safety:** Because sync hooks use `DB::afterCommit()`, rolled-back SQLite transactions (e.g., failed checkout stock lock) never write stale documents to Firestore.
4. **Late Payment vs Expiration Race Condition:** On countdown `00:00`, client calls `GET /api/v1/payments/{id}/status` once so backend checks Bakong before expiring stock.
5. **Network Disconnection During Polling:** Transient polling failures retry at 3s intervals without crashing or falsely marking expired.
6. **Phnom Penh Express Toggle Reset:** Selecting Express (`12,000 ៛`) on Phnom Penh and switching to another province automatically resets `shipping_method` to `standard` (`0 ៛`).
7. **XSS & Long Unicode Strings:** All dynamic copy, reviews, and Khmer Unicode strings render inside safe Flutter `Text` widgets / Blade `{{ }}` escaping.

---

## 7. Explicit Out-of-Scope Boundaries

- **Filament v3 Admin inside Flutter:** OUT OF SCOPE; `/admin` remains exclusively on `Website Project` Web.
- **Replacing SQLite with Firestore as Primary DB:** OUT OF SCOPE; `Website Project/database/database.sqlite` remains the ACID primary database.
- **Deploying `firestore.rules` before Firebase CLI Authentication:** Authoring & deploying `firestore.rules` is delegated to `@firestore-rules-author` ONLY after the stakeholder completes `firebase login` and selects a Firebase Project ID.
- **GitHub Push (`git push`):** STRICTLY OUT OF SCOPE / FORBIDDEN.
- **Additional Payment Gateways or Push Notifications (FCM/APNs):** OUT OF SCOPE for this sprint.
