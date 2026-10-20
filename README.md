# Nobatyar — multi-tenant appointment booking for salons and clinics

<p align="center">
  <img alt="CI" src="https://github.com/sirdadfar/laravel-booking-saas/actions/workflows/ci.yml/badge.svg">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777bb4">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-ff2d20">
  <img alt="Version" src="https://img.shields.io/badge/version-1.1.0-D9B65A">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
</p>

Every business gets its own public booking page, sets each specialist's working hours, and the
availability engine combines service duration, cleanup buffer, time off and existing bookings.
The interface is Persian and RTL, built without a CSS framework and without a build step. Each
tenant's data is isolated by a global scope that **fails closed** when no tenant is resolved.

> **Honest status:** this is an engineered, tested sample — not a hosted SaaS product. There is
> no payment gateway, no real SMS delivery, no customer login and no analytics dashboard. The
> [“Limitations and what is not included”](#8-limitations-and-what-is-not-included) section
> states those boundaries precisely.

Persian documentation: [`README.fa.md`](README.fa.md) · Security: [`SECURITY.md`](SECURITY.md) ·
Architecture: [`docs/architecture-fa.md`](docs/architecture-fa.md) ·
HTTP reference: [`docs/api-fa.md`](docs/api-fa.md)

---

## 1. What problem does this project solve

Small salons and clinics often keep appointments in a paper notebook, a spreadsheet, or a
WhatsApp/Telegram thread. The result is predictable:

- two customers get booked for the same hour with the same specialist;
- service duration and equipment cleanup time are not part of the calculation;
- the customer cannot tell which slots are actually free and has to call;
- the owner cannot say how many appointments this week produced, or how much revenue.

Here the free slots are a **pure function of working hours, service duration, buffer, time off
and existing bookings** — not a manually maintained list. The same rules apply to the public
page and to the admin panel.

## 2. Features

**Public booking (no account)**

- choose a service, a specialist (or “any available specialist”) and a date;
- fetch free slots with `fetch` and render buttons without reloading the page;
- book with a name, an Iranian mobile number (Persian/Arabic digits accepted) and an optional
  note;
- confirmation page with a booking code, a copy-code button and cancellation until the deadline;
- rate limits on booking creation (10/min), slot lookup (60/min), the confirmation page
  (60/min) and cancellation (10/min).

**Tenant admin**

- dashboard: today's appointments, this week's count, monthly revenue, status totals;
- bookings: filter by date and status, change status only through allowed transitions,
  paginated busy days;
- services: duration, cleanup buffer, price (integer tomans), colour and assigned specialists;
- team: weekly working hours with **multiple windows per day**
  (`09:00-13:00, 16:00-19:00`);
- inline editing with native `<details>` and paginated lists (100 rows per page).

**Platform**

- tenant list with service, staff, booking and user counts;
- a platform admin can enter any tenant panel for support; a tenant admin only their own.

**Engineering**

- tenant isolation via a global scope that returns no rows when no tenant is resolved;
- double-booking prevention with a transaction, a tenant row lock and a re-check after locking;
- all times stored in UTC and computed in each tenant's timezone;
- SMS confirmation through Laravel's notification channel with a local log driver;
- money as integers (tomans) everywhere; no decimals and no float arithmetic;
- 99 tests and 280 assertions, Pint and CI on PHP 8.2/8.3/8.4.

### Interface design

- **Liquid Glass** design language on a premium black + gold palette; **dark (default)** and
  **light** modes with a header toggle, remembered in `localStorage` and honouring
  `prefers-color-scheme` while the user has not chosen (no colour flash on load);
- a complete token system in `public/css/app.css` — **no CSS framework and no build step**:
  glass surfaces, hairline separators, a consistent radius/spacing scale, tabular figures for
  money and times;
- Persian typography with **Vazirmatn** and an explicit fallback stack; the reason for
  substituting the design system's font is recorded in `design-system/nobatyar/MASTER.md`;
- one outline icon family as a Blade component
  (`resources/views/components/icon.blade.php`) — no raster icons, no emoji;
- accessibility: measured text contrast in both modes (≥ 4.5:1), control borders with
  `--line-control` at least 3:1, 44 px touch targets, `:focus-visible` rings on every control,
  a focusable form error summary with `role="alert"` linked to each field, `aria-live` on free
  slots, a “skip to content” link and full `prefers-reduced-motion` support;
- below 760 px tables collapse into label/value cards (`data-label`); the services table stays
  free of horizontal scrolling at mid-desktop widths with `table-layout: fixed` and a bounded
  edit column;
- files: `public/css/app.css`, `public/js/app.js`,
  `resources/views/components/icon.blade.php`, `design-system/nobatyar/MASTER.md`.
  Run the project to see the interface; no synthetic screenshots are shipped in the repository.

## 3. Prerequisites

| Item | Version / note |
| --- | --- |
| PHP | 8.2, 8.3 or 8.4 (all three are tested in CI) |
| PHP extensions | `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `ctype`, `json`, `tokenizer`, `xml`, `fileinfo` |
| Composer | version 2 |
| Database | SQLite for local development and tests; MySQL 8 or PostgreSQL 13+ for production |
| Node/npm | **not required** — CSS and JS are hand-written and there is no build step |
| External service | **not required** — SMS and email are written to logs by default |

## 4. Installation and setup

From a clean checkout to a working page:

```bash
git clone https://github.com/sirdadfar/laravel-booking-saas.git
cd laravel-booking-saas

composer install
cp .env.example .env
php artisan key:generate

# SQLite is the default and needs no separate server.
touch database/database.sqlite

php artisan migrate --seed
php artisan serve
```

Then open `http://localhost:8000/`. If `PHP_CLI_SERVER_WORKERS` is set in `.env`,
`php artisan serve` uses it.

| URL | What it is |
| --- | --- |
| `http://localhost:8000/` | Landing page + demo businesses |
| `http://localhost:8000/rose` | Rose salon booking page (no login) |
| `http://localhost:8000/rose/admin` | Salon admin panel |
| `http://localhost:8000/mehr/admin` | Mehr clinic admin panel |
| `http://localhost:8000/platform/tenants` | Tenant list (platform admin) |
| `http://rose.localhost:8000/` | The same booking page via subdomain |

Demo accounts (password for all: `password`):

| Role | Email |
| --- | --- |
| Rose salon admin | `admin@rose.test` |
| Mehr clinic admin | `admin@mehr.test` |
| Platform admin | `admin@nobatyar.test` |

> Demo data is created once. To rebuild it: `php artisan migrate:fresh --seed`.

### Installing on MySQL or PostgreSQL

SQLite is not recommended for production. For MySQL:

```bash
mysql -u root -p -e "CREATE DATABASE nobatyar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Then in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nobatyar
DB_USERNAME=nobatyar
DB_PASSWORD=...
```

and run `php artisan migrate --seed`. For PostgreSQL use the same values with
`DB_CONNECTION=pgsql` and `DB_PORT=5432`. Tests always run on in-memory SQLite
(`phpunit.xml`) and do not read your `.env`.

### If something does not work

| Symptom | Common cause and fix |
| --- | --- |
| `No application encryption key has been specified` | `php artisan key:generate` was not run. |
| `Database file at path ... does not exist` | Run `touch database/database.sqlite`. |
| 500 with a grey page | `APP_DEBUG=false`; temporarily set it to `true` locally to see details, then revert. |
| `rose.localhost` does not open | Some browsers/systems do not resolve `*.localhost`; use the `/rose` path. |
| Port 8000 is taken | `php artisan serve --port=8001` and read the URLs with that port. |
| Write permission error | `storage/` and `bootstrap/cache/` must be writable by the web user. |

## 5. Configuration

Every key in `.env.example` ships with a default. No required key is left without a clear error
message; the only mandatory value is `APP_KEY`, created by `key:generate`.

| Key | Default | Meaning |
| --- | --- | --- |
| `APP_ENV` | `local` | Environment; `production` in production |
| `APP_DEBUG` | `false` | Error detail display; never `true` in production |
| `APP_URL` | `http://localhost:8000` | Base for generated URLs |
| `APP_TIMEZONE` | `UTC` | Application timezone; times are always stored in UTC |
| `APP_LOCALE` | `fa` | Interface language |
| `DB_CONNECTION` | `sqlite` | `sqlite`, `mysql` or `pgsql` |
| `DB_DATABASE` | `database/database.sqlite` | SQLite file path or database name |
| `DB_HOST` / `DB_PORT` / `DB_USERNAME` / `DB_PASSWORD` | empty | MySQL/PostgreSQL connection |
| `SESSION_DRIVER` | `database` | Session storage; `redis` for multiple servers |
| `SESSION_ENCRYPT` | `true` | Encrypt the session payload |
| `SESSION_HTTP_ONLY` | `true` | Session cookie unreadable from JavaScript |
| `SESSION_SAME_SITE` | `lax` | Cookie-level CSRF protection |
| `SESSION_SECURE_COOKIE` | `null` | Set to `true` behind HTTPS |
| `SESSION_LIFETIME` | `120` | Minutes |
| `CACHE_STORE` | `database` | Rate limiting and cache; `redis` for multi-server production |
| `QUEUE_CONNECTION` | `sync` | Queue; notifications are synchronous |
| `MAIL_MAILER` | `log` | Email is written to the log; SMTP in production |
| `SMS_DRIVER` | `log` | The only available driver; file log |
| `SMS_LOG_PATH` | `storage/logs/sms.log` | SMS file path; must be protected in production |
| `TENANCY_BASE_DOMAINS` | `localhost,example.test` | Base domains for subdomain resolution |
| `TENANCY_HEADER` | `X-Tenant` | Tenant resolution header for clients |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stack` / `warning` | Production log level; `debug` in development |

> `config:cache` is safe: no `env()` is called outside `config/`. After changing `.env` in
> production, run `php artisan config:cache` again.

## 6. Usage

**1) Customer booking**

1. Open `/rose`; choose a service, a specialist and a date.
2. Free slots come from `GET /rose/slots` and only show ranges where
   “duration + buffer” fits and does not overlap a booking or time off.
3. Pick a time, enter a name and mobile number, and submit the form.
4. You are redirected to `/rose/bookings/NB-XXXXXX`; keep the booking code.
5. Until the `cancellation_window_hours` deadline (6 hours by default) you can cancel from the
   same page.

**2) Tenant admin**

1. Sign in at `/login` (`admin@rose.test` / `password`).
2. Dashboard: today's appointments, the week's count, monthly completed revenue and statuses.
3. “Bookings”: filter by date and status; allowed transitions are shown as buttons
   (`pending → confirmed → completed/no_show/cancelled`).
4. “Services”: set duration, buffer, price, colour and specialists. A service with booking
   history cannot be deleted; deactivate it instead.
5. “Team”: separate each day's shifts with commas
   (`09:00-13:00, 16:00-19:00`). Leaving a day empty means closed.

**3) Platform admin**

`/platform/tenants` lists every business with counters. A platform admin can enter
`/{tenant}/admin` for any business for support; a tenant admin requesting another panel gets a
403.

**4) Subdomain**

If `TENANCY_BASE_DOMAINS` contains `localhost`, `rose.localhost:8000` is the booking page for
`rose`. The path parameter always takes priority over the `X-Tenant` header and the subdomain.

## 7. Security

The full model and reporting scope are in [`SECURITY.md`](SECURITY.md). What the code enforces:

- **Tenant isolation:** `TenantScope` adds `whereRaw('1 = 0')` when no tenant is bound;
  creating a model without context throws; `tenant_id` is immutable after creation.
  `ResetTenantContext` clears the context at the start of every request so it cannot leak in
  long-lived workers.
- **Authorization:** every admin route sits behind `tenant` + `auth` + `tenant.admin`;
  `EnsureTenantAdmin` checks that the user belongs to that tenant and `EnsurePlatformAdmin`
  guards the platform boundary. Records belonging to another tenant return 404 via
  `findOrFail`, not a 403 that leaks existence.
- **Cross-tenant links:** `staff_ids` and `service_ids` are validated with
  `Rule::exists(...)->where('tenant_id')`; the `staff_service` table has no `tenant_id`
  column, so this boundary is enforced in the controller and covered by a test.
- **CSRF and sessions:** every form has `@csrf` (and `@method` for PUT/DELETE); login calls
  `session()->regenerate()` and logout calls `invalidate()` + `regenerateToken()`; cookies are
  `HttpOnly` and `SameSite=Lax`, and become `Secure` over HTTPS.
- **Rate limits:** login 5 attempts per minute per “email hash + IP”; booking creation 10,
  slot lookup 60, confirmation page 60 and cancellation 10 per minute per IP. Exceeding a
  limit returns 429.
- **Security headers:** `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` and a CSP in
  **Report-Only** mode (the interface has an inline theme script). HSTS is sent only on TLS
  requests.
- **Errors and logging:** `APP_DEBUG=false` in `.env.example`; the 403/404/419/429/500 pages are
  Persian and contain no stack trace. Application logs print no card numbers or passwords. The
  one documented exception is `storage/logs/sms.log`, which contains the recipient number for
  the development driver (see `SECURITY.md`).
- **Money and concurrency:** the price is snapshotted as an integer at booking time; booking is
  transactional, locks the tenant and specialist rows, and re-checks after locking.
- **Dependencies:** `composer audit` reports no advisories and is mandatory in CI.

## 8. Limitations and what is not included

So that the claims match reality:

- **No payment gateway**; deposits/online payment are not implemented.
- **No real SMS delivery**; `SmsChannel` writes the message to `storage/logs/sms.log`. A
  Kavenegar or SMS.ir adapter is one class, but it is untested and not claimed.
- **No customer login** or booking history; a customer sees their page only through the booking
  code. That code is the page's only credential — anyone who sees it sees the page.
- **The booking code has no second factor (OTP)**; rate limits on cancellation and confirmation
  make guessing expensive, but they are not a substitute for real authentication.
- **Recurring appointments, waitlists, discounts, referral codes, tax and multi-currency** are
  not implemented.
- **No drag-and-drop calendar** or weekly specialist view.
- **No queue** for notifications; sending is synchronous. A notification failure does not fail
  the booking (try/catch + report), but a slow provider can slow the response.
- **No two-factor auth, email verification or password reset** in this sample.
- **No token API (Sanctum)** and no webhooks; the routes are the web routes with CSRF.
- **CSP is not enforced**, only Report-Only; a strict policy would need a nonce/hash for the
  inline script, which conflicts with “no build step”.
- **No audit log**, automatic backups or error monitoring inside the project.
- **SQLite is not sufficient for production**; MySQL/PostgreSQL is recommended. A real
  concurrency test against MySQL has not been run; `lockForUpdate` is a no-op on SQLite, where
  writers serialize at the file level.
- **DST** is delegated to Carbon; there is no dedicated test for a daylight-saving transition in
  a tenant timezone. Iran has had no DST since 2022, but a tenant in another zone should be
  tested separately.
- For production, set `SESSION_DRIVER`, `CACHE_STORE`, `SESSION_SECURE_COOKIE=true`, SMTP and
  database backups, and terminate TLS at the edge (and configure `TrustProxies` so HTTPS is
  detected).

## 9. Development and testing

```bash
php artisan test           # 99 tests / 280 assertions
vendor/bin/pint --test     # style check
vendor/bin/pint            # auto-fix
composer audit             # vulnerable dependencies
php artisan view:cache && php artisan view:clear
```

Test coverage:

| File | Subject |
| --- | --- |
| `AvailabilityTest` | buffer, closing time, time off, cancellation, booking horizon, granularity, timezone |
| `BookingServiceTest` | creation, no duplicates, price snapshot, status transitions, cancellation window, SMS |
| `TenantIsolationTest` | fail-closed scope, immutable `tenant_id`, no cross-tenant leakage |
| `PublicBookingHttpTest` | page, JSON slots, validation, 409 for a taken slot, XSS |
| `AdminHttpTest` | access, service/team CRUD, status transitions, role login |
| `TenantResolutionHttpTest` | path, header, subdomain, path priority over header |
| `RateLimitTest` | booking creation rate limit |
| `CancelThrottleTest` | cancellation rate limit with a booking code |
| `LoginThrottleTest` | login rate limit and per-email buckets |
| `CrossTenantPivotTest` | rejects cross-tenant service/specialist links and 404 for another tenant's booking status |
| `SecurityHardeningTest` | security headers, HSTS, no mass-assignment of the platform flag |
| `PublicConfirmationBadgeTest` | badge class on the public confirmation page |
| `SmsChannelTest` | writes the confirmation message to the SMS log |
| `PhoneTest` (unit) | Persian/Arabic digit normalisation and mobile validation |
| `IranianWeekTest` (unit) | Saturday = 0, day labels, week start |
| `BookingStatusBadgeTest` (unit) | status modifiers match the CSS rule set |

CI in `.github/workflows/ci.yml` runs on PHP 8.2, 8.3 and 8.4: install dependencies, generate
`APP_KEY`, `pint --test`, `composer audit`, `config:cache`/`view:cache`, and
`php artisan test`.

**Adding a feature:** add the route in `routes/web.php`, keep domain logic in
`app/Services`, let the controller validate and coordinate only, and do not forget
`tenant`+`auth`+`tenant.admin` on admin routes. Write a new test for every behaviour change;
do not change existing tests to make them pass.

## 10. Project structure

```
app/
├── Channels/SmsChannel.php              # SMS driver (writes to a log)
├── Concerns/BelongsToTenant.php         # scope + automatic + immutable tenant_id
├── Enums/BookingStatus.php              # states, transitions and badge class
├── Exceptions/                          # domain errors (booking/slot/tenant)
├── Http/Controllers/
│   ├── Admin/                           # dashboard, bookings, services, team
│   ├── Auth/LoginController.php
│   ├── Platform/TenantController.php
│   └── Public/PublicBookingController.php
├── Http/Middleware/                     # ResolveTenant, ResetTenantContext,
│                                        # EnsureTenantAdmin, EnsurePlatformAdmin, SecurityHeaders
├── Models/                              # Tenant, User, Service, Staff, StaffSchedule,
│                                        # TimeOff, Customer, Booking
├── Notifications/BookingConfirmed.php
├── Providers/AppServiceProvider.php     # SMS channel + login RateLimiter
├── Scopes/TenantScope.php
├── Services/
│   ├── AvailabilityService.php          # free-slot generation
│   ├── BookingService.php               # create, cancel and transition
│   └── TenantContext.php                # current tenant per request
└── Support/                             # AvailableSlot, IranianWeek, Phone, PersianNumber

config/                                  # app, database, session, tenancy, sms, ...
database/migrations|factories|seeders
public/css/app.css                       # design system (no build)
public/js/app.js                         # theme, reveal, form errors, code copy
resources/views/
├── layouts/app.blade.php
├── components/icon.blade.php
├── partials/pagination.blade.php
├── public/{booking,confirmation}.blade.php
├── admin/{dashboard,bookings,services,staff}.blade.php
├── platform/tenants.blade.php
├── auth/login.blade.php
└── errors/{403,404,419,429,500}.blade.php
routes/web.php
tests/{Unit,Feature,Concerns}
docs/{architecture-fa.md,api-fa.md}
design-system/nobatyar/MASTER.md
```

The booking transaction order and the tenant-isolation threat model are in
[`docs/architecture-fa.md`](docs/architecture-fa.md); the HTTP contract is in
[`docs/api-fa.md`](docs/api-fa.md).

## 11. License and commercial use

Released under the **MIT** license — see [`LICENSE`](LICENSE). Commercial use, modification,
distribution and inclusion in a closed product are permitted, provided the license text and
copyright notice are kept. The software is provided “as is”, without warranty or official
support.

- Laravel and the Composer dependencies are MIT/Apache-2.0 (see `composer.lock`).
- The **Vazirmatn** font is loaded from Google Fonts under the SIL Open Font License and is not
  distributed in the repository; without network access, the CSS fallback stack is used.

## 12. Support

- **Bug or feature request:** [GitHub Issues](https://github.com/sirdadfar/laravel-booking-saas/issues)
  with the PHP version, browser, reproduction steps and `php artisan about` output (without
  `.env`).
- **Security vulnerability:** use
  [GitHub Security Advisories](https://github.com/sirdadfar/laravel-booking-saas/security/advisories/new);
  details in [`SECURITY.md`](SECURITY.md). Do not publish security issues in public Issues.
- This is a portfolio sample: responses are best-effort with no SLA. For commercial use, an
  independent security review and load test are recommended.

Author: [sirdadfar](https://github.com/sirdadfar)
