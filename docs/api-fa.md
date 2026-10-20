# مرجع HTTP — نوبت‌یار

همهٔ مسیرها زیر `{tenant}` هستند که اسلاگ کسب‌وکار است (`rose`, `mehr`).
تشخیص مستأجر به ترتیب: پارامتر مسیر ← هدر `X-Tenant` ← زیردامنه.

> این پروژه API توکن‌دار ندارد. مسیرهای زیر همان مسیرهای وب‌اند و `POST`ها به
> توکن CSRF نیاز دارند. برای کلاینت موبایل باید Sanctum اضافه شود.

## مسیرهای عمومی

| متد | مسیر | پاسخ |
| --- | --- | --- |
| `GET` | `/{tenant}` | صفحهٔ رزرو (HTML) |
| `GET` | `/{tenant}/slots?service_id=&date=&staff_id=` | زمان‌های آزاد (JSON) |
| `POST` | `/{tenant}/bookings` | ثبت نوبت و ریدایرکت به صفحهٔ تأیید |
| `GET` | `/{tenant}/bookings/{code}` | صفحهٔ تأیید |
| `POST` | `/{tenant}/bookings/{code}/cancel` | لغو توسط مشتری |

### نمونهٔ زمان‌های آزاد

```bash
curl -s "http://localhost:8000/rose/slots?service_id=1&date=2026-05-05" \
  -H "Accept: application/json"
```

```json
{
  "date": "2026-05-05",
  "timezone": "Asia/Tehran",
  "slots": [
    {
      "starts_at": "2026-05-05T09:00:00+03:30",
      "ends_at": "2026-05-05T09:30:00+03:30",
      "time": "09:00",
      "end_time": "09:30",
      "staff_ids": [1, 2]
    }
  ]
}
```

`staff_ids` یعنی این زمان برای کدام متخصص‌ها آزاد است. اگر `staff_id` بفرستید، فقط همان
متخصص بررسی می‌شود.

### نمونهٔ ثبت نوبت (فرم)

```bash
# توکن CSRF را از صفحهٔ رزرو بگیرید (meta name="csrf-token") و کوکی نشست را نگه دارید.
curl -s -X POST "http://localhost:8000/rose/bookings" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -b cookies.txt \
  --data-urlencode "_token=$TOKEN" \
  --data-urlencode "service_id=1" \
  --data-urlencode "starts_at=2026-05-05 10:00" \
  --data-urlencode "name=سحر موسوی" \
  --data-urlencode "phone=09121234567"
```

پاسخ موفق: `302` به `/{tenant}/bookings/{code}`.

## خطاها

| کد | معنی |
| --- | --- |
| `403` | کاربر وارد شده به این مستأجر یا به پنل پلتفرم دسترسی ندارد |
| `404` | مستأجر، خدمت، متخصص یا نوبت پیدا نشد (یا به این مستأجر تعلق ندارد) |
| `409` | زمان انتخاب‌شده هم‌زمان توسط دیگری گرفته شد (`code: slot_unavailable`) |
| `419` | توکن CSRF منقضی یا نامعتبر است (صفحهٔ فارسی `errors/419`) |
| `422` | اعتبارسنجی ورودی یا نقض قاعدهٔ رزرو (`code: booking_rule_violation`) |
| `429` | عبور از محدودیت نرخ |
| `500` | خطای پیش‌بینی‌نشده؛ بدون جزئیات استثنا و stack trace |

پاسخ‌های HTML خطا از صفحه‌های فارسی `resources/views/errors/` می‌آیند. بدنهٔ خطای JSON:

```json
{
  "message": "این زمان دیگر آزاد نیست.",
  "code": "slot_unavailable"
}
```

پاسخ ۴۲۹ برای درخواست‌هایی که `Accept: application/json` دارند همان بدنهٔ JSON را با پیام
محدودیت نرخ برمی‌گرداند؛ در غیر این صورت صفحهٔ `errors/429` نمایش داده می‌شود.

## محدودیت نرخ

| مسیر | سقف |
| --- | --- |
| `POST /login` | ۵ در دقیقه به ازای `sha1(lower(email))` + IP |
| `POST /{tenant}/bookings` | ۱۰ درخواست در دقیقه برای هر IP |
| `GET /{tenant}/slots` | ۶۰ درخواست در دقیقه برای هر IP |
| `GET /{tenant}/bookings/{code}` | ۶۰ درخواست در دقیقه برای هر IP |
| `POST /{tenant}/bookings/{code}/cancel` | ۱۰ درخواست در دقیقه برای هر IP |

محدودیت ورود عمداً بر اساس ایمیل + IP است تا تلاش برای چند حساب از یک IP هم سهمیهٔ جدا
نداشته باشد؛ تغییر حرف بزرگ/کوچک ایمیل سبد تازه نمی‌سازد.

## سرصفحه‌های امنیتی

هر پاسخ گروه `web` این سرصفحه‌ها را دارد:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()`
- `Content-Security-Policy-Report-Only` (سیاست گزارش‌محور، بدون enforcement)
- `Strict-Transport-Security` فقط روی درخواست TLS

`X-Powered-By` حذف می‌شود. اگر پشت پروکسی معکوس هستید، برای تشخیص درست HTTPS باید
`TrustProxies` را تنظیم کنید تا HSTS و کوکی `Secure` فعال شوند.

## صفحه‌بندی فهرست‌های مدیریت

فهرست نوبت‌ها، خدمات، تیم و کسب‌وکارهای پلتفرم ۱۰۰ ردیف در هر صفحه برمی‌گردانند. پارامتر
`page` استاندارد لاراول است و فیلترهای فعلی (`date`, `status`) در لینک‌های صفحه حفظ می‌شوند.

## مسیرهای پنل (نیازمند ورود)

| متد | مسیر | نقش |
| --- | --- | --- |
| `GET` | `/{tenant}/admin` | مدیر همان کسب‌وکار یا مدیر پلتفرم |
| `GET` | `/{tenant}/admin/bookings?date=&status=` | همان |
| `POST` | `/{tenant}/admin/bookings/{id}/status` | همان |
| `GET/POST` | `/{tenant}/admin/services` | همان |
| `PUT/DELETE` | `/{tenant}/admin/services/{id}` | همان |
| `GET/POST` | `/{tenant}/admin/staff` | همان |
| `PUT/DELETE` | `/{tenant}/admin/staff/{id}` | همان |
| `GET` | `/platform/tenants` | فقط مدیر پلتفرم |

## فرمت ساعات کاری

در فرم تیم، هر روز یک رشته می‌گیرد؛ چند بازه با کاما یا خط جدید جدا می‌شوند:

```
09:00-13:00, 16:00-19:00
```

خانه‌گذاشتن یک روز یعنی تعطیل بودن آن روز. قالب نامعتبر (`25:00-13:00`) یا بازهٔ معکوس
(`19:00-09:00`) با خطای اعتبارسنجی روی همان روز رد می‌شود.
