# نوبت‌یار — نوبت‌دهی آنلاین چند‌مستأجری برای سالن و کلینیک

<p align="center">
  <img alt="CI" src="https://github.com/sirdadfar/laravel-booking-saas/actions/workflows/ci.yml/badge.svg">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777bb4">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-ff2d20">
  <img alt="Version" src="https://img.shields.io/badge/version-1.1.0-D9B65A">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
</p>

هر کسب‌وکار صفحهٔ رزرو اختصاصی خودش را دارد؛ ساعات کاری هر متخصص را تعیین می‌کند و موتور
زمان‌های آزاد، مدت خدمت، فاصلهٔ پاک‌سازی، مرخصی و نوبت‌های موجود را با هم حساب می‌کند. رابط
کاربری فارسی و راست‌به‌چپ است، بدون فریم‌ورک CSS و بدون مرحلهٔ build. دادهٔ هر مستأجر با یک
global scope که در نبود مستأجر **fail-closed** است جدا می‌ماند.

> **وضعیت صادقانه:** این پروژه یک نمونهٔ مهندسی‌شده و تست‌شده است، نه یک سرویس SaaS در حال
> بهره‌برداری. درگاه پرداخت، پیامک واقعی، ورود مشتری و داشبورد تحلیل در آن نیست. بخش
> [«محدودیت‌ها»](#۸-محدودیتها-و-چیزهایی-که-داخلش-نیست) دقیقاً همین مرزها را می‌گوید.

مستندات انگلیسی: [`README.md`](README.md) · امنیت: [`SECURITY.md`](SECURITY.md) ·
معماری: [`docs/architecture-fa.md`](docs/architecture-fa.md) ·
مرجع HTTP: [`docs/api-fa.md`](docs/api-fa.md)

---

## ۱. این پروژه چه مشکلی را حل می‌کند

سالن‌ها و کلینیک‌های کوچک معمولاً نوبت‌ها را در دفتر کاغذی، اکسل یا یک رشته پیام واتساپ/ایتا
نگه می‌دارند. نتیجه قابل پیش‌بینی است:

- دو مشتری برای یک ساعت با یک متخصص رزرو می‌شوند؛
- مدت خدمت و زمان پاک‌سازی تجهیزات در محاسبه نیست؛
- مشتری نمی‌داند کدام ساعت واقعاً آزاد است و باید زنگ بزند؛
- صاحب کسب‌وکار نمی‌تواند بگوید این هفته چند نوبت داشته و چقدر درآمد داشته است.

اینجا زمان‌های آزاد یک **تابع خالص از ساعات کاری، مدت خدمت، بافر، مرخصی و نوبت‌های موجود**
است — نه لیستی که دستی نگه داشته شود. همان قواعد، هم در صفحهٔ عمومی و هم در پنل مدیریت
اعمال می‌شوند.

## ۲. امکانات

**رزرو عمومی (بدون حساب کاربری)**

- انتخاب خدمت، متخصص (یا «هر متخصصی که آزاد باشد») و تاریخ؛
- دریافت زمان‌های آزاد با `fetch` و رندر دکمه‌ها، بدون بارگذاری صفحه؛
- ثبت نوبت با نام، شمارهٔ همراه ایرانی (ارقام فارسی/عربی هم پذیرفته می‌شود) و توضیح اختیاری؛
- صفحهٔ تأیید با کد پیگیری، دکمهٔ کپی کد و امکان لغو تا مهلت تعیین‌شده؛
- محدودیت نرخ روی ثبت نوبت (۱۰ در دقیقه)، دریافت زمان‌ها (۶۰ در دقیقه)، صفحهٔ تأیید
  (۶۰ در دقیقه) و لغو (۱۰ در دقیقه).

**پنل کسب‌وکار**

- داشبورد: نوبت‌های امروز، نوبت‌های هفته، درآمد ماه، شمارش وضعیت‌ها؛
- نوبت‌ها: فیلتر تاریخ و وضعیت، تغییر وضعیت فقط از مسیرهای مجاز، صفحه‌بندی روزهای شلوغ؛
- خدمات: مدت، بافر پاک‌سازی، قیمت (عدد صحیح تومان)، رنگ و اتصال به متخصص‌ها؛
- تیم: ساعات کاری هفتگی با **چند بازه در یک روز** (`09:00-13:00, 16:00-19:00`)؛
- ویرایش درجا با `<details>` بومی و صفحه‌بندی فهرست‌ها (۱۰۰ ردیف در هر صفحه).

**پلتفرم**

- فهرست کسب‌وکارها با تعداد خدمات، تیم، نوبت و کاربر؛
- مدیر پلتفرم می‌تواند برای پشتیبانی وارد پنل هر کسب‌وکار شود؛ مدیر هر کسب‌وکار فقط پنل خودش.

**فنی**

- جداسازی مستأجر با global scope که در نبود مستأجر هیچ ردیفی برنمی‌گرداند؛
- جلوگیری از رزرو همزمان با تراکنش، قفل ردیف مستأجر و متخصص و بازبینی دوباره پس از قفل؛
- ذخیرهٔ همهٔ زمان‌ها به UTC و محاسبه در منطقهٔ زمانی هر مستأجر؛
- پیامک تأیید از طریق کانال Notification لاراول با درایور لاگ محلی؛
- پول به‌صورت عدد صحیح (تومان) در همهٔ مسیرها؛ بدون اعشار و بدون float در محاسبات؛
- ۹۹ تست و ۲۸۰ assertion، Pint و CI روی PHP 8.2/8.3/8.4.

### طراحی رابط کاربری

- زبان طراحی **Liquid Glass** با پالت مشکی ممتاز + طلایی؛ دو حالت **تیره (پیش‌فرض)** و
  **روشن** با کلید تبدیل در هدر، ذخیره در `localStorage` و احترام به `prefers-color-scheme`
  وقتی کاربر انتخابی نکرده باشد (بدون پرش رنگ هنگام بارگذاری)؛
- یک سیستم توکن کامل در `public/css/app.css` — **بدون فریم‌ورک CSS و بدون مرحلهٔ build**:
  سطوح شیشه‌ای، خطوط مویی، شعاع و فاصله‌های منظم، اعداد tabular برای مبالغ و ساعت‌ها؛
- تایپوگرافی فارسی با **Vazirmatn** و فهرست جانشین مشخص؛ دلیل جانشینی این فونت به‌جای
  پیشنهاد سیستم طراحی، در `design-system/nobatyar/MASTER.md` ثبت شده است؛
- یک خانوادهٔ آیکون outline به‌صورت کامپوننت Blade
  (`resources/views/components/icon.blade.php`) — بدون آیکون رستری و بدون ایموجی؛
- دسترس‌پذیری: کنتراست متن در هر دو حالت اندازه‌گیری شده (≥ ۴٫۵:۱)، مرز کنترل‌ها با
  `--line-control` حداقل ۳:۱، هدف لمسی ۴۴ پیکسل، حلقهٔ `:focus-visible` روی همهٔ کنترل‌ها،
  خلاصهٔ خطای فرم قابل فوکوس با `role="alert"` و لینک‌دار به هر فیلد، `aria-live` روی
  زمان‌های آزاد، لینک «پرش به محتوا» و احترام کامل به `prefers-reduced-motion`؛
- جدول‌ها زیر ۷۶۰ پیکسل به کارت‌های «برچسب/مقدار» تبدیل می‌شوند (`data-label`)؛ جدول خدمات
  در عرض‌های میانی با `table-layout: fixed` و ستون ویرایش محدود، بدون اسکرول افقی می‌ماند؛
- فایل‌های مربوط: `public/css/app.css`، `public/js/app.js`،
  `resources/views/components/icon.blade.php`، `design-system/nobatyar/MASTER.md`.
  برای دیدن رابط، پروژه را اجرا کنید؛ در مخزن تصویر ساختگی قرار داده نشده است.

## ۳. پیش‌نیازها

| مورد | نسخه/توضیح |
| --- | --- |
| PHP | ۸٫۲، ۸٫۳ یا ۸٫۴ (همین سه نسخه در CI تست می‌شوند) |
| افزونه‌های PHP | `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `ctype`, `json`, `tokenizer`, `xml`, `fileinfo` |
| Composer | نسخهٔ ۲ |
| پایگاه داده | SQLite برای اجرای محلی و تست؛ MySQL 8 یا PostgreSQL 13+ برای تولید |
| Node/npm | **لازم نیست** — CSS و JS دست‌نویس‌اند و مرحلهٔ build وجود ندارد |
| سرویس بیرونی | **لازم نیست** — پیامک و ایمیل در حالت پیش‌فرض در لاگ نوشته می‌شوند |

## ۴. نصب و راه‌اندازی

از یک checkout تمیز تا صفحهٔ کارکننده:

```bash
git clone https://github.com/sirdadfar/laravel-booking-saas.git
cd laravel-booking-saas

composer install
cp .env.example .env
php artisan key:generate

# SQLite پیش‌فرض است و به سرور جداگانه نیاز ندارد.
touch database/database.sqlite

php artisan migrate --seed
php artisan serve
```

سپس `http://localhost:8000/` را باز کنید. اگر `PHP_CLI_SERVER_WORKERS` را در `.env` تنظیم
کرده‌اید، `php artisan serve` از آن استفاده می‌کند.

| نشانی | توضیح |
| --- | --- |
| `http://localhost:8000/` | صفحهٔ معرفی + فهرست کسب‌وکارهای نمونه |
| `http://localhost:8000/rose` | صفحهٔ رزرو آرایشگاه رز (بدون ورود) |
| `http://localhost:8000/rose/admin` | پنل مدیریت آرایشگاه |
| `http://localhost:8000/mehr/admin` | پنل مدیریت کلینیک مهر |
| `http://localhost:8000/platform/tenants` | فهرست کسب‌وکارها (مدیر پلتفرم) |
| `http://rose.localhost:8000/` | همان صفحهٔ رزرو از طریق زیردامنه |

حساب‌های نمایشی (رمز همه: `password`):

| نقش | ایمیل |
| --- | --- |
| مدیر آرایشگاه رز | `admin@rose.test` |
| مدیر کلینیک مهر | `admin@mehr.test` |
| مدیر پلتفرم | `admin@nobatyar.test` |

> داده‌های نمایشی فقط یک بار ساخته می‌شوند. برای ساخت دوباره:
> `php artisan migrate:fresh --seed`

### نصب روی MySQL یا PostgreSQL

SQLite برای تولید توصیه نمی‌شود. برای MySQL:

```bash
mysql -u root -p -e "CREATE DATABASE nobatyar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

سپس در `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nobatyar
DB_USERNAME=nobatyar
DB_PASSWORD=...
```

و `php artisan migrate --seed`. برای PostgreSQL همان مقادیر با `DB_CONNECTION=pgsql` و
`DB_PORT=5432`. تست‌ها همیشه روی SQLite در حافظه (`phpunit.xml`) اجرا می‌شوند و به `.env`
شما کاری ندارند.

### اگر چیزی کار نکرد

| نشانه | علت رایج و راه‌حل |
| --- | --- |
| `No application encryption key has been specified` | `php artisan key:generate` اجرا نشده است. |
| `Database file at path ... does not exist` | `touch database/database.sqlite` را اجرا کنید. |
| خطای ۵۰۰ با صفحهٔ خاکستری | `APP_DEBUG=false` است؛ برای دیدن جزئیات در محیط محلی موقتاً `true` کنید و بعد برگردانید. |
| `rose.localhost` باز نمی‌شود | برخی مرورگرها/سیستم‌ها `*.localhost` را resolve نمی‌کنند؛ از مسیر `/rose` استفاده کنید. |
| پورت ۸۰۰۰ اشغال است | `php artisan serve --port=8001` و نشانی‌ها را با همان پورت بخوانید. |
| خطای دسترسی نوشتن | `storage/` و `bootstrap/cache/` باید برای کاربر وب قابل نوشتن باشند. |

## ۵. پیکربندی

همهٔ کلیدها در `.env.example` با مقدار پیش‌فرض آمده‌اند. هیچ کلید اجباری بدون پیام خطای روشن
رها نشده است؛ تنها مقدار اجباری `APP_KEY` است که با `key:generate` ساخته می‌شود.

| کلید | پیش‌فرض | معنا |
| --- | --- | --- |
| `APP_ENV` | `local` | محیط اجرا؛ در تولید `production` |
| `APP_DEBUG` | `false` | نمایش جزئیات خطا؛ در تولید هرگز `true` نباشد |
| `APP_URL` | `http://localhost:8000` | مبنای ساخت URL |
| `APP_TIMEZONE` | `UTC` | منطقهٔ زمانی برنامه؛ زمان‌ها همیشه UTC ذخیره می‌شوند |
| `APP_LOCALE` | `fa` | زبان رابط |
| `DB_CONNECTION` | `sqlite` | `sqlite`، `mysql` یا `pgsql` |
| `DB_DATABASE` | `database/database.sqlite` | مسیر فایل SQLite یا نام دیتابیس |
| `DB_HOST` / `DB_PORT` / `DB_USERNAME` / `DB_PASSWORD` | خالی | اتصال MySQL/PostgreSQL |
| `SESSION_DRIVER` | `database` | ذخیرهٔ نشست؛ `redis` برای چند سرور |
| `SESSION_ENCRYPT` | `true` | رمزنگاری payload نشست |
| `SESSION_HTTP_ONLY` | `true` | کوکی نشست از JavaScript خوانده نشود |
| `SESSION_SAME_SITE` | `lax` | محافظت CSRF در سطح کوکی |
| `SESSION_SECURE_COOKIE` | `null` | در HTTPS روی `true` بگذارید |
| `SESSION_LIFETIME` | `120` | دقیقه |
| `CACHE_STORE` | `database` | محدودیت نرخ و کش؛ `redis` برای تولید چندسروری |
| `QUEUE_CONNECTION` | `sync` | صف؛ اعلان‌ها همگام‌اند |
| `MAIL_MAILER` | `log` | ایمیل در لاگ نوشته می‌شود؛ SMTP در تولید |
| `SMS_DRIVER` | `log` | تنها درایور موجود؛ فایل لاگ |
| `SMS_LOG_PATH` | `storage/logs/sms.log` | مسیر فایل پیامک؛ در تولید محافظت‌شده باشد |
| `TENANCY_BASE_DOMAINS` | `localhost,example.test` | دامنه‌های پایه برای تشخیص زیردامنه |
| `TENANCY_HEADER` | `X-Tenant` | هدر تشخیص مستأجر برای کلاینت‌ها |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stack` / `warning` | سطح لاگ تولید؛ در توسعه `debug` |

> `config:cache` امن است: هیچ `env()` خارج از `config/` صدا زده نمی‌شود. پس از تغییر `.env`
> در تولید، `php artisan config:cache` را دوباره اجرا کنید.

## ۶. استفاده

**۱) رزرو توسط مشتری**

1. `/rose` را باز کنید؛ خدمت، متخصص و تاریخ را انتخاب کنید.
2. زمان‌های آزاد از `GET /rose/slots` می‌آیند و فقط بازه‌هایی را نشان می‌دهند که
   «مدت خدمت + بافر» در آن جا شود و با نوبت/مرخصی هم‌پوشانی نداشته باشد.
3. یک زمان را انتخاب کنید، نام و شمارهٔ همراه را وارد کنید و فرم را بفرستید.
4. به `/rose/bookings/NB-XXXXXX` هدایت می‌شوید؛ کد پیگیری را نگه دارید.
5. تا مهلت `cancellation_window_hours` (پیش‌فرض ۶ ساعت) می‌توانید از همان صفحه لغو کنید.

**۲) مدیر کسب‌وکار**

1. `/login` وارد شوید (`admin@rose.test` / `password`).
2. داشبورد: نوبت‌های امروز، شمارش هفته، درآمد نوبت‌های انجام‌شدهٔ ماه و وضعیت‌ها.
3. «نوبت‌ها»: تاریخ و وضعیت را فیلتر کنید؛ گذارهای مجاز به‌صورت دکمه نمایش داده می‌شوند
   (`pending → confirmed → completed/no_show/cancelled`).
4. «خدمات»: مدت، بافر، قیمت، رنگ و متخصص‌ها را تعیین کنید. خدمتی که سابقهٔ نوبت دارد حذف
   نمی‌شود؛ غیرفعالش کنید.
5. «تیم»: شیفت‌های هر روز را با کاما جدا کنید (`09:00-13:00, 16:00-19:00`). خالی گذاشتن
   یعنی تعطیل.

**۳) مدیر پلتفرم**

`/platform/tenants` فهرست همهٔ کسب‌وکارها را با شمارنده‌ها نشان می‌دهد. مدیر پلتفرم می‌تواند
برای پشتیبانی وارد `/{tenant}/admin` هر کسب‌وکار شود؛ مدیر یک کسب‌وکار با درخواست به پنل
دیگری ۴۰۳ می‌گیرد.

**۴) زیردامنه**

اگر `TENANCY_BASE_DOMAINS` شامل `localhost` باشد، `rose.localhost:8000` همان صفحهٔ رزرو
`rose` است. پارامتر مسیر همیشه بر هدر `X-Tenant` و زیردامنه اولویت دارد.

## ۷. امنیت

مدل کامل و دامنهٔ گزارش در [`SECURITY.md`](SECURITY.md) آمده است. آنچه در کد اجرا می‌شود:

- **جداسازی مستأجر:** `TenantScope` در نبود مستأجر `whereRaw('1 = 0')` می‌گذارد؛ ساخت بدون
  context استثنا می‌دهد؛ `tenant_id` بعد از ساخت تغییرناپذیر است. `ResetTenantContext` در شروع
  هر درخواست context را پاک می‌کند تا در workerهای بلندعمر نشت نکند.
- **کنترل دسترسی:** هر مسیر پنل پشت `tenant` + `auth` + `tenant.admin` است؛
  `EnsureTenantAdmin` تعلق کاربر به همان مستأجر را بررسی می‌کند و `EnsurePlatformAdmin` مرز
  پلتفرم را. رکوردهای مستأجر دیگر با `findOrFail` به ۴۰۴ می‌خورند، نه ۴۰۳ با نشت وجود.
- **پیوندهای بین‌مستأجری:** `staff_ids` و `service_ids` با `Rule::exists(...)->where('tenant_id')`
  اعتبارسنجی می‌شوند؛ جدول `staff_service` ستون `tenant_id` ندارد، پس این مرز در کنترلر
  اعمال می‌شود و در تست پوشش دارد.
- **CSRF و نشست:** همهٔ فرم‌ها `@csrf` دارند (و `@method` برای PUT/DELETE)؛ ورود
  `session()->regenerate()` و خروج `invalidate()` + `regenerateToken()` می‌کند؛ کوکی‌ها
  `HttpOnly` و `SameSite=Lax` هستند و در HTTPS `Secure` می‌شوند.
- **محدودیت نرخ:** ورود ۵ تلاش در دقیقه به ازای «هش ایمیل + IP»؛ ثبت نوبت ۱۰، زمان‌های آزاد
  ۶۰، صفحهٔ تأیید ۶۰ و لغو ۱۰ در دقیقه به ازای IP. عبور از حد، پاسخ ۴۲۹ می‌گیرد.
- **سرصفحه‌های امنیتی:** `X-Content-Type-Options: nosniff`، `X-Frame-Options: DENY`،
  `Referrer-Policy: strict-origin-when-cross-origin`، `Permissions-Policy` و یک CSP در حالت
  **Report-Only** (چون رابط اسکریپت درون‌خطی تم دارد). HSTS فقط روی درخواست TLS فرستاده
  می‌شود.
- **خطا و لاگ:** `APP_DEBUG=false` در `.env.example`؛ صفحه‌های ۴۰۳/۴۰۴/۴۱۹/۴۲۹/۵۰۰ فارسی و
  بدون stack trace هستند. لاگ برنامه شمارهٔ کارت/رمز چاپ نمی‌کند. تنها استثنای مستند،
  `storage/logs/sms.log` است که برای درایور توسعه شمارهٔ گیرنده را دارد (SECURITY.md).
- **پول و همزمانی:** قیمت در لحظهٔ رزرو به‌صورت عدد صحیح اسنپ‌شات می‌شود؛ ثبت نوبت در تراکنش با
  قفل ردیف مستأجر و متخصص و بازبینی دوباره انجام می‌شود.
- **وابستگی‌ها:** `composer audit` هیچ ادوایزری گزارش نمی‌کند و در CI اجباری است.

## ۸. محدودیت‌ها و چیزهایی که داخلش نیست

برای اینکه ادعا با واقعیت بخواند:

- **درگاه پرداخت** وجود ندارد؛ بیعانه/پرداخت آنلاین پیاده نشده است.
- **پیامک واقعی** ارسال نمی‌شود؛ `SmsChannel` پیام را در `storage/logs/sms.log` می‌نویسد.
  جای‌گذاری کاوه‌نگار یا SMS.ir یک کلاس است، اما تست نشده و ادعا هم نمی‌شود.
- **ورود مشتری** و تاریخچهٔ نوبت‌های او وجود ندارد؛ مشتری فقط با کد پیگیری صفحهٔ خودش را
  می‌بیند. این کد تنها «اعتبارنامه» آن صفحه است؛ اگر کسی کد را ببیند، صفحه را می‌بیند.
- **کد پیگیری** دومین عامل (OTP) ندارد؛ محدودیت نرخ لغو و تأیید، حدس زدن را پرهزینه می‌کند
  اما جای احراز هویت واقعی را نمی‌گیرد.
- **نوبت تکرارشونده، انتظار (waitlist)، تخفیف، کد معرف، مالیات و چند‌ارزی** پیاده نشده‌اند.
- **تقویم کشیدن‌و‌رها کردن (drag & drop)** و نمای هفتگی متخصص وجود ندارد.
- **صف (queue)** برای اعلان‌ها استفاده نشده؛ ارسال همگام است. خطای اعلان رزرو را برنمی‌گرداند
  (try/catch + report)، اما کندی ارائه‌دهنده می‌تواند پاسخ را کند کند.
- **احراز هویت دو‌مرحله‌ای، تأیید ایمیل و بازیابی رمز عبور** در این نمونه نیست.
- **API توکن‌دار (Sanctum)** و webhook وجود ندارد؛ مسیرها همان مسیرهای وب با CSRF هستند.
- **CSP اجباری نیست** و فقط Report-Only است؛ سیاست سخت‌گیرانه به nonce/hash در اسکریپت
  درون‌خطی نیاز دارد که با «بدون build step» سازگار نیست.
- **لاگ ممیزی (audit log)**، بکاپ خودکار و مانیتورینگ خطا داخل پروژه نیست.
- **SQLite برای تولید کافی نیست**؛ MySQL/PostgreSQL توصیه می‌شود. تست همزمانی واقعی روی
  MySQL انجام نشده است؛ `lockForUpdate` در SQLite بی‌اثر است و نویسنده‌ها در سطح فایل سریال
  می‌شوند.
- **DST** به Carbon سپرده شده است؛ یک تست اختصاصی برای گذار ساعت تابستانی در منطقهٔ مستأجر
  وجود ندارد. ایران از ۲۰۲۲ ساعت تابستانی ندارد، اما مستأجر با منطقهٔ دیگر باید جداگانه تست شود.
- برای تولید باید `SESSION_DRIVER`، `CACHE_STORE`، `SESSION_SECURE_COOKIE=true`، SMTP و
  بکاپ دیتابیس تنظیم شوند و TLS در لبه ترمینال شود (و `TrustProxies` برای تشخیص HTTPS).

## ۹. توسعه و تست

```bash
php artisan test           # ۹۹ تست / ۲۸۰ assertion
vendor/bin/pint --test     # بررسی سبک کد
vendor/bin/pint            # اصلاح خودکار
composer audit             # وابستگی‌های آسیب‌پذیر
php artisan view:cache && php artisan view:clear
```

پوشش تست‌ها:

| فایل | موضوع |
| --- | --- |
| `AvailabilityTest` | بافر، تعطیلی، مرخصی، لغو، افق رزرو، گرانولاریتی، منطقهٔ زمانی |
| `BookingServiceTest` | ثبت، تکراری‌نبودن، اسنپ‌شات قیمت، گذار وضعیت، مهلت لغو، پیامک |
| `TenantIsolationTest` | fail-closed بودن scope، تغییرناپذیری `tenant_id`، نشتی بین مستأجرها |
| `PublicBookingHttpTest` | صفحه، JSON زمان‌ها، اعتبارسنجی، ۴۰۹ برای نوبت گرفته‌شده، XSS |
| `AdminHttpTest` | دسترسی‌ها، CRUD خدمات و تیم، گذار وضعیت، ورود نقش‌ها |
| `TenantResolutionHttpTest` | مسیر، هدر، زیردامنه، اولویت مسیر بر هدر |
| `RateLimitTest` | محدودیت نرخ ثبت نوبت |
| `CancelThrottleTest` | محدودیت نرخ لغو با کد پیگیری |
| `LoginThrottleTest` | محدودیت ورود و جدا بودن سبد هر ایمیل |
| `CrossTenantPivotTest` | رد پیوند خدمت/متخصص بین دو مستأجر و ۴۰۴ برای وضعیت نوبت دیگران |
| `SecurityHardeningTest` | سرصفحه‌های امنیتی، HSTS، عدم mass-assignment پرچم پلتفرم |
| `PublicConfirmationBadgeTest` | کلاس badge در صفحهٔ عمومی |
| `SmsChannelTest` | نوشتن پیام تأیید در لاگ پیامک |
| `PhoneTest` (واحد) | نرمال‌سازی ارقام فارسی/عربی و اعتبار شمارهٔ همراه |
| `IranianWeekTest` (واحد) | شنبه = ۰، برچسب روزها، شروع هفته |
| `BookingStatusBadgeTest` (واحد) | تطابق modifier وضعیت با قاعدهٔ CSS |

CI در `.github/workflows/ci.yml` روی PHP 8.2، 8.3 و 8.4 اجرا می‌شود: نصب وابستگی‌ها،
ساخت `APP_KEY`، `pint --test`، `composer audit`، `config:cache`/`view:cache` و
`php artisan test`.

**افزودن یک قابلیت:** مسیر را در `routes/web.php` اضافه کنید، منطق دامنه را در
`app/Services` نگه دارید، کنترلر فقط اعتبارسنجی/هماهنگی کند، و برای مسیرهای پنل
`tenant`+`auth`+`tenant.admin` را فراموش نکنید. برای هر تغییر رفتار یک تست جدید بنویسید؛
تست‌های موجود را برای سبز شدن عوض نکنید.

## ۱۰. ساختار پروژه

```
app/
├── Channels/SmsChannel.php              # درایور پیامک (نوشتن در لاگ)
├── Concerns/BelongsToTenant.php         # scope + tenant_id خودکار + immutable
├── Enums/BookingStatus.php              # وضعیت‌ها، گذارها و کلاس badge
├── Exceptions/                          # خطاهای دامنه (رزرو/اسلات/مستأجر)
├── Http/Controllers/
│   ├── Admin/                           # داشبورد، نوبت، خدمت، تیم
│   ├── Auth/LoginController.php
│   ├── Platform/TenantController.php
│   └── Public/PublicBookingController.php
├── Http/Middleware/                     # ResolveTenant, ResetTenantContext,
│                                        # EnsureTenantAdmin, EnsurePlatformAdmin, SecurityHeaders
├── Models/                              # Tenant, User, Service, Staff, StaffSchedule,
│                                        # TimeOff, Customer, Booking
├── Notifications/BookingConfirmed.php
├── Providers/AppServiceProvider.php     # کانال SMS + RateLimiter ورود
├── Scopes/TenantScope.php
├── Services/
│   ├── AvailabilityService.php          # تولید زمان‌های آزاد
│   ├── BookingService.php               # ثبت، لغو و تغییر وضعیت
│   └── TenantContext.php                # مستأجر جاری در هر درخواست
└── Support/                             # AvailableSlot, IranianWeek, Phone, PersianNumber

config/                                  # app, database, session, tenancy, sms, ...
database/migrations|factories|seeders
public/css/app.css                       # سیستم طراحی (بدون build)
public/js/app.js                         # تم، reveal، خطای فرم، کپی کد
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

جزئیات ترتیب تراکنش رزرو و مدل تهدید جداسازی در [`docs/architecture-fa.md`](docs/architecture-fa.md)
و قرارداد HTTP در [`docs/api-fa.md`](docs/api-fa.md) آمده است.

## ۱۱. مجوز و استفادهٔ تجاری

این پروژه با مجوز **MIT** منتشر شده است — فایل [`LICENSE`](LICENSE). استفادهٔ تجاری، تغییر،
توزیع و استفاده در محصول بسته مجاز است، به شرط حفظ متن مجوز و اعلامیهٔ کپی‌رایت. نرم‌افزار
«همان‌طور که هست» ارائه می‌شود و ضمانت یا پشتیبانی رسمی ندارد.

- Laravel و وابستگی‌های Composer با مجوز MIT/Apache-2.0 هستند (فایل `composer.lock` مرجع است).
- قلم **Vazirmatn** با مجوز SIL Open Font License از Google Fonts بارگذاری می‌شود و در مخزن
  توزیع نمی‌شود؛ در نبود شبکه، فهرست جانشین در CSS استفاده می‌شود.

## ۱۲. پشتیبانی

- **باگ یا درخواست قابلیت:** [GitHub Issues](https://github.com/sirdadfar/laravel-booking-saas/issues)
  با نسخهٔ PHP، مرورگر، مراحل بازتولید و خروجی `php artisan about` (بدون `.env`).
- **آسیب‌پذیری امنیتی:** از [GitHub Security Advisories](https://github.com/sirdadfar/laravel-booking-saas/security/advisories/new)
  استفاده کنید؛ جزئیات در [`SECURITY.md`](SECURITY.md). مسائل امنیتی را در Issues عمومی
  منتشر نکنید.
- این یک پروژهٔ نمونهٔ پورتفولیو است: پاسخ به‌صورت بهترین تلاش و بدون SLA انجام می‌شود.
  برای استفادهٔ تجاری، بازبینی امنیتی و تست بار مستقل توصیه می‌شود.

نویسنده: [sirdadfar](https://github.com/sirdadfar)
