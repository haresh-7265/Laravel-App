# Laravel 12 E-Commerce Application

A full-featured e-commerce platform built with Laravel 12. Dual-guard authentication (customers + admins), tiered API rate limiting, Sanctum token auth, custom API key auth, Meilisearch full-text search, Livewire components, job batching, real-time Slack notifications, and a pluggable secrets management system.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Setup](#setup)
3. [Available Scripts](#available-scripts)
4. [Environment Variables](#environment-variables)
5. [Request Lifecycle](#request-lifecycle)
6. [Service Container Explained](#service-container-explained)
7. [Middleware Stack](#middleware-stack)
8. [Authentication Strategies](#authentication-strategies)
9. [Authorization — Gates & Policies](#authorization--gates--policies)
10. [Queue, Jobs & Notifications](#queue-jobs--notifications)
11. [Events & Listeners](#events--listeners)
12. [Model Observers](#model-observers)
13. [Data Security](#data-security)
14. [Secrets Management](#secrets-management)
15. [Testing](#testing)
16. [Key Design Decisions](#key-design-decisions)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.2 |
| Composer | ≥ 2 |
| Node.js / npm | ≥ 18 |
| SQLite / MySQL / PostgreSQL | any |
| Redis | ≥ 6 (optional — broadcasting, browse tracking) |
| Meilisearch | ≥ 1 (optional — full-text product search) |

---

## Setup

### 1 — Install dependencies

```bash
git clone <repo-url>
cd laravel12-app
composer install
npm install
```

### 2 — Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Minimum working local configuration:

```dotenv
APP_KEY=           # filled by key:generate
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log    # writes to storage/logs — no SMTP needed locally
```

### 3 — Database

```bash
php artisan migrate        # creates all 30 tables
php artisan db:seed        # optional demo data
```

### 4 — Front-end assets

```bash
npm run build    # production
npm run dev      # Vite HMR (local)
```

### 5 — Start development server

The `composer dev` script starts **9 processes concurrently** (colour-coded in the terminal):

```bash
composer dev
```

Equivalent to:

```
php artisan serve                                          → http://localhost:8000
npm run dev                                                → Vite HMR
php artisan queue:listen --queue=realtime      --tries=1   → real-time / broadcasting jobs
php artisan queue:listen --queue=emails        --tries=1   → outbound e-mail jobs
php artisan queue:listen --queue=notifications --tries=1   → notification jobs
php artisan queue:listen --queue=default       --tries=1   → general-purpose jobs
php artisan queue:listen --queue=pdfs          --tries=1   → PDF invoice generation
php artisan queue:listen --queue=analytics     --tries=1   → slow-query logging
```

Each queue runs in its own worker process so high-volume analytics jobs never block time-sensitive e-mail or notification queues.

### 6 — Meilisearch (optional)

```bash
# After starting Meilisearch:
php artisan scout:import "App\Models\Product"
```

---

## Available Scripts

| Command | Purpose |
|---|---|
| `composer setup` | One-shot: install → copy `.env` → `key:generate` → migrate → npm install → npm build |
| `composer dev` | All dev processes concurrently |
| `composer test` | Clear config cache then run PHPUnit |
| `php artisan queue:work` | Process one job and exit |
| `php artisan pail` | Live structured log tail |
| `php artisan tinker` | REPL |

---

## Environment Variables

| Variable | Default | Notes |
|---|---|---|
| `APP_KEY` | — | **Required.** Run `php artisan key:generate` |
| `APP_ENV` | `local` | Controls debug mode, lazy-load guard, slow-query logging |
| `APP_URL` | `http://localhost` | Used in e-mail links and signed routes |
| `DB_CONNECTION` | `sqlite` | `sqlite`, `mysql`, or `pgsql` |
| `QUEUE_CONNECTION` | `database` | `database`, `redis`, or `sync` |
| `CACHE_STORE` | `database` | `database`, `redis`, or `file` |
| `BROADCAST_CONNECTION` | `log` | `log` locally, `pusher` in production |
| `MAIL_MAILER` | `log` | `log`, `resend`, `mailgun`, or `smtp` |
| `BCRYPT_ROUNDS` | `12` | Raise to 14 for higher-security environments |
| `BLIND_INDEX_SECRET` | — | HMAC key for searchable encrypted fields (`phone`, `shipping_phone`) |
| `SECRETS_DRIVER` | `env` | `env` \| `aws` \| `vault` \| `doppler` |
| `EXTERNAL_API_BASE_URL` | — | Base URL for `ExternalApiService` |
| `EXTERNAL_API_TOKEN` | — | Bearer token for `ExternalApiService` |
| `SLACK_BOT_TOKEN` | — | Slack API token for order/error notifications |
| `SLACK_SIGNING_SECRET` | — | Validates inbound Slack webhook payloads |
| `MEILISEARCH_HOST` | `http://localhost:7700` | Required when `SCOUT_DRIVER=meilisearch` |
| `REDIS_HOST` | `127.0.0.1` | Required when using Redis for cache/session/broadcasting |

---

## Request Lifecycle

```
Browser / API Client
        │
        ▼
┌─────────────────────────────────────────────────────────┐
│  public/index.php                                       │
│  Loads the Composer autoloader. Creates the             │
│  Application instance. Hands off to the HTTP kernel.   │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  bootstrap/app.php                                      │
│  Wires routing (web, api, admin-auth, channels,        │
│  console). Registers middleware aliases and priority.   │
│  Binds the custom exception handler.                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Service Provider Boot  (once per process, not per req) │
│                                                         │
│  SecretsServiceProvider  — loads secrets from           │
│    env/AWS/Vault/Doppler into Config before anything    │
│    else reads config() values.                          │
│                                                         │
│  AppServiceProvider  — singleton bindings,              │
│    named rate limiters, Gate policies & gates,          │
│    Password rules, slow-query listener, macros.         │
│                                                         │
│  ProductServiceProvider  — singleton bindings for       │
│    ProductService, CartService, OrderService, etc.;     │
│    Blade directives; View composers; Model observers.   │
│                                                         │
│  EventServiceProvider  — maps Events → Listeners.       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Global Web Middleware  (every web request, in order)  │
│                                                         │
│  1. SetLocale                   read locale cookie →   │
│                                 app()->setLocale()      │
│  2. RequestTrackingMiddleware   inject request_id,      │
│                                 user_type, ip into Log  │
│                                 Context; track browse   │
│                                 in Redis for online-    │
│                                 user dashboard.         │
│  3. AuthenticateSession         invalidate session if   │
│                                 password changed in     │
│                                 another tab.            │
│  4. RedirectIfPasswordResetForced  guard force-reset    │
│  5. VerifyCsrfToken             except /api/* and       │
│                                 /submit                 │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Router                                                 │
│  Matches URL against routes/web.php, api.php,           │
│  admin-auth.php, cart.php, auth.php.                    │
│  Applies route-level middleware: auth, throttle,        │
│  permission, role, signed, verified …                   │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Form Request  (where used)                             │
│  authorize() runs Gate / Policy check.                  │
│  rules() + prepareForValidation() sanitise input.       │
│  Rejected requests return a 422 / redirect before the  │
│  controller is ever called.                             │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Controller → Service Layer                             │
│  Controllers are thin: receive validated input, call a  │
│  Service (CartService, OrderService, ProductService…),  │
│  return a response. No raw queries in controllers.      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Events / Observers  (fired during service calls)       │
│  e.g. OrderPlaced → NotifyAdmin, LogEvent, SendEmail    │
│  Queued listeners run asynchronously after response.    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Response returned to client                            │
│  RequestTrackingMiddleware logs status code on way out. │
└─────────────────────────────────────────────────────────┘
```

### Slow-Query Side Channel

`AppServiceProvider` registers `DB::whenQueryingForLongerThan(100, ...)`. When any query exceeds **100 ms**, a `LogSlowQueryJob` is dispatched onto the `analytics` queue **after** the response is already sent — zero latency impact. The `SlowQueryMonitor` Livewire component queries an `analytics` database connection and displays a live, filterable table in the admin panel.

---

## Service Container Explained

> This section is a recurring submission requirement. Read it carefully.

The **service container** (IoC / DI container) is Laravel's central object graph manager. Every class the application needs — controllers, services, middleware, notifications — is built and injected through it. You never call `new SomeService()` directly in application code.

### Core Idea

```php
// ❌ Without the container — fragile, coupled
$cache  = new CacheService();
$coupon = new CouponService();
$cart   = new CartService($cache, $coupon); // caller must know all deps

// ✅ With the container — decoupled
$cart = app(CartService::class); // container builds it, injects deps automatically
```

### Binding Types Used in This Project

#### `singleton` — one shared instance per request

```php
// ProductServiceProvider::register()
$this->app->singleton(CartService::class);
$this->app->singleton(OrderService::class);
$this->app->singleton(CouponService::class);
$this->app->singleton(CacheService::class);

// Named alias binding
$this->app->singleton('products', fn() => new ProductService);
```

**Why singletons?** These services carry request-scoped in-memory state. A new instance on every injection would waste resources and cause state inconsistencies. A singleton guarantees all parts of a single request share the same instance.

The `'products'` string is an alias. Both of these resolve the same object:

```php
app('products');           // alias
app(ProductService::class) // FQCN
```

The custom `Products` facade (used in `routes/api.php`) resolves the same binding:

```php
use App\Facades\Products;
Products::getAll(); // same as app('products')->getAll()
```

#### External HTTP services

```php
// AppServiceProvider::register()
$this->app->singleton(FakeStoreService::class);
$this->app->singleton(ExternalApiService::class);
```

`ExternalApiService` reads config and builds a pre-configured Guzzle client once. The singleton avoids repeated config reads on every API call.

#### Automatic Resolution (zero-config)

The container can resolve any class whose constructor parameters are type-hinted — no binding entry needed:

```php
class SalesAnalyticsController extends Controller
{
    // Container reads the type-hint and injects automatically
    public function __construct(private SalesAnalyticsService $salesAnalyticsService) {}
}
```

This works via PHP reflection. The container inspects the type-hint, builds `SalesAnalyticsService` (auto-resolving its own deps), and injects it.

### How the Container Resolves a Controller

When the router dispatches to `CartController`, the container:

1. Reflects on `CartController::__construct()`.
2. Sees it type-hints `CartService`.
3. Finds a singleton binding for `CartService`.
4. Returns the existing singleton (or creates it on first use).
5. Injects it — the controller never calls `new CartService()`.

### Service Providers: Where Bindings Live

Service providers are the bootstrap entry points for all bindings. They run **once at startup**, before any request:

| Provider | Responsibility |
|---|---|
| `SecretsServiceProvider` | Loads secrets from AWS / Vault / Doppler into Config first — before any other provider reads `config()` |
| `AppServiceProvider` | `FakeStoreService`, `ExternalApiService` singletons; Gate definitions & policies; named rate limiters; `Password::defaults()`; slow-query listener; `Response`, `Http`, `Str` macros |
| `ProductServiceProvider` | `ProductService`, `CartService`, `OrderService`, `CouponService`, `CacheService` singletons; Blade directives (`@admin`, `@customer`, `@currency`); View composers; `Product` + `Order` observer registration |
| `EventServiceProvider` | Maps domain events to their listeners |

### Global Helper Functions

Two autoloaded files (`app/Helpers/helpers.php`, `app/Helpers/AuthHelpers.php`) expose globally available functions that internally use the container:

```php
current_user()   // returns User|Admin|null — checks admin then web guard, impersonation-aware
current_guard()  // returns 'admin'|'web'|null
is_admin()       // true when admin guard active and not impersonating
is_customer()    // true when web guard active and not impersonating
is_guest()       // true when neither guard is active
analytics()      // app(AnalyticsService::class)
format_price()   // Number::currency()
```

### Practical Cheat Sheet

| Situation | Pattern |
|---|---|
| Stateful service used in many places | `$this->app->singleton(ServiceClass::class)` |
| Service with no shared state | Let container auto-resolve via type-hint |
| Service needed in Blade or a closure | `app(ServiceClass::class)` or Facade |
| Third-party library needing config | Bind in a provider, return a configured instance |

---

## Middleware Stack

### Global Web Middleware (applied to every web request)

| Order | Middleware | Purpose |
|---|---|---|
| 1 | `SetLocale` | Reads locale from cookie/session, calls `app()->setLocale()` |
| 2 | `RequestTrackingMiddleware` | Generates/forwards `X-Request-ID`; writes `request_id`, `user_type`, `user_id`, `ip_address` into `Context`; tracks authenticated users in Redis for online-user dashboard |
| 3 | `AuthenticateSession` | Invalidates session when password changes in another tab |
| 4 | `RedirectIfPasswordResetForced` | Redirects users with `force_password_reset = true` |
| 5 | `VerifyCsrfToken` | Excludes `/api/*` and `/submit` |

### Registered Middleware Aliases

| Alias | Class | Purpose |
|---|---|---|
| `auth.apikey` | `AuthenticateApiKey` | Validates `{keyId}.{secret}` token; SHA-256 hash compare; logs `last_used_at` |
| `role` | Spatie `RoleMiddleware` | Asserts named role |
| `permission` | Spatie `PermissionMiddleware` | Asserts named permission |
| `guest_or_customer` | `GuestOrCustomer` | Allows guests and customers, blocks admins |
| `verified` | Laravel built-in | Requires e-mail verification |
| `signed` | Laravel built-in | Validates HMAC-signed URL |

### Named Rate Limiters

| Limiter | Limit | Key | Notes |
|---|---|---|---|
| `api` | 60 / 600 / 6 000 rpm | user id or IP | Tier-based: free / pro / enterprise |
| `login` | 5 per 5 min | `email\|ip` | Logs to `security` channel on hit |
| `password-reset` | 3 per hour | email | Logs to `security` channel on hit |
| `checkout` | 10 per min | user id or IP | — |
| `search` | 30 per min | user id or IP | Skipped when no `?q=` parameter |

`AuthThrottleService` adds a second layer on top: after **5 failures** it locks the account for 15 minutes (cache-backed, guard-aware) and sends a `LoginWarningMail`. After **10 failures** it requires CAPTCHA.

---

## Authentication Strategies

This project supports four independent authentication methods:

### 1. Web Session — Customers (`guard: web`)

Standard Laravel session cookie. Routes in `routes/auth.php`. The `auth` middleware checks this guard by default.

### 2. Web Session — Admins (`guard: admin`)

Separate Eloquent provider backed by the `admins` table. Admin routes use `auth:admin` or `auth:admin,web`. Password reset goes through `routes/admin-auth.php`.

### 3. Sanctum Token (API clients)

Customers call `POST /api/login` → receive a personal access token → include as `Authorization: Bearer {token}` on subsequent requests. Routes protected by `auth:sanctum`. Tokens stored in `personal_access_tokens`.

### 4. Custom API Key (`middleware: auth.apikey`)

Format: `{keyId}.{secret}`.

- `keyId` is a non-secret lookup index (safe to query).
- `secret` is never stored — only its SHA-256 hash lives in `api_keys.key_secret_hash`.
- Comparison uses `hash_equals()` to prevent timing attacks.

Accepted via `Authorization: Bearer`, `X-API-Key` header, or `?api_key=` query string.

---

## Authorization — Gates & Policies

All authorization is registered in `AppServiceProvider::boot()`.

### Policies

| Model | Policy | Key abilities |
|---|---|---|
| `Product` | `ProductPolicy` | `create`, `update`, `delete`, `waitlist` |
| `Order` | `OrderPolicy` | `view`, `cancel`, `update` |
| `ProductReview` | `ReviewPolicy` | `create`, `delete` |
| `User` / `Admin` | `ProfilePolicy` | `update`, `destroy` |
| `Cart` | `CartPolicy` | `view`, `checkout` |

### Named Gates

```php
Gate::define('view-admin-dashboard', fn($user) => $user instanceof Admin);
Gate::define('impersonate-users',    fn($user) => $user instanceof Admin);
Gate::define('view-analytics',       fn($user) => $user instanceof Admin);
Gate::define('edit-comment',         fn($u, $c, $p) => $u->id === $c->user_id || $u->id === $p->user_id);
```

### Super-Admin Bypass

`Gate::before()` returns `true` for any `Admin` instance — granting all abilities — **except** a skip-list:

- `Cart@view`, `Cart@checkout`, `Product@waitlist`

These abilities are intentionally left to the policy to handle so admins cannot accidentally bypass customer-only checkout flows.

### Audit Trail

`Gate::after()` logs every authorization decision (user id, email, ability, result) to the default log channel.

---

## Queue, Jobs & Notifications

Queue connection defaults to `database`. Start the worker with `composer dev`.

### Jobs

| Job | Queue | Purpose |
|---|---|---|
| `ChargePayment` | default | Process payment |
| `GenerateInvoicePdf` | default | Render and store PDF invoice via DomPDF |
| `ReserveStock` / `ReleaseStock` | default | Inventory management on order state transitions |
| `SendOrderConfirmation` | default | Send order confirmation e-mail |
| `ImportProductRowJob` | default | Single row within a CSV batch import |
| `LogSlowQueryJob` | `analytics` | Persist slow-query metadata after response is sent |

### Notifications — `BaseNotification`

All notifications extend `BaseNotification`, which provides shared resilience configuration:

| Property | Value |
|---|---|
| `$tries` | 3 |
| `backoff()` | 10s → 30s → 60s |
| `$timeout` | 60 s |
| Middleware | `ResilientNotificationMiddleware` |
| `failed()` | Logs to Slack; falls back to `SlackFallbackNotification` via e-mail — failures never disappear silently |

---

## Events & Listeners

Events are mapped in `EventServiceProvider`. Queued listeners run asynchronously.

| Event | Listeners |
|---|---|
| `OrderPlaced` | `NotifyAdmin`, `LogEvent`, `SendOrderEmail` |
| `OrderPaid` | `NotifyAdmin`, `UpdateInventory`, `LogEvent`, `SendOrderEmail` |
| `OrderShipped` | `NotifyAdmin`, `LogEvent`, `SendOrderEmail` |
| `OrderDelivered` | `NotifyAdmin`, `LogEvent`, `SendOrderEmail` |
| `ProductStockLow` | `SendStockLowEmail` |

### `CustomerActionSubscriber`

A single subscriber class handles multiple product/cart events:

| Event | Handler | Side-effect |
|---|---|---|
| `ProductViewed` | `onProductViewed` | Tracks analytics + updates `RecentlyViewedService` |
| `ProductAddedToCart` | `onProductAddedToCart` | Tracks analytics (guest-aware) |
| `CartAbandoned` | `onCartAbandoned` | Sends `CartAbandonedMail` — **delayed 24 hours** via `withDelay()` |
| `ProductReviewed` | `onProductReviewed` | Recalculates `avg_rating` on the product |

---

## Model Observers

Registered in `ProductServiceProvider::boot()`. All observer methods run **after database commit** (`$afterCommit = true`).

### `ProductObserver`

| Hook | What it does |
|---|---|
| `saving` | Auto-generates slug from name if empty |
| `creating` | Stamps `created_by_id` / `created_by_type` audit columns |
| `updating` | Stamps `updated_by` morph; auto-activates product on restock; moves image file when slug changes |
| `saved` | Syncs product to / removes from Meilisearch index based on `shouldBeSearchable()` |
| `updated` | Dispatches `ProductStockChanged`, `ProductOutOfStock`, `ProductRestocked`, `ProductStockLow` events on stock change; clears caches |
| `deleted` | Clears cache; removes from Meilisearch index; logs to `product` channel |
| `forceDeleted` | Deletes physical image from storage; clears cache; removes from search index |
| `restored` | Re-indexes in Meilisearch if still publishable |

### `OrderObserver`

Watches for order status transitions and dispatches the corresponding domain events (`OrderPaid`, `OrderShipped`, `OrderDelivered`).

---

## Data Security

### Encrypted Columns

Sensitive fields are encrypted at rest using Laravel's `encrypted` cast (AES-256-CBC, keyed by `APP_KEY`):

| Model | Encrypted fields |
|---|---|
| `User` | `phone`, `address`, `tax_id` |
| `Order` | `shipping_address`, `billing_address`, `shipping_phone` |

### Blind Indexes

Because encrypted columns cannot be searched with `WHERE`, searchable encrypted fields have a corresponding blind index:

```
users.phone               → users.phone_blind_index
orders.shipping_phone     → orders.shipping_phone_blind_index
```

Both are `hash_hmac('sha256', $value, BLIND_INDEX_SECRET)`. Set `BLIND_INDEX_SECRET` in `.env` — never reuse `APP_KEY`.

### Password History

`User::saved()` automatically records each new password hash in `password_histories` (morph relation). Only the last 5 entries are kept. This supports "cannot reuse last N passwords" validation.

### Path Disclosure Prevention

Error notifications use only sanitised, path-stripped information. No raw file system paths are ever included in user-facing messages or logged to channels accessible outside the server.

---

## Secrets Management

`SecretsServiceProvider` runs first and populates `Config` (and `$_ENV`) from the chosen driver.

```dotenv
SECRETS_DRIVER=env      # default — .env as usual
SECRETS_DRIVER=aws      # AWS Secrets Manager (requires aws/aws-sdk-php)
SECRETS_DRIVER=vault    # HashiCorp Vault — token or AppRole auth
SECRETS_DRIVER=doppler  # Doppler service token
```

Secrets are cached in the Laravel cache store (default TTL: 3 600 s). The external API is only called once per cache lifetime, not on every request.

**Failure policy:**

- `local` → logs a warning, continues with `.env` values.
- `production` / `staging` → throws `RuntimeException`, aborts boot. The application **will not start** with missing secrets.

---

## Testing

```bash
composer test          # clears config cache, then runs PHPUnit
php artisan test       # equivalent
```

### Test Suite Layout

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── PasswordConfirmationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── PasswordUpdateTest.php
│   │   └── RegistrationTest.php
│   ├── CheckoutTest.php
│   ├── FakeStoreApiTest.php
│   ├── GatePermissionsTest.php
│   ├── ModelFactoriesTest.php
│   ├── NotificationTest.php
│   ├── ProfileTest.php
│   ├── SharedInvoiceTest.php
│   └── SlackDailyDigestTest.php
└── Unit/
    └── ExampleTest.php
```

All feature tests use `RefreshDatabase` to run against a clean SQLite in-memory database. `phpunit.xml` configures this automatically.

---

## Key Design Decisions

**No business logic in controllers.** Controllers validate input (Form Request), call exactly one Service method, and return a response. All Eloquent queries and rules live in Service classes.

**Lazy-loading disabled outside production.** `Model::preventLazyLoading(! app()->isProduction())` throws an exception in development when a relationship is accessed without eager-loading — eliminating N+1 bugs before they reach production.

**Dual-guard, separate tables.** Customers (`users`) and admins (`admins`) are entirely separate models and guards. There is no `role` column distinguishing them at the row level — the guard is the discriminator. This prevents privilege escalation via role manipulation.

**Request IDs in every log line.** `RequestTrackingMiddleware` injects a UUID into Laravel's `Context` facade. Because `Context` is included in every `Log::*` call automatically, all log lines for a given HTTP request share the same `request_id` — making request tracing in log aggregators trivial.

**Impersonation-aware auth helpers.** `current_user()` and related helpers check `session('impersonate.active')` first. When an admin impersonates a customer, `is_admin()` returns `false` and `is_customer()` returns `true`, so all permission checks behave exactly as if a real customer were logged in.

**Product route model binding via cache.** `Product::resolveRouteBinding()` is overridden to read from a tagged cache (`products.*`) with a 30-minute TTL before falling back to a database query. Every product page hit that is not the first is served without a DB round-trip.

**Sales analytics CSV export.** `SalesAnalyticsController::exportCsv()` streams the response directly with `response()->streamDownload()` — no temporary file is written to disk. It supports four report types (`monthly`, `products`, `customers`, `category`) controlled by a `?type=` query parameter.
