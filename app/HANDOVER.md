# تسليم تطبيق «غني أكاديمي» للنشر على App Store و Google Play

دليل للمبرمج المسؤول عن رفع التطبيق. كل الخطوات مرتبة، والنصوص الجاهزة للمتاجر بالأسفل.

---

## 1) معلومات أساسية

| البند | القيمة |
|---|---|
| اسم التطبيق | غني أكاديمي (Ghani Academy) |
| الشركة / الناشر | شركة غني كود لتكنولوجيا المعلومات والاتصالات |
| Android package | `iq.ghaniacademy.ghani_academy` |
| iOS Bundle ID | `iq.ghaniacademy.ghaniAcademy` |
| الإطار | Flutter (stable) — Dart ≥ 3.4 |
| الحد الأدنى | Android 7 (API 24) · iOS 15 |
| الـ API | `https://ghaniacademy.online/api/v1` (توثيقه في `platform/API.md`) |
| المستودع | https://github.com/hussinalghny/ghani (المجلد `app/`) |
| الخصوصية | https://ghaniacademy.online/privacy |
| الشروط | https://ghaniacademy.online/terms |
| الدعم | https://ghaniacademy.online · Info@ghanicode.com · واتساب 07766637444 |

> مجلدات `android/` و `ios/` تتولد تلقائيا بالسكربت `tool/prepare.sh` (لا تعدلها يدويا قبل تشغيله).

---

## 2) قواعد مهمة جدا قبل البناء (سبب رفض شائع)

- **نسخة المتاجر لازم تنبني بـ `STORE_BUILD=true`.** هذا يخفي الأسعار والشراء والمحفظة والقسائم من التطبيق، لأن Apple (3.1.1) و Google (Payments policy) يمنعون بيع محتوى رقمي أو فتحه بأكواد خارج نظام الدفع مالهم.
- **لا تضيف أي زر أو رابط أو عبارة توجه للشراء خارج التطبيق** (واتساب للشراء، رابط الموقع للاشتراك...). زر واتساب للدعم فقط مسموح، وموجود.
- التطبيق يمنع تصوير الشاشة (Android: `FLAG_SECURE` — iOS: طبقة آمنة). هذا طبيعي، اذكره بملاحظات المراجعة لأن المراجع ما يكدر يصور.
- حذف الحساب موجود داخل التطبيق: حسابي ← «حذف الحساب نهائيا» (مطلب Apple 5.1.1(v) و Google).

---

## 3) Firebase (الإشعارات)

1. أنشئ مشروع Firebase وأضف تطبيقين بالمعرفات أعلاه.
2. ضع `google-services.json` و `GoogleService-Info.plist` في `app/firebase/`.
3. iOS: ارفع مفتاح APNs (`.p8`) في Firebase ← Project settings ← Cloud Messaging.
4. `tool/prepare.sh` يولد `lib/firebase_config.dart` تلقائيا، ويضيف صلاحية Push و Background Modes للآيفون.
5. للإرسال من المنصة: لوحة التحكم ← الإشعارات ← رفع ملف Service Account JSON.

بدون الملفات التطبيق يشتغل طبيعي بدون إشعارات.

---

## 4) Android — Google Play

### أ. مفتاح الرفع (مرة وحدة، واحفظه بمكان آمن)
```bash
keytool -genkey -v -keystore ~/ghani-upload.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```
> لا تستخدم `tool/debug.keystore` الموجود بالمستودع للمتجر، لأنه عام.

### ب. التحضير وربط التوقيع
```bash
cd app
bash tool/prepare.sh
```
أنشئ `app/android/key.properties`:
```properties
storePassword=****
keyPassword=****
keyAlias=upload
storeFile=/Users/you/ghani-upload.jks
```
في `app/android/app/build.gradle.kts` أضف فوق `android {`:
```kotlin
import java.util.Properties
val keystoreProperties = Properties().apply {
    val f = rootProject.file("key.properties"); if (f.exists()) load(f.inputStream())
}
```
وداخل `android { ... }`:
```kotlin
signingConfigs {
    create("release") {
        keyAlias = keystoreProperties["keyAlias"] as String
        keyPassword = keystoreProperties["keyPassword"] as String
        storeFile = file(keystoreProperties["storeFile"] as String)
        storePassword = keystoreProperties["storePassword"] as String
    }
}
buildTypes { release { signingConfig = signingConfigs.getByName("release") } }
```

### ج. البناء
```bash
flutter build appbundle --release \
  --build-number=1 --build-name=1.0.0 \
  --dart-define=API_BASE=https://ghaniacademy.online \
  --dart-define=STORE_BUILD=true --dart-define=BUILD=1
```
الملف: `build/app/outputs/bundle/release/app-release.aab` → ارفعه في Play Console (فعّل Play App Signing).

### د. Play Console
- **Data safety:** انظر القسم 7.
- **Content rating:** تعليمي، بدون محتوى حساس → Everyone / 3+.
- **Target audience:** 13+ (المنصة غير موجهة للأطفال).
- **App access:** أعط حساب المراجعة (القسم 6).
- **Ads:** لا يحتوي إعلانات.

---

## 5) iOS — App Store (يحتاج Mac + Xcode + حساب Apple Developer)

```bash
cd app
bash tool/prepare.sh
open ios/Runner.xcworkspace
```
في Xcode ← Runner ← Signing & Capabilities:
- Team: حساب الشركة · Bundle Identifier: `iq.ghaniacademy.ghaniAcademy`
- إذا موجود Firebase: تأكد من **Push Notifications** و **Background Modes → Remote notifications**.

البناء والرفع:
```bash
flutter build ipa --release \
  --build-number=1 --build-name=1.0.0 \
  --dart-define=API_BASE=https://ghaniacademy.online \
  --dart-define=STORE_BUILD=true --dart-define=BUILD=1
```
ارفع `build/ios/ipa/*.ipa` بـ **Transporter** أو من Xcode ← Organizer.

في App Store Connect:
- **Category:** Education · **Age rating:** 4+
- **Encryption:** مضاف تلقائيا `ITSAppUsesNonExemptEncryption = NO`.
- **App Privacy:** انظر القسم 7.
- **Sign-in required:** نعم، أعط حساب المراجعة.

---

## 6) حساب المراجعة (Apple و Google يطلبونه)

صاحب المنصة يسوي من لوحة التحكم ← الطلاب ← حساب جديد:
- رقم: `07700000001` · كلمة مرور: (يحددها) · نوع: طالب
- يسجله بكورس واحد على الأقل (زر «تسجيل مجاني» بصفحة الطالب)، ويرفع للكورس فيديو تجريبي.
- قبل كل مراجعة: «تصفير الأجهزة» لهذا الحساب، لأن الحساب مربوط بجهازين كحد أعلى والمراجعين يستخدمون أكثر من جهاز.

### نص ملاحظات المراجعة (App Review Notes)
```
Ghani Academy is an educational video app for students of ghaniacademy.online.
Users access courses they already own on their account (reader-style access).
The app does not sell anything and has no purchase flows, prices, or external purchase links.

Demo account:
Phone: 07700000001
Password: ********
The demo account is enrolled in a sample course so you can watch lessons.

Notes:
- Screenshots/screen recording are intentionally blocked to protect paid course videos (content shows black).
- Each video shows a moving watermark with the student's phone number for anti-piracy.
- Account deletion: Account tab → "حذف الحساب نهائيا".
- The app is in Arabic (RTL).
```

---

## 7) الخصوصية (Data safety / App Privacy)

| البيانات | تُجمع؟ | الغرض | مرتبطة بالمستخدم | للتتبع/الإعلانات |
|---|---|---|---|---|
| الاسم | نعم | وظائف التطبيق، إدارة الحساب | نعم | لا |
| رقم الهاتف | نعم | تسجيل الدخول، الحساب | نعم | لا |
| البريد الإلكتروني | اختياري | الحساب | نعم | لا |
| معرف الجهاز | نعم | الأمان ومنع مشاركة الحساب | نعم | لا |
| رسائل المستخدم (المحادثات) | نعم | الدعم الفني | نعم | لا |
| تفاعل التطبيق (التقدم بالدروس) | نعم | وظائف التطبيق | نعم | لا |
| رمز الإشعارات | نعم | الإشعارات | نعم | لا |

- البيانات مشفرة أثناء النقل (HTTPS): نعم.
- المستخدم يقدر يطلب حذف بياناته: نعم (داخل التطبيق والموقع).
- لا مشاركة مع أطراف ثالثة لأغراض إعلانية. لا تتبع (No tracking / ATT غير مطلوب).

---

## 8) نصوص المتجر

**الاسم:** غني أكاديمي
**العنوان الفرعي (iOS، 30 حرف):** كورسات تقنية عملية بشهادة
**الوصف القصير (Google، 80 حرف):** كورسات الأمن السيبراني والبرمجة بمدربين ممارسين. تعلم. طبق. اشتغل.

**الوصف الكامل:**
```
غني أكاديمي منصة تعليمية تقنية تابعة لشركة غني كود. تعلم الأمن السيبراني وبرمجة المواقع والتطبيقات والذكاء الاصطناعي بكورسات عملية يقدمها مدربون ممارسون.

• كورسات قصيرة وواضحة: 12 جلسة خلال 4 أسابيع
• مشروع حقيقي بكل كورس تعرضه لصاحب العمل
• شهادة إتمام برقم تحقق عام
• تابع تقدمك وكمل من حيث توقفت
• راسل المدرب أو الدعم مباشرة داخل التطبيق
• وضع ليلي فاخر ووضع نهاري
• محتوى محمي بعلامة مائية ومنع تصوير الشاشة

سجل دخولك بحسابك في منصة غني أكاديمي وابدأ التعلم.
```
**الكلمات المفتاحية (iOS):** أمن سيبراني,برمجة,كورسات,تعليم,مواقع,تطبيقات,ذكاء اصطناعي,شهادة,غني

---

## 9) الصور المطلوبة

| المتجر | المطلوب | الموجود بالحزمة |
|---|---|---|
| Apple | أيقونة 1024×1024 | `store-assets/icon-1024.png` |
| Apple | لقطات iPhone 6.9" (1320×2868) — 3 إلى 10 | تُصور من محاكي iPhone 16 Pro Max |
| Google | أيقونة 512×512 | `store-assets/icon-512.png` |
| Google | Feature graphic 1024×500 | `store-assets/feature-graphic-1024x500.png` |
| Google | لقطات هاتف 2 إلى 8 | تُصور من المحاكي |

> التصوير ممنوع داخل التطبيق حتى على المحاكي. لأخذ اللقطات: بعد `tool/prepare.sh` علّق سطر `window.setFlags(...FLAG_SECURE...)` في `MainActivity.kt` وسطر `ScreenGuard.start()` في `AppDelegate.swift`، صوّر، وبعدها أعد تشغيل `tool/prepare.sh` (يرجع الحماية). **لا ترفع نسخة اللقطات للمتجر.**

---

## 10) قائمة الفحص قبل الإرسال

- [ ] البناء بـ `STORE_BUILD=true` و `API_BASE=https://ghaniacademy.online`
- [ ] لا أسعار ولا أزرار شراء ولا قسائم ظاهرة
- [ ] حساب المراجعة يشتغل ومسجل بكورس فيه فيديو، وأجهزته مصفرة
- [ ] روابط الخصوصية والشروط تفتح
- [ ] حذف الحساب يشتغل
- [ ] (اختياري) ملفات Firebase مضافة ومفتاح APNs مرفوع
- [ ] رقم الإصدار يزيد بكل رفع (`--build-number`)
