# API تطبيق غني أكاديمي — v1

- الرابط الأساسي: `https://YOUR-DOMAIN/api/v1`
- كل الطلبات ترسل `Accept: application/json`.
- المسارات المحمية ترسل `Authorization: Bearer {token}`.
- الأخطاء ترجع بالشكل: `{ "message": "نص عربي جاهز للعرض", "code": "error_code" }`، وأخطاء التحقق ترجع `422` مع `errors`.

## رموز الأخطاء المهمة

| code | HTTP | التصرف بالتطبيق |
|---|---|---|
| `device_limit` | 403 | اعرض الرسالة وزر واتساب الدعم |
| `device_revoked` / `account_disabled` | 401 / 403 | امسح التوكن وارجع لشاشة الدخول |
| `insufficient_balance` | 422 | افتح شاشة المحفظة |
| `not_enrolled` | 403 | افتح صفحة الكورس |
| `too_many_opens` | 429 | اعرض الرسالة |
| `invalid_code` / `used_code` / `expired_code` / `disabled_code` | 422 | رسالة تحت حقل القسيمة |

> التوكن الجديد يلغي القديم إذا كانت «الجلسة الواحدة» مفعلة؛ أي `401` معناه تسجيل خروج.

---

## عام (بدون تسجيل دخول)

| Method | Path | الوصف |
|---|---|---|
| GET | `/config` | إعدادات التطبيق: واتساب، إعلان، ألوان، `security.prevent_screenshots` |
| GET | `/home` | `featured`، `latest`، `categories` |
| GET | `/categories` | الأقسام |
| GET | `/courses?category=slug&q=نص&page=1` | قائمة الكورسات (paginated) |
| GET | `/courses/{slug}` | تفاصيل الكورس + الفصول والدروس + `is_enrolled` (إذا أرسلت توكن) |
| GET | `/verify/{code}` | التحقق من شهادة |

## الدخول

### `POST /auth/register`
```json
{ "name": "حسين علي", "phone": "07701234567", "city": "بغداد", "password": "12345678",
  "device_id": "android-id-or-idfv", "device_name": "Samsung A54", "platform": "android" }
```
### `POST /auth/login`
```json
{ "phone": "07701234567", "password": "12345678", "device_id": "…", "device_name": "iPhone 15", "platform": "ios" }
```
الرد:
```json
{ "token": "1|xxxx", "token_type": "Bearer", "expires_at": "2027-01-18T…", "user": { "id": 7, "name": "…", "balance": 85000, "balance_label": "85,000 د.ع" } }
```
- `device_id`: استخدم `ANDROID_ID` بأندرويد و `identifierForVendor` بـ iOS (حزمة `device_info_plus`)، واحفظه بـ `flutter_secure_storage`.
- `platform`: `android` أو `ios`.

### `POST /auth/logout`

---

## الحساب (Bearer)

| Method | Path | Body |
|---|---|---|
| GET | `/me` | — |
| PUT | `/me` | `name`, `email?`, `city?` |
| PUT | `/me/password` | `current_password`, `password` |
| GET | `/me/devices` | الأجهزة المربوطة + `max_devices` |
| GET | `/me/courses` | كورساتي مع `progress` |
| GET | `/me/certificates` | الشهادات مع رابط الطباعة |

## التعلم (Bearer)

| Method | Path | الوصف |
|---|---|---|
| POST | `/courses/{slug}/enroll` | شراء من الرصيد. يرجع `balance` الجديد |
| GET | `/courses/{slug}/curriculum` | الفصول والدروس مع `completed` و `position` |
| GET | `/lessons/{id}` | بيانات تشغيل الدرس (انظر أدناه) |
| POST | `/lessons/{id}/progress` | `{ "position": 312 }` كل 15 ثانية، و `{ "completed": true }` عند النهاية |

### `GET /lessons/{id}`
```json
{ "data": {
  "id": 15, "title": "تحليل الثغرات", "type": "video", "duration": 1340,
  "position": 312, "completed": false, "body": null,
  "playback": {
    "embed_url": "https://iframe.mediadelivery.net/embed/…?token=…&expires=…",
    "hls_url": "https://vz-xxx.b-cdn.net/bcdn_token=…&expires=…&token_path=%2F…%2F/…/playlist.m3u8",
    "expires_in": 7200,
    "watermark": "حسين علي · 07701234567"
  },
  "attachment": { "name": "slides.pdf", "url": "https://…/file/15?u=7&expires=…&signature=…" }
} }
```
**بالتطبيق:**
- شغل `hls_url` بحزمة `video_player` أو `better_player_plus`. إذا كان `null` استخدم `embed_url` داخل `WebView`.
- ارسم `watermark` فوق الفيديو بـ `Stack` وحركه كل 4 ثواني، **وأيضا بوضع ملء الشاشة**.
- أندرويد: `FLAG_SECURE` (حزمة `no_screenshot` أو `flutter_windowmanager_plus`).
- iOS: راقب `UIScreen.capturedDidChange` وأخف الفيديو أثناء تسجيل الشاشة.
- الرابط ينتهي بعد `expires_in` ثانية؛ اطلب `/lessons/{id}` من جديد عند فتح الدرس.
- `attachment.url` صالح 10 دقائق.

## المحفظة (Bearer)

| Method | Path | الوصف |
|---|---|---|
| GET | `/wallet?page=1` | `balance` + `transactions` |
| POST | `/wallet/redeem` | `{ "code": "GA7K-29XQ-MP4D" }` |

رد الشحن:
```json
{ "type": "balance", "message": "تمت إضافة 25,000 د.ع إلى رصيدك.", "course": null, "balance": 110000 }
```
إذا كانت قسيمة كورس: `type = "course"` و `course = { id, slug }`؛ افتح الكورس مباشرة.

---

## حدود الاستخدام
- الدخول والتسجيل: 6 بالدقيقة لكل IP.
- شحن القسائم: 5 بالدقيقة و30 بالساعة.
- عام: 120 طلب بالدقيقة.
- فتح الدروس: حسب إعداد اللوحة (افتراضيا 40 بالساعة).
