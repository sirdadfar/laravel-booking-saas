# Nobatyar — multi-tenant appointment booking for salons and clinics

<p align="center">
  <img alt="CI" src="https://github.com/sirdadfar/laravel-booking-saas/actions/workflows/ci.yml/badge.svg">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777bb4">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-11-ff2d20">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
</p>

A runnable booking SaaS: every business gets its own public booking page, its own staff
schedules, and a real availability engine that accounts for service duration, cleanup
buffer, existing bookings and time off.

The UI is Persian and RTL, hand-written CSS (no build step), and the whole test suite runs
on SQLite with no external credentials.

> **Honest status:** this is a tested engineering sample, not a hosted product. There is no
> payment gateway, no real SMS provider, no customer login and no analytics dashboard.
> The [“What is not included”](#9-what-is-not-included) section draws those lines explicitly.

Persian documentation: [`README.fa.md`](README.fa.md) · Architecture notes:
[`docs/architecture-fa.md`](docs/architecture-fa.md) · HTTP reference:
[`docs/api-fa.md`](docs/api-fa.md)

---

## 1. The problem

Small salons and clinics usually book appointments in a paper notebook, a spreadsheet, or a
chat thread. The failure modes are predictable: two customers get the same slot, the cleaning
buffer between services is ignored, and the customer has to call to learn what is actually
free.

Here the free slots are a **pure function** of working hours, service duration, buffer and
existing bookings — not a manually maintained list.

## 2. Quick start

```bash
git clone https://github.com/sirdadfar/laravel-booking-saas.git
cd laravel-booking-saas

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

| URL | What it is |
| --- | --- |
| `http://localhost:8000/` | Landing page + demo businesses |
| `http://localhost:8000/rose` | Public booking page (no login) |
| `http://localhost:8000/rose/admin` | Salon admin panel |
| `http://localhost:8000/mehr/admin` | Clinic admin panel |
| `http://localhost:8000/platform/tenants` | Platform tenant list |
| `http://rose.localhost:8000/` | Same booking page via subdomain |

Demo accounts (password `password`): `admin@rose.test`, `admin@mehr.test`,
`admin@nobatyar.test` (platform admin).

## 3. Features

**Public booking (no account)**

- pick a service, a specialist (or “any available specialist”) and a date;
- slots are fetched over JSON and rendered without a page reload;
- Iranian mobile validation, Persian digits accepted;
- confirmation page with a booking code and self-service cancellation inside the window;
- rate limiting on booking creation (10/min) and slot lookups (60/min).

**Tenant admin**

- dashboard: today's appointments, weekly count, monthly completed revenue, status totals;
- bookings: filter by date and status, change status only through allowed transitions;
- services: duration, cleanup buffer, price, colour, assigned staff;
- staff: weekly working hours with **multiple windows per day**
  (`09:00-13:00, 16:00-19:00`).

**Platform**

- tenant list with service / staff / booking / user counts;
- platform admins can open any tenant panel for support; tenant admins cannot.

**Engineering**

- tenant isolation via a global scope that **fails closed** when no tenant is resolved;
- double-booking prevention with a transaction, `lockForUpdate`, and a re-check after locking;
- UTC storage, per-tenant timezone computation;
- SMS confirmation through Laravel's notification pipeline with a local log driver;
- 73 tests / 193 assertions, Pint style check, GitHub Actions on PHP 8.2–8.4.

## 4. Availability rules

For each day, staff member and working window:

1. read the day's windows from `staff_schedules` (Saturday = 0 … Friday = 6);
2. turn blocking bookings (`pending`, `confirmed`, `completed`, `no_show`) and time off into
   busy intervals; the buffer of an existing booking extends its end;
3. generate candidate starts in steps of the tenant's `slot_granularity_minutes` (30 by default);
4. offer a candidate only if **duration + buffer** fits before the window closes and the whole
   occupied range is free;
5. drop past starts and days beyond `booking_horizon_days`.

A **cancelled** booking frees its slot; `completed` and `no_show` keep it blocked for history.
That decision is documented in `App\Enums\BookingStatus`.

## 5. Architecture

```
app/
├── Channels/SmsChannel.php          # log-file SMS driver
├── Concerns/BelongsToTenant.php     # scope + automatic + immutable tenant_id
├── Enums/BookingStatus.php          # states and the allowed transition table
├── Http/Controllers/                # Public / Admin / Auth / Platform
├── Http/Middleware/                 # ResolveTenant, EnsureTenantAdmin, EnsurePlatformAdmin
├── Models/                          # Tenant, User, Service, Staff, StaffSchedule, TimeOff, Customer, Booking
├── Notifications/BookingConfirmed.php
├── Scopes/TenantScope.php
├── Services/                        # AvailabilityService, BookingService, TenantContext
└── Support/                         # AvailableSlot, IranianWeek, Phone
```

### Tenant resolution

In priority order:

1. the path parameter — `/rose/bookings`;
2. the `X-Tenant` header (API clients and tests);
3. the subdomain — `rose.localhost`, base domains in `TENANCY_BASE_DOMAINS`.

A present-but-unknown path parameter is a **404**, never a silent fallback to “no tenant”.
When none of the three resolves, the landing page is shown.

## 6. Tests

```bash
php artisan test          # 73 tests
vendor/bin/pint --test    # style
```

Covered: buffer and closing-time boundaries, closed days, time off, cancellation freeing a
slot, booking horizon, granularity, timezone conversion, price snapshot, status transitions,
cancellation window, tenant isolation (including fail-closed queries and immutable
`tenant_id`), HTTP validation, 409 on a taken slot, XSS escaping, role access, path/header/
subdomain resolution, rate limiting and the SMS driver.

## 7. Design decisions worth a comment

- **Fail closed.** `TenantScope` adds `whereRaw('1 = 0')` when no tenant is bound, instead of
  skipping the `where` and leaking every tenant's rows.
- **Immutable `tenant_id`.** Moving a row between tenants raises an exception; it is an
  explicit operation, not a normal `update`.
- **Lock, then re-check.** The fast availability check is not trusted inside the transaction;
  the engine runs again after `lockForUpdate`.
- **Price snapshot.** Changing a service price never rewrites past bookings.
- **Buffer is not part of `ends_at`.** `ends_at` is the service duration; the buffer applies to
  availability and overlap checks. Reports stay truthful.
- **Route parameters are read by name** (`$request->route('service')`). Laravel passes route
  parameters to controller methods positionally after dependency injection, so relying on the
  order breaks the moment `{tenant}` is added to the prefix. That bug happened here; the tests
  caught it.

## 8. Known trade-offs

- SQLite is used for development and tests; production should use MySQL or PostgreSQL.
- Notifications are sent synchronously (no queue); a slow provider would slow the request.
- The `staff_service` eligibility query goes through the query builder rather than a
  relationship, to keep tenant scoping explicit.
- The public confirmation page is protected only by the booking code (128-bit random suffix).

## 9. What is not included

- payment gateway / deposits;
- real SMS delivery — the channel appends to `storage/logs/sms.log`;
- customer accounts, OTP login, booking history;
- recurring appointments, waitlists, discounts, referral codes;
- drag-and-drop weekly calendar, revenue reports over time;
- two-factor auth, email verification, password reset;
- a JSON API with tokens (Sanctum) for a mobile app.

## 10. Roadmap

- [ ] deposit payments
- [ ] real SMS provider behind the existing channel + queue
- [ ] customer OTP login and history
- [ ] weekly staff calendar with drag-and-drop rescheduling
- [ ] revenue and no-show reporting
- [ ] Sanctum API for a mobile client
- [ ] concurrent booking load test on MySQL

## 11. License

MIT — see [`LICENSE`](LICENSE). Author: [sirdadfar](https://github.com/sirdadfar).
