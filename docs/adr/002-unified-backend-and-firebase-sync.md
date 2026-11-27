# ADR-002: Unified Backend in `Website Project`, Flutter-Only `Mobile Project`, and Pure-PHP Firebase Cloud Firestore Sync

- **Status:** Accepted  
- **Date:** 2026-11-27  
- **Author:** `@architect`  
- **Amends / Extends:** [`ADR-001`](./001-flutter-laravel-api-architecture.md) (Supersedes `ADR-001 Decision 1` co-located root layout; retains Decisions 2–4 inside `Website Project`)  
- **Related Documents:** [`specs/PRD.md`](../../specs/PRD.md), [`contracts/api.ts`](../../contracts/api.ts), [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md), [`docs/MEMORY.md`](../MEMORY.md)

---

## 1. Context & Problem Statement

Following initial `/api/v1` + Flutter development in `Mobile Project/`, the workspace ended up with **two parallel Laravel applications**:
- `Website Project/` hosted the Blade web storefront (`routes/web.php`), Filament v3 Admin (`/admin`), and the authoritative `Website Project/database/database.sqlite` database (with 97/97 PHPUnit tests passing and all live baseline rows: `4` categories, `24` products, `168` variants, `48` images, `5` users, `3` discounts, `11` settings, `24` reviews, `1` address, `1` wishlist), **without** `/api/v1/*`.
- `Mobile Project/` hosted a duplicated Laravel backend, duplicated Filament `/admin`, and a separate `database.sqlite` alongside the Flutter application (`pubspec.yaml`, `lib/`, `android/`, `ios/`, `web/`).

Maintaining two separate Laravel backends creates split-brain inventory (`stock_quantity` / `reserved_quantity`), divergent order histories, and duplicated Filament administration. Additionally, the Executive Stakeholder requires synchronizing catalog, settings, reviews, and order/payment statuses to **Firebase Cloud Firestore** cleanly—without compromising SQLite ACID Bakong KHQR transactions, introducing Composer/gRPC dependency breakage, or exposing customer PII.

---

## 2. Architectural Decisions

### Decision 1: Unify All Backend Responsibilities Inside `Website Project` and Clean `Mobile Project` to Flutter-Only

- **Options Considered:**
  1. *Keep dual Laravel backends and synchronize two SQLite databases:* Rejected; creates split-brain inventory race conditions and double admin portals.
  2. *Delete `Website Project` and move Blade storefront into `Mobile Project`:* Rejected; `Website Project` owns the authoritative `Website Project/database/database.sqlite` and verified 97/97 storefront/admin suite.
  3. *Consolidate Blade storefront + ONE shared Filament v3 Admin (`/admin`) + stateless `/api/v1/*` + OpenAPI (`/api/openapi.json`, `/api/docs`) inside `Website Project` over `Website Project/database/database.sqlite`, and strip duplicated Laravel/Filament/SQLite files from `Mobile Project` so `Mobile Project` is Flutter-only:* **Selected**.
- **Consequences:**
  - `Website Project/database/database.sqlite` remains the single authoritative relational database with all baseline rows preserved and only the 3 additive migrations applied (`api_tokens`, `add_api_performance_indexes`, `add_perf_covering_indexes`).
  - Note on `api_tokens` column naming: the concrete migration & `ApiToken` model use **`token_hash`** (64-char unique SHA-256 hex digest), superceding the earlier draft name `token` noted in `ADR-001`.
  - `Mobile Project/` retains strictly Flutter artifacts (`pubspec.yaml`, `lib/`, `test/`, `android/`, `ios/`, `web/`, `analysis_options.yaml`) plus `start-flutter-app.bat` pointing to `../Website Project` (`http://127.0.0.1:8000`) and launching Flutter against `http://127.0.0.1:8000` (Web/Desktop/iOS) or `http://10.0.2.2:8000` (Android Emulator).
  - All 97 existing PHPUnit tests in `Website Project` plus the `/api/v1/*` & Firebase sync tests execute as one unified test suite.

---

### Decision 2: SQLite Primary Transactional Store + Pure-PHP `FirebaseFirestoreService` (RS256 JWT via `openssl_sign` -> Firestore REST API v1)

- **Options Considered:**
  1. *Replace SQLite with Cloud Firestore as the primary database:* Rejected; Filament v3 relies on Eloquent relational queries, Bakong KHQR stock reservation requires pessimistic row locking (`lockForUpdate()` inside `DB::transaction`), and existing SQLite baseline data must be preserved.
  2. *Install `kreait/laravel-firebase` or `google/cloud-firestore` via Composer:* Rejected; Google's official PHP Firestore SDK requires `ext-grpc` / `ext-protobuf` (fragile on Windows PHP 8.4 setups) and introduces Composer dependency churn into Laravel 13.17 + Filament 3.2.
  3. *Implement a zero-dependency pure-PHP `FirebaseFirestoreService` using `openssl_sign(..., OPENSSL_ALGO_SHA256)` + Laravel `Http` client against Firestore REST API v1, configured via `config/firebase.php` (`FIREBASE_ENABLED=false` default) and driven by `DB::afterCommit` hooks + `php artisan app:firebase-sync`:* **Selected**.
- **Design & Consequences:**
  - **Authentication:** Reads Google Service Account JSON (`client_email`, `private_key`, `token_uri`), constructs a short-lived RS256 JWT assertion signed with native PHP `openssl_sign`, exchanges it for an OAuth2 access token (`https://www.googleapis.com/auth/datastore`), and caches the token in Laravel `Cache` (~50 minutes).
  - **Transaction Integrity (`DB::afterCommit`):** Model sync hooks on `Category`, `Product`, `ProductVariant`, `ProductImage`, `Discount`, `Setting`, `Review`, `Order`, and `Payment` dispatch strictly inside `DB::afterCommit(...)`. Failed/rolled-back SQLite transactions never emit phantom Firestore documents, and SQLite write locks are released before outbound HTTPS requests.
  - **Fail-Open Availability & Opt-In Default:** `FIREBASE_ENABLED` defaults to `false`. When disabled—or if Firestore experiences transient network downtime—local checkout, Bakong KHQR polling, Blade storefront, `/api/v1/*`, and Filament `/admin` continue operating with zero user-facing errors.
  - **Manual/Initial Backfill:** `php artisan app:firebase-sync [--only=] [--dry-run]` reconciles existing SQLite records (`4` categories, `24` products, `168` variants, `48` images, `3` discounts, `11` settings, `24` reviews, plus orders/payments) into Firestore once enabled.

---

### Decision 3: Strict Zero-PII Cloud Mirroring & Deferred `@firestore-rules-author` Security Gate

- **Decision:**
  - Synced Firestore collections (`categories`, `products`, `discounts`, `settings`, `reviews`, `orders`, `payments`) are strictly **PII-sanitized**:
    - **Excluded tables:** `users`, `api_tokens`, `password_reset_tokens`, `sessions`, `carts`, `cart_items`, `addresses`, `wishlists`, `contact_messages`, `newsletter_subscribers`.
    - **Excluded sensitive fields:** `password`, `remember_token`, `token_hash`, customer `email`, `phone`, `street_address`, `district`, `commune`, `customer_notes`, raw `bakong_response`, `qr_string`, `md5`, `account_id`, and `manual_paid_note`.
    - `orders/{number}` mirrors only sanitized status, totals, currency, shipping method, `province` aggregate, `items_count`, carrier/tracking info, and lifecycle timestamps.
    - `reviews/{id}` syncs only `is_approved === true` reviews with display author name and zero email/phone metadata.
  - Authoring and deploying `firestore.rules` MUST be performed by `@firestore-rules-author` only after the Executive Stakeholder logs in via Firebase CLI and chooses the target Firebase Project ID.

---

## 3. Verification & Compliance Criteria

- `Website Project`: `php artisan migrate --force` succeeds non-destructively preserving all baseline SQLite counts; all 97 existing tests + new `/api/v1/*` + Firebase mock-HTTP unit/feature tests pass (`0` failures).
- `Website Project`: `FIREBASE_ENABLED=false` triggers zero outbound HTTP calls during normal requests/tests.
- `Website Project`: Firestore sync serializer tests explicitly assert that `password`, `token_hash`, `email`, `phone`, `street_address`, `qr_string`, and `md5` are absent from generated Firestore payloads.
- `Mobile Project`: Contains zero Laravel/PHP/SQLite files; `flutter analyze`, `flutter test`, and `flutter build web` pass cleanly pointing to `Website Project` (`127.0.0.1:8000` / `10.0.2.2:8000`).
- **Git Safety:** Zero `git push` commands executed.
