# Project Memory Bank — RESET Store (Unified Backend + Flutter Client + Firebase Sync)

**Last Updated:** 2026-11-27  
**Owner:** `@architect`  
**Phase Status:** Phase 2 (Unification & Firebase Architecture) Complete — Ready for Phase 4 Implementation

---

## 1. Active Context & Unified Workspace Topology

RESET Store operates across two cleanly separated sibling workspace folders:

1. **`Website Project/` — Single Authoritative Backend + Blade Web + Filament v3 Admin + Firebase Sync**
   - Hosts the **Blade Web Storefront** (`routes/web.php`, `resources/views/`).
   - Hosts the **ONE shared Filament v3 Web Admin Panel** (`/admin`, `app/Filament/`, `AdminPanelProvider`).
   - Hosts the **stateless `/api/v1/*` REST API** (`routes/api.php`), **OpenAPI 3.1.0 spec** (`GET /api/openapi.json`), and **Swagger UI** (`GET /api/docs`).
   - Owns the **authoritative SQLite database** (`Website Project/database/database.sqlite`).
   - Runs **pure-PHP Firebase Cloud Firestore Sync** (`config/firebase.php`, `FirebaseFirestoreService`, `php artisan app:firebase-sync`, `DB::afterCommit` model hooks).
2. **`Mobile Project/` — Pure Flutter Cross-Platform Client (Android, iOS, Web)**
   - Cleaned of all duplicated Laravel, PHP, Filament, and SQLite backend files (`app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, `artisan`, `composer.json`).
   - Contains strictly Flutter application assets (`pubspec.yaml`, `lib/`, `test/`, `android/`, `ios/`, `web/`, `analysis_options.yaml`) plus `start-flutter-app.bat` configured to launch `Website Project` (`127.0.0.1:8000`) + `Mobile Project` Flutter client.
   - `AppConfig` resolves `http://127.0.0.1:8000` (Web/Desktop/iOS) and `http://10.0.2.2:8000` (Android Emulator), overridable via `--dart-define=API_BASE_URL=...`.

### Non-Negotiable Boundaries
1. **Preserve `Website Project/database/database.sqlite`:** Populated with **4 categories, 24 products, 168 variants, 48 images, 5 users, 3 discounts, 11 settings, 24 reviews, 1 address, and 1 wishlist** (97/97 existing PHPUnit tests passing). Never execute destructive wipes or `migrate:fresh` against `database/database.sqlite`. Apply only the 3 additive migrations (`api_tokens`, `add_api_performance_indexes`, `add_perf_covering_indexes`).
2. **One Filament v3 Admin (`/admin`):** Remains strictly on `Website Project` Web. Zero admin UI inside Flutter. Admin changes reflect immediately in Blade, `/api/v1/*`, and (when enabled) Cloud Firestore.
3. **Firebase Sync Opt-In Default (`FIREBASE_ENABLED=false`):** Keep SQLite for Filament v3 + Eloquent ACID Bakong KHQR `lockForUpdate()` transactions. `FIREBASE_ENABLED` defaults strictly to `false` until the stakeholder completes Firebase CLI login + Project ID selection and `@firestore-rules-author` authors `firestore.rules`.
4. **Strict Zero-PII Cloud Mirroring:** Never sync `users.password`, `remember_token`, `api_tokens`, customer emails/phones/street addresses, or raw Bakong gateway credentials/tokens to public Firestore documents.
5. **No Remote Git Push:** **NEVER execute `git push`** until explicitly instructed by the Executive Stakeholder.

---

## 2. Selected Golden Path Adaptation

Per `STACK_BLUEPRINTS.md`, RESET Store adapts **Blueprint B's Contract-First & OpenAPI-to-SDK decoupled API discipline** onto its mature **Laravel 13.17 + SQLite + Filament v3** monolith in `Website Project/`, paired with a pure **Flutter 3.35.1 / Dart 3.9.0** storefront in `Mobile Project/`:

- **Backend Runtime (`Website Project/`):** PHP `8.4` (`^8.3` in `composer.json`) + Laravel `^13.17` (`laravel/framework`) + Filament `^3.2` + `khqr-gateway/bakong-khqr-php ^1.0` + `simplesoftwareio/simple-qrcode ^4.2`.
- **Primary Database:** SQLite (`Website Project/database/database.sqlite`), enhanced only with 3 additive migrations (`api_tokens`, `add_api_performance_indexes`, `add_perf_covering_indexes`).
- **Cloud Sync Target:** Google Cloud Firestore REST API v1 (`projects/{projectId}/databases/(default)/documents/...`), opt-in via `FIREBASE_ENABLED`.
- **Frontend Runtime (`Mobile Project/`):** Flutter `3.35.1` / Dart `3.9.0` (`http ^1.6.0`, `provider ^6.1.5+1`, `qr_flutter ^4.1.0`, `shared_preferences ^2.5.5`, `url_launcher ^6.3.2`, `google_fonts ^8.1.0`, `intl ^0.20.3`, `web ^1.1.1`).
- **API Contract & Docs:**
  - Single Source of Truth: `Website Project/contracts/api.ts`
  - Machine-readable spec: `GET /api/openapi.json` (OpenAPI 3.1.0, exported to `openapi.json` & `public/openapi.json`)
  - Interactive browser docs: `GET /api/docs` (Swagger UI 5.x)
  - Typed Dart SDK: `Mobile Project/lib/core/api/api_client.dart` + strongly typed Dart DTOs in `Mobile Project/lib/models/*` strictly aligned to `contracts/api.ts`.

---

## 3. Zero-New-Composer-Dependency Policy

To avoid Composer conflicts, registry delays, PHP `ext-grpc` issues on Windows, or breaking the locked Laravel 13.17 + Filament 3.2 dependency graph in `Website Project/composer.json`:
- **No `laravel/sanctum`, no Swagger packages, and no `kreait/laravel-firebase` / `google/cloud-firestore` Composer packages** will be installed.
- **Customer API Auth:** Additive `api_tokens` table storing **SHA-256 `token_hash`** values, resolved via stateless `SetApiContext` + `AuthenticateApiToken` (`auth.api`) middleware without opening PHP sessions.
- **Stateless Guest Cart:** `X-Cart-Token` mapped to `carts.session_id`, atomically merged into `user_id` carts on `/api/v1/auth/login` and `/api/v1/auth/register`.
- **Stateless Guest Order Access:** HMAC-SHA256 signed `X-Order-Token` (`OrderAccessTokenService`) binding `order_number`, `order_id`, `sha256(email)`, and expiry.
- **OpenAPI & Swagger UI:** Served via lightweight `OpenApiController` + `OpenApiSpecService`.
- **Firebase Cloud Firestore Sync:** Pure-PHP `FirebaseFirestoreService` using PHP's built-in `openssl_sign(..., OPENSSL_ALGO_SHA256)` to sign Service Account RS256 JWTs, cached Google OAuth2 bearer tokens, and `Illuminate\Support\Facades\Http` calls to Firestore REST API v1.

---

## 4. Core Technical & Domain Conventions

| Convention | Rule & Implementation Detail |
| :--- | :--- |
| **Monetary Base Unit (`*_cents`)** | Every integer monetary column (`base_price_cents`, `compare_at_price_cents`, `price_cents`, `subtotal_cents`, `discount_cents`, `shipping_fee_cents`, `total_cents`, `min_spend_cents`, `free_shipping_threshold`) stores **base Cambodian Riel (KHR ៛)** integers—NOT USD cents. Example: `200000` = `200,000 ៛`. |
| **USD Conversion** | Divides base KHR by live `settings.exchange_rate` (default `4100`), rounded to 2 decimal places (`CurrencyService::convertCents()`). |
| **Promo Codes (Seeded)** | `RESET10` (10% off, min `100,000 ៛`), `FREESHIP` (waives standard shipping, min `0 ៛`), `STARTFRESH` (`60,000 ៛` off, min `250,000 ៛`). |
| **Express Shipping Parity** | `standard` = `0 ៛` across all 25 Cambodian provinces. `express` = **`12,000 ៛` strictly when `province === 'Phnom Penh'`**. Enforced server-side in `CartService` / `OrderService`; `/api/v1/checkout` rejects non-Phnom Penh `express` with HTTP `422`. |
| **Stateless API Context Headers** | `Authorization: Bearer <token>`, `X-Cart-Token: <uuid>`, `X-Order-Token: <hmac-token>`, `X-Locale: en \| km` (fallback `Accept-Language` / `?lang=`), `X-Currency: KHR \| USD` (fallback `?currency=`). |
| **Product Detail Tabs (5 Tabs)** | API always returns 5 localized `detail_tabs` (`description`, `fabric`, `care`, `fit`, `shipping`) with EN/KM fallback defaults when `details.fit` or `details.shipping` are absent in SQLite. |
| **Bakong Mock Guard** | `config('bakong.mock')` is `env('BAKONG_MOCK', false) && env('APP_ENV') !== 'production'`. `simulate-pay` strictly returns HTTP `403` when `false`. |
| **Firebase `DB::afterCommit` Sync** | `Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, `Payment` sync via `DB::afterCommit` only when `config('firebase.enabled') === true`; never blocks or rolls back SQLite on network failure. |
| **Local API Base URLs** | `Website Project` Laravel server: `http://127.0.0.1:8000` (Web/iOS/Desktop) and `http://10.0.2.2:8000` (Android Emulator). |

---

## 5. Verified Accounts & Brand Tokens

- **Seeded Admin (`/admin`):** `admin@reset.store` / `password` (`UserRole::Admin`)
- **Seeded Customers:** `customer@reset.store` / `password` and `kongchhunyt@gmail.com` / `password` (`UserRole::Customer`)
- **Brand Tokens:** Onyx Black `#111111`, Bone White `#F5F4F0`, Off-White `#FAF9F5`, Terracotta `#E65100`, Dark Mode Background `#0D0D0D`, KHQR Red `#E21A1A`; Fonts: `Space Grotesk` (Latin Display), `Kantumruy Pro` / `Noto Sans Khmer` (Khmer), `JetBrains Mono` / `Roboto Mono` (Tabular numerals).

---

## 6. Key Architecture Artifacts

- **PRD:** [`specs/PRD.md`](../specs/PRD.md)
- **Single Source of Truth Contract:** [`contracts/api.ts`](../contracts/api.ts)
- **System, SQLite & Firestore Architecture:** [`docs/ARCHITECTURE.md`](./ARCHITECTURE.md)
- **ADR-001 (Flutter + Laravel `/api/v1`):** [`docs/adr/001-flutter-laravel-api-architecture.md`](./adr/001-flutter-laravel-api-architecture.md)
- **ADR-002 (Unified Backend & Firebase Sync):** [`docs/adr/002-unified-backend-and-firebase-sync.md`](./adr/002-unified-backend-and-firebase-sync.md)
- **Living Task Board:** [`.agents/TASK_BOARD.md`](../.agents/TASK_BOARD.md)
