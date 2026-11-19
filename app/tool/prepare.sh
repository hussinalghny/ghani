#!/usr/bin/env bash
# يولد مجلدات android و ios ويطبق إعدادات غني أكاديمي (اسم، صلاحيات، منع السكرين شوت، أيقونة)
set -euo pipefail
cd "$(dirname "$0")/.."

flutter create --org com.ghanicode --project-name ghani_academy --platforms android,ios . >/dev/null
rm -rf test
flutter pub get

# ---------- Android ----------
MANIFEST=android/app/src/main/AndroidManifest.xml
perl -pi -e 's/android:label="[^"]*"/android:label="غني أكاديمي"/' "$MANIFEST"
grep -q 'android.permission.INTERNET' "$MANIFEST" || \
  perl -pi -e 's|(<manifest[^>]*>)|$1\n    <uses-permission android:name="android.permission.INTERNET"/>|' "$MANIFEST"

# منع السكرين شوت وتسجيل الشاشة على مستوى النظام (FLAG_SECURE) لكل التطبيق
KT=$(find android/app/src/main -name MainActivity.kt | head -1)
PKG=$(head -1 "$KT")
cat > "$KT" <<KOTLIN
$PKG

import android.os.Bundle
import android.view.WindowManager
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        window.setFlags(WindowManager.LayoutParams.FLAG_SECURE, WindowManager.LayoutParams.FLAG_SECURE)
        super.onCreate(savedInstanceState)
    }
}
KOTLIN

# ---------- iOS ----------
PLIST=ios/Runner/Info.plist
if command -v /usr/libexec/PlistBuddy >/dev/null; then
  /usr/libexec/PlistBuddy -c "Set :CFBundleDisplayName غني أكاديمي" "$PLIST" || \
  /usr/libexec/PlistBuddy -c "Add :CFBundleDisplayName string غني أكاديمي" "$PLIST"
  /usr/libexec/PlistBuddy -c "Delete :UISupportedInterfaceOrientations~ipad" "$PLIST" 2>/dev/null || true
fi

# ---------- الأيقونة ----------
dart run flutter_launcher_icons
echo "prepared"
