# Changelog — RESET Store

All notable architectural, feature, security, and release milestones for the unified **RESET Store** platform are documented in this file.

---

## [2.0.0] — 2026-11-27 — Backend Unification, Flutter-Only Client & Firebase Firestore Sync

### Summary
Consolidated the entire Laravel 13 backend, Blade storefront (`/`), single shared Filament v3 Web Admin (`/admin`), stateless `/api/v1/*` REST API + OpenAPI 3.1.0 (`/api/openapi.json`, `/api/docs`), authoritative SQLite database (`database/database.sqlite`), and pure-PHP **Firebase Cloud Firestore** sync inside `Website Project/`, while cleaning `../Mobile Project/` into a pure Flutter client pointing directly to `Website Project`.

### Added
- **Unified Stateless REST API (`/api/v1/*`) & OpenAPI 3.1.0 inside `Website Project/`:**
  - Ported `routes/api.php`, `bootstrap/app.php` JSON API exception rendering, `SetApiContext`, `AuthenticateApiToken` (`auth.api`), `ApiToken` (`api_tokens.token_hash` SHA-256), `OrderAccessTokenService` (HMAC-SHA256 `X-Order-Token`), `CatalogCacheService`, `StoreContentService`, `OpenApiSpecService`, `OpenApiController`, `App\Http\Controllers\Api\V1\*`, and `App\Http\Resources\Api\V1\*`.
  - Added live Swagger UI (`GET /api/docs`), OpenAPI 3.1.0 spec (`GET /api/openapi.json`), `php artisan app:export-openapi` (`openapi.json`, `public/openapi.json`), Single Source of Truth contract (`contracts/api.ts`), and 1-click HTTP collection (`api.http`).
  - Added **3 non-destructive additive migrations** over preserved `database/database.sqlite`: `create_api_tokens_table`, `add_api_performance_indexes`, and `add_perf_covering_indexes`.
- **Pure-PHP Firebase Cloud Firestore Sync (Opt-In, Zero Composer/gRPC Dependencies):**
  - Added `config/firebase.php` (`FIREBASE_ENABLED=false` default), `App\Services\FirebaseFirestoreService` (native PHP `openssl_sign(..., OPENSSL_ALGO_SHA256)` RS256 JWT + cached OAuth2 token + Firestore REST API v1 typed value encoder), and `App\Observers\FirebaseSyncObserver` registered across 9 models (`Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, `Payment`) via `DB::afterCommit`.
  - Added `php artisan app:firebase-sync {--collection=all} {--only=} {--dry-run}` (`FirebaseSyncCommand`) with fail-open behavior and strict zero-PII enforcement (`users`, `api_tokens`, `carts`, `addresses`, passwords, `token_hash`, emails, phones, street addresses, `qr_string`, `md5`, and `bakong_response` never leave SQLite).
- **Tri-Scenario Sandbox Seeder (`app:seed-scenario`):**
  - Added `App\Console\Commands\SeedScenarioCommand` (`clean`, `demo`, `stress`) with live `database/database.sqlite` protection (requires `--database=database/testing_sandbox.sqlite` or `:memory:`, blocking destructive runs on live SQLite unless `--force-destructive` is explicitly supplied).
- **Dual-Workspace CORS, Env & Launcher:**
  - Added `config/cors.php` allowing Flutter Web (`localhost:*`, `127.0.0.1:*`) and Android Emulator (`10.0.2.2:*`) with `X-Cart-Token`, `X-Order-Token`, `X-Locale`, and `X-Currency`.
  - Updated `.env.example` (including `FIREBASE_*`, `CORS_ALLOWED_ORIGINS`, `FRONTEND_URL`, `STORE_EXCHANGE_RATE=4100`, `STORE_FREE_SHIPPING_THRESHOLD=200000`) and `.gitignore` (`database/testing_sandbox.sqlite*`, `storage/app/firebase/*.json`, `*firebase-adminsdk*.json`).
  - Updated `../Mobile Project/start-flutter-app.bat` to boot `Website Project` on `http://127.0.0.1:8000` and launch the Flutter client.

### Changed & Fixed
- **Flutter-Only `../Mobile Project/` Cleanup:** Removed all duplicated Laravel, Filament, and SQLite files from `Mobile Project/` and wired `Mobile Project/lib/core/config/app_config.dart` directly to `Website Project` (`http://127.0.0.1:8000` for Web/iOS/Desktop; `http://10.0.2.2:8000` for Android Emulator).
- **`DATABASE_CACHE_SERIALIZATION` Fix (`CACHE_STORE=database`):** Resolved warm database-cache serialization failures under Laravel 13's `'serializable_classes' => false` hardening (`config/cache.php`) by caching resolved scalar/array payloads and integer ID lists (`BootstrapController`, `HomeController`, `ProductController`, `CatalogCacheService`), verified end-to-end by `tests/Feature/DatabaseCacheSerializationTest.php`.
- **`config/bakong.php` Fallback Cleanup:** Removed hardcoded fallback token from `config/bakong.php` (`'token' => env('BAKONG_TOKEN', '')`), defaulted optional merchant fields (`merchant_id`, `acquiring_bank`, `mobile_number`) to `null`, and enforced `'mock' => env('BAKONG_MOCK', false) && env('APP_ENV') !== 'production'` (`simulate-pay` strictly returns HTTP `403` when disabled).
- **Domain Parity Alignment:** Unified `CartService::EXPRESS_SHIPPING_FEE_KHR = 12000` (`Phnom Penh` only), `OrderService::createOrderFromCart()` `shipping_method` forwarding + post-commit sync hooks, `CurrencyService` stateless header resolution, `Setting::allValues()` memoization/invalidation, and 5 localized `detail_tabs` on product details.
- **Documentation & Architecture Artifacts:** Updated `README.md`, `specs/PRD.md`, `docs/MEMORY.md`, `docs/ARCHITECTURE.md`, `docs/adr/002-unified-backend-and-firebase-sync.md`, and `.agents/TASK_BOARD.md`.

### Preserved Authoritative Database Baseline (`Website Project/database/database.sqlite`)
All existing records remain intact without destructive resets:
- **4** categories, **24** products, **168** variants, **48** images, **5** users, **3** discounts (`RESET10`, `FREESHIP`, `STARTFRESH`), **11** settings, **24** reviews, **1** address, **1** wishlist.

---

### Exact Setup, Run, Seed & Firebase Sync Commands

#### 1. Start Unified Backend (`Website Project/`)
```bash
cd "Website Project"
composer install
npm install --ignore-scripts
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

#### 2. Start Pure-Flutter Client (`../Mobile Project/`)
- **Windows 1-Click Launcher:** Run `..\Mobile Project\start-flutter-app.bat`
- **Manual Flutter CLI:**
  ```bash
  cd "../Mobile Project"
  flutter pub get
  flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000
  # Android Emulator: flutter run -d android (uses http://10.0.2.2:8000)
  # iOS Simulator:    flutter run -d ios     (uses http://127.0.0.1:8000)
  ```

#### 3. Safe Sandbox Seeding (Never Touch Live `database.sqlite`)
```bash
php artisan app:seed-scenario clean --database=database/testing_sandbox.sqlite
php artisan app:seed-scenario demo --database=database/testing_sandbox.sqlite
php artisan app:seed-scenario stress --database=database/testing_sandbox.sqlite
```

#### 4. OpenAPI & Swagger UI
- **Swagger UI:** `http://127.0.0.1:8000/api/docs`
- **OpenAPI Spec:** `http://127.0.0.1:8000/api/openapi.json`
- **Regenerate Static Spec:** `php artisan app:export-openapi`
- **HTTP Playground:** `Website Project/api.http`

#### 5. Firebase Cloud Firestore Opt-In Sync
```bash
npx -y firebase-tools@latest login
# Save Service Account JSON to Website Project/storage/app/firebase/service-account.json
# Set FIREBASE_ENABLED=true and FIREBASE_PROJECT_ID=<your-project-id> in Website Project/.env
php artisan app:firebase-sync --dry-run
php artisan app:firebase-sync
```

---

### Demo Credentials
- **Filament v3 Web Admin (`http://127.0.0.1:8000/admin`):** `admin@reset.store` / `password`
- **Customer Account (Web & Flutter App):** `customer@reset.store` / `password` *(alt: `kongchhunyt@gmail.com` / `password`)*

---

### Verification Gates & QA Results
- **PHPUnit Unified Suite (`Website Project`):** `php artisan test` — **119 passed (690 assertions)** (includes 97 baseline tests + `/api/v1/*` + OpenAPI + `DatabaseCacheSerializationTest` under `CACHE_STORE=database` + `FirebaseSyncTest` mock HTTP & zero-PII assertions).
- **PHP Code Style (`Website Project`):** `vendor/bin/pint` — **Passed**.
- **Flutter Client (`../Mobile Project`):** `flutter analyze` (**0 issues**), `flutter test` (**15 passed**), `flutter build web` (**clean**).
- **Live Visual & Security Verification (`@reviewer` + `@browser`):** Verified `http://127.0.0.1:8000/` (Blade), `/admin` (Filament v3), `/api/docs` (Swagger UI), and `http://127.0.0.1:8080` (Flutter Web connected to `127.0.0.1:8000`) with 0 CORS or console errors on Flutter, Admin, and Swagger UI *(pre-existing `app-DMxvFsOq.js` syntax warning on Blade `/` noted)*.
- **Pending External / Optional Gates:**
  - *External Gate:* Authoring and deploying `firestore.rules` via `@firestore-rules-author` remains gated until stakeholder completes `firebase login` and supplies a Firebase Project ID.
  - *Optional Perf Benchmark Doc:* Covering indexes (`add_api_performance_indexes` + `add_perf_covering_indexes`) and `FIREBASE_ENABLED=false` zero-overhead are active; formal `docs/PERFORMANCE.md` benchmark report remains optional/deferred.
- **Git Safety Confirmation:** **NO `git push` was executed.**
