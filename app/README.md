# تطبيق غني أكاديمي (Flutter)

نفس تصميم المنصة، ويشتغل على API المنصة (`/api/v1`).

## الحماية
- **منع السكرين شوت وتسجيل الشاشة بكل التطبيق:**
  - أندرويد: `FLAG_SECURE` على مستوى النظام، فالصورة والتسجيل يطلعن أسود.
  - iOS: طبقة آمنة بـ AppDelegate تخلي المحتوى يطلع أسود بالسكرين شوت وتسجيل الشاشة.
- **علامة مائية:** نص يتحرك كل 4 ثواني فوق الفيديو، بيه اسم المشترك ورقمه، ويبقى ظاهر حتى بملء الشاشة.
- ملء الشاشة من التطبيق نفسه وليس من مشغل Bunny، حتى ما تختفي العلامة.
- المشغل ما يسمح بالخروج لأي رابط ثاني.
- الحساب مربوط بالجهاز (Android ID / identifierForVendor).

## البناء (APK + IPA) بدون Mac
1. ارفع هذا المجلد كمستودع على GitHub (حسابك).
2. (اختياري) من **Settings → Secrets and variables → Actions → Variables** أضف `API_BASE` = دومين المنصة.
3. من تبويب **Actions** شغل **Build Ghani Academy** (أو أي push على main).
4. بعد حوالي 15 دقيقة نزل الملفين من **Artifacts**: `ghani-academy-apk` و `ghani-academy-ipa`.

### ملاحظات
- الـ APK موقع بمفتاح debug: يتنصب مباشرة على أي أندرويد. للنشر على Google Play لازم keystore خاص.
- الـ IPA **غير موقع**: يتنصب عبر ESign أو Sideloadly أو AltStore. للنشر على App Store لازم حساب Apple Developer (99$ بالسنة) وتوقيع.

## التشغيل محليا
```bash
bash tool/prepare.sh
flutter run --dart-define=API_BASE=https://academy.ghanicode.com
```
