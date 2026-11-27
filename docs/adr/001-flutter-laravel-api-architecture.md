# ADR-001: Flutter + Laravel `/api/v1` Co-Located Architecture

- **Status:** Accepted  
- **Date:** 2026-11-26  
- **Author:** `@architect`  
- **Supersedes:** Web-only storefront architecture  
- **Related Documents:** [`specs/PRD.md`](../../specs/PRD.md), [`contracts/api.ts`](../../contracts/api.ts), [`docs/ARCHITECTURE.md`](../ARCHITECTURE.md), [`docs/MEMORY.md`](../MEMORY.md)

---

## 1. Context & Problem Statement

RESET Store currently runs as a monolithic Laravel 13.17 (PHP 8.4) application backed by SQLite (`database/database.sqlite`), Blade + Alpine.js + Tailwind CSS storefront views, Filament v3 Admin (`/admin`), and native Bakong KHQR payment orchestration (`BakongService`, `OrderService`, `CartService`, `CurrencyService`).

We must deliver a cross-platform **Flutter** customer storefront (Android, iOS, and Web) with 100% customer-facing feature parity while:
1. Reusing the exact existing `database/database.sqlite` database without wiping or splitting data.
2. Keeping Filament v3 `/admin`, existing web storefront routes, and local `.bat` launchers operational.
3. Enforcing zero Composer dependency churn and strict end-to-end contract safety between Laravel and Flutter.

---

## 2. Architectural Decisions

### Decision 1: Co-Located Flutter at Repository Root + Laravel In-Place

- **Options Considered:**
  1. *Create a separate `mobile/` subdirectory or separate Git repository:* Requires moving paths, complicates root `flutter run` expectations, and breaks stakeholder requirement to initialize Flutter directly at the root.
  2. *Initialize Flutter directly at repository root alongside Laravel:* Keeps `app/`, `database/database.sqlite`, `routes/`, `artisan`, `composer.json`, and existing `.bat` scripts untouched in place while adding `pubspec.yaml`, `lib/`, `test/`, `android/`, `ios/`, `web/`.
- **Decision:** **Co-locate Flutter directly at the repository root** with Laravel in place.
- **Consequences:**
  - Zero disruption to `database/database.sqlite`, `/admin`, or existing PHPUnit feature/unit tests.
  - Both `php artisan serve` and `flutter run` execute from the same workspace root.
  - `.gitignore` must cleanly cover Flutter artifacts (`.dart_tool/`, `.flutter-plugins*`, `build/`, `.packages`, `ios/Pods/`, `android/.gradle/`, etc.) without ignoring `lib/`, `test/`, or `pubspec.yaml`, and without interfering with Laravel ignores (`/vendor`, `/node_modules`, `.env`, `/public/build`).
  - Note: Check `web/index.html` vs Laravel's `public/index.php`—no filename collision exists because Flutter web assets live in `web/` and build to `build/web/`, whereas Laravel serves from `public/`.

---

### Decision 2: Zero-Dependency Bearer Token Auth + Stateless Guest `X-Cart-Token` + Stateless HMAC `X-Order-Token`

- **Options Considered:**
  1. *Install `laravel/sanctum`:* Requires modifying `composer.json`/`composer.lock` and installing vendor packages; violates the zero-new-Composer-dependency policy.
  2. *Use PHP session cookies in Flutter:* Unreliable across native iOS/Android HTTP clients, incompatible with stateless REST mobile APIs, and risks colliding with web/admin session guards.
  3. *Custom `api_tokens` table (SHA-256) + `X-Cart-Token` (`carts.session_id`) + HMAC-SHA256 `X-Order-Token`:* Zero Composer packages, works identically on Android, iOS, and Flutter Web, and cleanly isolates API authentication from Filament's `admin` guard.
- **Decision:** Adopt **zero-dependency SHA-256 Bearer tokens (`api_tokens`) + stateless `X-Cart-Token` + stateless HMAC-SHA256 `X-Order-Token`**.
- **Design Details:**
  - **User Bearer Auth (`api_tokens` table):**
    - Additive migration creates `api_tokens` (`id`, `user_id` FK, `name`, `token` `string(64)` unique, `abilities` JSON nullable, `last_used_at`, `expires_at`, timestamps).
    - On `/api/v1/auth/login` or `/api/v1/auth/register`, backend generates a cryptographically random plaintext token (`rst_<64-hex>`), stores `hash('sha256', $plaintext)` in `api_tokens.token`, and returns the plaintext token once.
    - `ApiTokenAuth` middleware validates `Authorization: Bearer <token>` via indexed SHA-256 lookup, rejects expired tokens (`401`), resolves the authenticated `User` on `$request->setUserResolver(...)` / `Auth::setUser($user)`, and never opens a PHP session or touches the `admin` guard.
  - **Guest Cart (`X-Cart-Token`):**
    - Flutter persists a UUID v4 `cart_token` locally and sends `X-Cart-Token: <uuid>` on `/api/v1/cart*`, `/api/v1/checkout`, `/api/v1/bootstrap`, `/api/v1/auth/login`, and `/api/v1/auth/register`.
    - Backend maps `X-Cart-Token` to `carts.session_id` where `user_id IS NULL`. If omitted on a cart mutation, backend generates a new UUID v4 token and returns it as `cart_token` in the response.
    - On `login` or `register`, if `X-Cart-Token` corresponds to a guest cart, `CartService::mergeCarts()` atomically merges line items (clamped to `available_stock`), copies `discount_id` if user cart has none, and deletes the guest cart inside a transaction.
  - **Guest Order Access (`X-Order-Token`):**
    - Unlike web checkout (`session('active_order_...')`), mobile clients have no session cookie.
    - `POST /api/v1/checkout` generates a stateless **HMAC-SHA256** token (`OrderAccessTokenService`) binding `order_number`, `order_id`, `email`, and an expiration timestamp (e.g., 30 days) signed with `config('app.key')`:
      `token = base64url(json({ n: order.number, i: order.id, exp })) + '.' + base64url(hmac_sha256(payload, appKey))`
    - Payment, polling, QR regeneration, mock simulation, and invoice routes (`/api/v1/orders/{number}/payment`, `/api/v1/payments/{payment}/status`, `/api/v1/orders/{number}/regenerate-qr`, `/api/v1/payments/{payment}/simulate-pay`, `/api/v1/orders/{number}/invoice`) authorize when **EITHER**:
      1. Authenticated Bearer user is the order owner (`$user->id === $order->user_id`) or Admin (`$user->isAdmin()`), **OR**
      2. Request carries a valid `X-Order-Token` header verified with `hash_equals()` for that exact order (`$order->number`, `$order->id`, `$order->email`).
    - Rejecting missing/mismatched tokens with HTTP `403` prevents order enumeration and unauthorized `simulate-pay` / `regenerate-qr` calls while avoiding any destructive schema alteration to `orders`.

---

### Decision 3: Server-Side Express Shipping Parity (`12,000 ៛`) & Context Resolution

- **Context:** Currently, `checkout.blade.php` calculates `12,000 ៛` client-side when `province === 'Phnom Penh'` and `shippingMethod === 'express'`, while `CartService::calculateTotals(?string $province = null)` and `OrderService::createOrderFromCart()` ignore `shipping_method` and compute `0 ៛`. Additionally, `CurrencyService::current()` only checks PHP session/cookie, and `Product.details` rows in SQLite omit `shipping` (and occasionally `fit`).
- **Decision:** Fix server-side business logic once in the shared domain services so both Web and `/api/v1` behave identically:
  1. **Express Shipping Enforcement:** Update `CartService::calculateTotals(?string $province = null, string $shippingMethod = 'standard')` to return `12000` (`CartService::EXPRESS_SHIPPING_FEE_KHR`) when `$shippingMethod === 'express' && $province === 'Phnom Penh'`, and `0` (or configured province standard rate) for `standard`. Update `OrderService::createOrderFromCart()` to pass `$data['shipping_method'] ?? 'standard'` into `calculateTotals()`, and validate in `CheckoutRequest` / API checkout that `express` is strictly forbidden outside `Phnom Penh` (`422` validation error).
  2. **Stateless Context (`SetApiContext` + `CurrencyService`):** `SetApiContext` middleware resolves locale (`X-Locale` -> `Accept-Language` -> `?lang=`, normalized to `en|km`) and currency (`X-Currency` -> `?currency=`, normalized to `KHR|USD`) per API request. `CurrencyService::current()` checks request header/attribute/query before falling back to session/cookie/config.
  3. **5-Tab Product Details Parity:** API product serializer builds all 5 localized `detail_tabs` (`description`, `fabric`, `care`, `fit`, `shipping`) with clean EN/KM fallback defaults when `details['fit']` or `details['shipping']` are absent in `database/database.sqlite`.
  4. **Settings Cache Invalidation:** Add `Setting` Eloquent `saved` / `deleted` model events to clear `Cache::forget("setting_{$setting->key}")` so Filament `/admin` edits appear immediately in `/api/v1/bootstrap`.

---

### Decision 4: OpenAPI 3.1 (`/api/openapi.json` + `/api/docs`) & Typed Dart Client SDK

- **Options Considered:**
  1. *Add `darkaonline/l5-swagger` or `dedoc/scramble`:* Adds Composer dependencies and annotation overhead.
  2. *Hand-written raw HTTP URLs in Flutter:* Violates Contract-First engineering and causes silent model drift.
  3. *Contract-First `contracts/api.ts` + zero-dependency OpenAPI 3.1 route (`/api/openapi.json` & `/api/docs`) + typed Dart SDK (`lib/core/api/api_client.dart` + typed DTOs):* Keeps zero Composer dependencies while delivering interactive Swagger UI documentation and compile-time typed requests/responses in Dart.
- **Decision:** Establish `contracts/api.ts` as the **Single Source of Truth**, serve OpenAPI 3.1 JSON at `GET /api/openapi.json` and interactive Swagger UI at `GET /api/docs`, and build a strongly typed Dart API client (`lib/core/api/api_client.dart`) with typed endpoint methods, typed DTO classes (`fromJson`/`toJson`), and centralized `Dio`/`http` interceptors for `Authorization`, `X-Cart-Token`, `X-Order-Token`, `X-Locale`, and `X-Currency`.

---

## 3. Verification & Compliance Criteria

- All existing PHPUnit tests (`CartTest`, `BakongPaymentTest`, `CheckoutFlowTest`, `StorefrontTest`, `AdminPanelTest`) continue to pass with 0 regressions.
- New `/api/v1/*` feature tests verify auth token hashing/revocation, guest `X-Cart-Token` + login/register cart merge, `X-Order-Token` authorization isolation, Express `12,000 ៛` enforcement, KHQR polling/expiration/regeneration/mock guard, and OpenAPI schema validity.
- `flutter analyze` passes with 0 errors and 0 warnings; `flutter test` passes 100%.
