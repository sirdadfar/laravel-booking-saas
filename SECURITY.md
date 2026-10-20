# Security policy

## Supported versions

| Version | PHP | Laravel | Supported |
| --- | --- | --- | --- |
| 1.1.x | 8.2, 8.3, 8.4 | 12.x | Yes |
| 1.0.x | 8.2, 8.3 | 11.x / 12.x | No — upgrade to 1.1.x |

Only the latest release line receives fixes. The PHP versions above are the ones exercised in
CI (`.github/workflows/ci.yml`); a different PHP minor is untested even if it happens to run.

## Reporting a vulnerability

Use **GitHub Security Advisories** for this repository:

<https://github.com/sirdadfar/laravel-booking-saas/security/advisories/new>

That channel is private and is the preferred way to report. Please do **not** open a public
issue for a suspected vulnerability, and do not include real customer data, credentials or
production `.env` contents in a report.

If GitHub Advisories is unavailable, open a minimal public issue asking for a private contact
channel — without exploit details.

Please include:

- the affected version or commit;
- the PHP and database versions;
- reproduction steps or a proof of concept;
- the impact you believe it has.

## Response expectation

This is a portfolio project maintained on a best-effort basis, with **no SLA and no bug bounty**.
Reports are acknowledged when time allows; a fix may take days or weeks depending on severity
and available time. You will be told whether the report is accepted, and credited in the
advisory if you want.

## Scope

**In scope**

- tenant isolation and any way to read or write another tenant's data;
- authorization bypass on admin or platform routes;
- CSRF, session fixation or session leakage;
- booking integrity (double booking, price manipulation, status transition bypass);
- injection (SQL, HTML/JS, header) through any request input;
- rate-limit bypass that makes credential or booking-code guessing practical;
- exposure of secrets, stack traces or personal data through the application;
- missing or ineffective security headers that materially change risk.

**Out of scope**

- vulnerabilities in PHP, Laravel or Composer dependencies — report them upstream; this project
  tracks them with `composer audit`;
- denial of service, volumetric attacks and load testing;
- social engineering and physical access;
- a deployment that runs with `APP_DEBUG=true`, exposes `storage/logs/sms.log`, serves over
  plain HTTP, or leaves the demo accounts and `password` in place;
- the deliberately documented limitations in `README.fa.md` §8 (no payment gateway, booking
  code without a second factor, CSP Report-Only, no audit log);
- missing features rather than defects.

## Security design

### Tenant isolation

- `App\Scopes\TenantScope` adds `whereRaw('1 = 0')` when no tenant is bound: queries **fail
  closed** instead of returning every tenant's rows.
- `BelongsToTenant` fills `tenant_id` on create, refuses creation without a tenant context, and
  throws if `tenant_id` is changed after creation.
- `ResetTenantContext` runs at the start of every web request and clears the bound tenant, so a
  long-lived worker cannot serve request *n+1* with request *n*'s tenant.

### Authentication and authorization

- Passwords use Laravel's default hash driver (bcrypt); no plaintext or reversible storage.
- Login regenerates the session ID; logout invalidates the session and regenerates the CSRF
  token.
- Every tenant admin route is behind `tenant`, `auth` and `tenant.admin`.
  `EnsureTenantAdmin` verifies membership of the resolved tenant, not just authentication.
- The platform area is behind `auth` and `platform.admin`. `is_platform_admin` is **not**
  mass-assignable; only the seeder sets it, via `forceFill()`.
- Records from another tenant return 404 through route-model binding/`findOrFail`, so the
  response does not confirm that the record exists.
- `staff_ids` and `service_ids` are validated with `Rule::exists(...)->where('tenant_id', ...)`
  because the `staff_service` pivot has no tenant column.

### CSRF and sessions

- Every state-changing form carries `@csrf` (and `@method` where needed); there are no
  state-changing GET routes.
- Session cookies are `HttpOnly`, `SameSite=Lax`, encrypted (`SESSION_ENCRYPT=true`) and become
  `Secure` when `SESSION_SECURE_COOKIE=true` is set behind HTTPS.
- `.env.example` ships with `APP_DEBUG=false`, `LOG_LEVEL=warning` and encrypted sessions.

### Rate limits

| Endpoint | Limit | Key |
| --- | --- | --- |
| `POST /login` | 5/min | `sha1(lower(email))` + IP |
| `POST /{tenant}/bookings` | 10/min | IP |
| `GET /{tenant}/slots` | 60/min | IP |
| `GET /{tenant}/bookings/{code}` | 60/min | IP |
| `POST /{tenant}/bookings/{code}/cancel` | 10/min | IP |

Exceeding a limit returns HTTP 429 with the Persian error page. The login limiter is a named
`login` limiter registered in `AppServiceProvider`, so a throttled login cannot be bypassed by
changing the email alone.

### Security headers

`App\Http\Middleware\SecurityHeaders` adds:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Content-Security-Policy-Report-Only` — a documented report-only policy, because the layout
  contains a small inline script for the no-flash theme; no reporting endpoint is configured.
- `Strict-Transport-Security` only when the request is already HTTPS.
- Removes `X-Powered-By`.

### Data, money and logging

- Times are stored in UTC and rendered in the tenant timezone; money is an integer number of
  tomans end-to-end, snapshotted on the booking.
- Booking creation runs in a transaction that locks the tenant row and the specialist row and
  re-checks availability after locking.
- Application logs do not print passwords, tokens or card data.
- **Documented PII caveat:** the development SMS driver writes the recipient mobile number and
  message to `storage/logs/sms.log`, created with mode `0600`. In production, ship these
  notifications through a real provider (or a queue with retention rules) and keep that file off
  any web-served path. The number is intentionally not masked because an existing test asserts
  the raw recipient; masking it is a follow-up, not a claim.
- Error pages (403/404/419/429/500) are branded, Persian and contain no exception details,
  paths or stack traces.

### Secrets and dependencies

- Secrets live only in `.env`, which is git-ignored. `.env.example` contains placeholders only.
- No `env()` call exists outside `config/`, so `php artisan config:cache` is safe.
- `composer audit` is part of CI and fails the build on a known advisory. Laravel is pinned to a
  patched 12.x release (see `CHANGELOG.md`, `CVE-2026-48019`).

### Known limitations relevant to security

These are intentional and documented in `README.fa.md` §8: the booking code is the only
credential for the confirmation page and has no OTP; CSP is report-only; there is no audit log,
no 2FA, no password reset and no queue; SQLite is not a production database and
`lockForUpdate` has no effect on it. Deployments must add TLS, `TrustProxies`, SMTP, backups and
log retention.
