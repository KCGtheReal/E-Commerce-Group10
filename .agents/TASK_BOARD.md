# RESET Store — Living Task Board (Unification & Firebase Sync Sprint)

**Sprint Goal:** Unify the entire RESET Store Laravel backend (Blade storefront + ONE shared Filament v3 Admin `/admin` + stateless `/api/v1/*` REST API + OpenAPI `/api/openapi.json` & `/api/docs`) inside `Website Project` over the preserved `Website Project/database/database.sqlite`, clean `Mobile Project` to a pure Flutter client pointing directly to `Website Project` (`http://127.0.0.1:8000` / `http://10.0.2.2:8000`), and integrate pure-PHP Firebase Cloud Firestore sync (`FirebaseFirestoreService`, `config/firebase.php`, `app:firebase-sync`, `DB::afterCommit` hooks, `FIREBASE_ENABLED=false` default).

**Selected Blueprint:** Golden Path Adaptation (Unified Laravel 13.17 / PHP 8.4 / SQLite / Filament v3 Backend + Pure-PHP Firestore REST Sync in `Website Project`, paired with Contract-First Flutter 3.35.1 / Dart 3.9.0 Client in `Mobile Project`; zero new Composer dependencies).

---

## Binding Stakeholder Rules
- [x] Unify backend in `Website Project` over preserved `Website Project/database/database.sqlite` (**4 categories, 24 products, 168 variants, 48 images, 5 users, 3 discounts, 11 settings, 24 reviews, 1 address, 1 wishlist**) — **NEVER wipe/destroy baseline data**.
- [x] Apply only the **3 additive migrations** (`api_tokens`, `add_api_performance_indexes`, `add_perf_covering_indexes`).
- [x] Clean `Mobile Project` to **Flutter-only** (remove duplicate Laravel/Filament/SQLite files) and point Flutter + `start-flutter-app.bat` directly to `Website Project` (`127.0.0.1:8000` / `10.0.2.2:8000`).
- [x] Keep Filament v3 Admin (`/admin`) strictly on `Website Project` Web (not duplicated in Flutter).
- [x] Keep SQLite for Filament + Eloquent ACID Bakong KHQR transactions; add pure-PHP `FirebaseFirestoreService` (`openssl_sign` RS256 JWT -> Firestore REST v1) + `DB::afterCommit` sync hooks with zero PII in public docs and `FIREBASE_ENABLED=false` default until Firebase CLI login + Project ID selection + `@firestore-rules-author` rules authoring.
- [x] **NEVER execute `git push`**.

---

## Verification Gates

- [x] **Product Strategy Gate (`@pm`)** — `Website Project/specs/PRD.md` updated with Modules 1–10 (including Unified Backend & Firebase Firestore Sync acceptance criteria).
- [x] **Blueprint & Contract Gate (`@architect`)** — `Website Project/contracts/api.ts`, `docs/MEMORY.md`, `docs/ARCHITECTURE.md`, `docs/adr/001-flutter-laravel-api-architecture.md`, `docs/adr/002-unified-backend-and-firebase-sync.md`, and `.agents/TASK_BOARD.md` published with zero `any` types and zero destructive DB changes.
- [x] **Design System Gate (`@designer`)** — Exact RESET brand tokens, adaptive Flutter layouts, and `seed:clean` / `seed:demo` / `seed:stress` UI specs verified.
- [x] **Backend Unification & Firebase Sync Gate (`@backend`)** — 97 existing PHPUnit tests + `/api/v1/*` + OpenAPI + Firebase mock HTTP/PII-redaction + `DatabaseCacheSerializationTest` (`CACHE_STORE=database`) 100% pass (119/119 tests, 690 assertions), `vendor/bin/pint` passed, `config/bakong.php` hardcoded token/account removed, `/api/openapi.json` & `/api/docs` valid, non-destructive migration on `Website Project/database/database.sqlite` verified.
- [x] **Mobile Cleanup & Client Alignment Gate (`@frontend`)** — Duplicate Laravel/Filament/SQLite removed from `Mobile Project`, Flutter wired to `Website Project`, `flutter analyze` (0 issues), `flutter test` (15/15 pass), `flutter build web` clean.
- [x] **DevOps & Dual-Workspace Gate (`@devops`)** — `Website Project/config/cors.php`, `.env.example` (including `FIREBASE_*`), `.gitignore`, and `Mobile Project/start-flutter-app.bat` launcher verified with 0 leaked secrets (pre-existing `composer`/`npm` dependency advisories documented in review).
- [ ] **Performance Gate (`@perf`)** — Covering indexes ported (`add_api_performance_indexes` + `add_perf_covering_indexes`) and `FIREBASE_ENABLED=false` zero-overhead verified; formal `seed:stress` sandbox benchmark doc (`docs/PERFORMANCE.md`) optional/deferred.
- [x] **QA, Security & Visual Gate (`@reviewer` + `@browser`)** — Full unified test suite (`119/119` PHPUnit + `15/15` Flutter + `flutter analyze` + `flutter build web`) + OpenAPI conformance + PII-redaction & token/HMAC audit + live visual E2E (`@browser` verified `http://127.0.0.1:8000` `/`, `/admin`, `/api/docs`, and `http://127.0.0.1:8080` Flutter Web with 0 console/CORS errors on Flutter/Admin/Docs; pre-existing `app-DMxvFsOq.js` syntax warning on Blade `/` noted). *(External Gate: `firestore.rules` authoring/deployment remains gated on stakeholder `firebase login` + Project ID + `@firestore-rules-author`.)*
- [x] **Documentation Delivery Gate (`@docs`)** — `Website Project/README.md`, `Website Project/CHANGELOG.md`, `Website Project/api.http`, Swagger `/api/docs`, and `.agents/TASK_BOARD.md` complete and verified (zero `git push` executed).

---

## Sprint Phase Tracker

### Phase 1: Product Strategy & Discovery (`@pm`) — `[x] COMPLETE`
- **Owner:** `@pm`
- **Deliverables:**
  - [x] `Website Project/specs/PRD.md` — Updated for unified `Website Project` backend (Blade + Filament `/admin` + `/api/v1/*` + Firebase Firestore Sync) and Flutter-only `Mobile Project` across Modules 1–10.

---

### Phase 2: System Architecture & Contracts (`@architect`) — `[x] COMPLETE`
- **Owner:** `@architect`
- **Deliverables:**
  - [x] `Website Project/contracts/api.ts` — Single Source of Truth v1 contract preserving all 37 `/api/v1/*` operations, headers, DTOs, and status codes with zero `any` types.
  - [x] `Website Project/docs/MEMORY.md` — Active technical context for unified `Website Project`, Flutter-only `Mobile Project`, zero-new-Composer-dependency policy, and Firebase sync boundaries.
  - [x] `Website Project/docs/ARCHITECTURE.md` — Unified workspace diagram, 21 existing SQLite tables + 3 additive migrations (`api_tokens` `token_hash`, `add_api_performance_indexes`, `add_perf_covering_indexes`), and pure-PHP Firestore REST v1 sync + zero-PII collection mapping.
  - [x] `Website Project/docs/adr/001-flutter-laravel-api-architecture.md` & `Website Project/docs/adr/002-unified-backend-and-firebase-sync.md` — Architectural Decision Records documenting consolidation & Firebase sync.
  - [x] `Website Project/.agents/TASK_BOARD.md` — Living sprint tracker covering Phases 1–7.

---

### Phase 3: UI/UX Design & Interactive Prototyping (`@designer`) — `[x] COMPLETE`
- **Owner:** `@designer`
- **Deliverables:**
  - [x] Design tokens, Light/Dark adaptive layout blueprints, and interactive prototype previously verified and preserved in Flutter client (`Mobile Project`) without UI redesign required.

---

### Phase 4: Parallel Engineering Sprint — `[x] COMPLETE`

> **Parallel Execution Authorization:** Phase 2 contracts (`Website Project/contracts/api.ts`), schema, and Firebase architecture (`Website Project/docs/ARCHITECTURE.md`) are complete. `@backend`, `@frontend`, and `@devops` may execute in parallel immediately.

#### 4A. Backend Unification & Firebase Integration (`@backend` in `Website Project`)
- **Owner:** `@backend`
- **Status:** `[x]` Complete
- **Tasks:**
  - [x] **Additive Migrations & Live SQLite Preservation:**
    - Port `create_api_tokens_table` (`token_hash` unique), `add_api_performance_indexes`, and `add_perf_covering_indexes` into `Website Project/database/migrations/`.
    - Run `php artisan migrate --force` against `Website Project/database/database.sqlite` and verify all baseline row counts remain untouched (`4` categories, `24` products, `168` variants, `48` images, `5` users, `3` discounts, `11` settings, `24` reviews, `1` address, `1` wishlist).
  - [x] **Port `/api/v1/*`, Middleware, Domain Parity & OpenAPI 3.1:**
    - Port `ApiToken`, `AuthenticateApiToken`, `SetApiContext`, `OrderAccessTokenService`, `CatalogCacheService`, `StoreContentService`, `OpenApiSpecService`, `OpenApiController`, `ExportOpenApiCommand`, `SeedScenarioCommand` (`app:seed-scenario`), `App\Http\Controllers\Api\V1\*`, `App\Http\Resources\Api\V1\*`, `routes/api.php`, and `bootstrap/app.php` (`api:` route registration + JSON exception handling).
    - Apply domain parity updates in `CartService` (`EXPRESS_SHIPPING_FEE_KHR = 12000`, `resolveApiCart`, `mergeGuestCartByToken`), `OrderService` (`shipping_method` forwarding + cache/sync hooks), `CurrencyService` (stateless `api_currency` / `X-Currency`), `Setting` (`allValues()` + cache invalidation), and `Product` (`scopeWithReviewStats()`).
    - Export `openapi.json` & `public/openapi.json` via `php artisan app:export-openapi`.
  - [x] **Pure-PHP Firebase Cloud Firestore Sync:**
    - Create `Website Project/config/firebase.php` (`FIREBASE_ENABLED=false` default, `project_id`, `database_id=(default)`, `credentials_path`, `collection_prefix`, `timeout`).
    - Create `App\Services\FirebaseFirestoreService` (RS256 JWT via `openssl_sign`, cached OAuth2 token, Firestore REST v1 typed Value encoder, `upsertDocument`, `deleteDocument`, PII-safe payload builders).
    - Add `DB::afterCommit` model sync hooks on `Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, and `Payment` (and post-commit triggers after atomic `OrderService` stock/status updates).
    - Create `App\Console\Commands\FirebaseSyncCommand` (`php artisan app:firebase-sync {--only=} {--dry-run}`).
  - [x] **Unified PHPUnit Suite (`tests/Feature/Api/` & Firebase Unit/Feature Tests):**
    - Port API feature test suites and add Firebase sync tests (`Http::fake()` RS256 JWT / Firestore payload / zero-PII assertions / `FIREBASE_ENABLED=false` no-op check) alongside the existing 97 tests.

#### 4B. Mobile Workspace Cleanup & Client Verification (`@frontend` in `Mobile Project`)
- **Owner:** `@frontend`
- **Status:** `[x]` Complete
- **Tasks:**
  - [x] Remove duplicated Laravel/Filament/SQLite files and directories from `Mobile Project/` while keeping all Flutter files (`pubspec.yaml`, `lib/`, `test/`, `android/`, `ios/`, `web/`, `analysis_options.yaml`) intact.
  - [x] Verify `Mobile Project/lib/core/config/app_config.dart` resolves `http://127.0.0.1:8000` (Web/iOS/Desktop) and `http://10.0.2.2:8000` (Android Emulator) against unified `Website Project`.
  - [x] Run `flutter analyze`, `flutter test`, and `flutter build web` until 0 errors remain.

#### 4C. DevOps, CORS & Dual-Workspace Launcher (`@devops`)
- **Owner:** `@devops`
- **Status:** `[x]` Complete
- **Tasks:**
  - [x] Add/verify `Website Project/config/cors.php` for local Flutter Web (`127.0.0.1:*`, `localhost:*`) and Android Emulator (`10.0.2.2:*`) with `X-Cart-Token`, `X-Order-Token`, `X-Locale`, `Accept-Language`, `X-Currency`.
  - [x] Update `Website Project/.env.example` with `BAKONG_*`, `STORE_FREE_SHIPPING_THRESHOLD=200000`, `CORS_ALLOWED_ORIGINS`, `FRONTEND_URL`, and `FIREBASE_ENABLED=false` / `FIREBASE_PROJECT_ID=` / `FIREBASE_DATABASE_ID=(default)` / `FIREBASE_CREDENTIALS=` / `FIREBASE_COLLECTION_PREFIX=` (zero secrets).
  - [x] Update `Website Project/.gitignore` to ignore `database/testing_sandbox.sqlite*` and any local Service Account credentials (`storage/app/firebase-credentials.json`, `*firebase-adminsdk*.json`).
  - [x] Update `Mobile Project/start-flutter-app.bat` so it boots `Website Project` (`php artisan serve --host=127.0.0.1 --port=8000`) and launches the Flutter client from `Mobile Project`.

---

### Phase 5: Performance & Speed Optimization (`@perf`) — `[ ] PENDING`
- **Owner:** `@perf`
- **Depends On:** Phase 4 (`@backend`, `@frontend`, `@devops`)
- **Tasks:**
  - [ ] Run `php artisan app:seed-scenario stress` against isolated `Website Project/database/testing_sandbox.sqlite` (delete sandbox cleanly afterward).
  - [ ] Verify `EXPLAIN QUERY PLAN` uses all covering indexes (`add_api_performance_indexes` + `add_perf_covering_indexes`) and confirm 0 N+1 queries on `/api/v1/bootstrap`, `/api/v1/home`, `/api/v1/products`, and Blade routes.
  - [ ] Verify `FIREBASE_ENABLED=false` adds `0` SQLite queries and `0` HTTP calls during stress benchmarks, and confirm `DB::afterCommit` fires strictly outside transaction locks.
  - [ ] Publish/update `Website Project/docs/PERFORMANCE.md` and verify 0 test regressions.

---

### Phase 6: QA, Security & Visual Verification (`@reviewer` + `@browser`) — `[x] COMPLETE`
- **Owner:** `@reviewer` + `@browser`
- **Depends On:** Phase 5 (`@perf`)
- **Tasks:**
  - [x] Verify full PHPUnit suite (97 baseline + API + Firebase tests), `flutter analyze`, `flutter test`, and `flutter build web`.
  - [x] Audit `/api/openapi.json` and `/api/docs` conformance against `Website Project/contracts/api.ts`.
  - [x] Security audit: zero secrets in repo, SHA-256 `api_tokens.token_hash`, HMAC `X-Order-Token` 403 isolation, `BAKONG_MOCK=false` 403 guard, and strict zero-PII enforcement on Firestore sync payloads.
  - [x] Live visual E2E (`@browser`) across unified `Website Project` Blade storefront (`/`), Filament Admin (`/admin`), Swagger UI (`/api/docs`), and `Mobile Project` Flutter Web pointing to `127.0.0.1:8000`.
  - [ ] **External Gate (Deferred until Stakeholder Action):** Once stakeholder runs `firebase login` and provides Firebase Project ID, delegate `firestore.rules` authoring to `@firestore-rules-author`.

---

### Phase 7: Technical Writing & Delivery (`@docs`) — `[x] COMPLETE`
- **Owner:** `@docs`
- **Depends On:** Phase 6 (`@reviewer`)
- **Tasks:**
  - [x] Update `Website Project/README.md` with Unified Backend + Flutter Client + Firebase Firestore Sync setup instructions, `/api/docs` reference, `app:seed-scenario` sandbox commands, and `app:firebase-sync` guide.
  - [x] Create `Website Project/api.http` with 1-click requests for all `/api/v1/*` flows plus `/api/openapi.json` and `/api/docs`.
  - [x] Create/update `Website Project/CHANGELOG.md` with release notes, credentials, test results, and confirmation that **NO `git push`** was executed.
