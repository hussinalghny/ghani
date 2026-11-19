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

# منع السكرين شوت وتسجيل الشاشة على iOS: نخلي نافذة التطبيق داخل طبقة حقل سري
APPDEL=ios/Runner/AppDelegate.swift
if ! grep -q ScreenGuard "$APPDEL"; then
  perl -0pi -e 's/(\n(\s*)return super\.application\(application, didFinishLaunchingWithOptions)/\n$2ScreenGuard.start()$1/' "$APPDEL"
  cat >> "$APPDEL" <<'SWIFT'

// غني أكاديمي: المحتوى يطلع أسود بالسكرين شوت وتسجيل الشاشة
final class ScreenGuard {
  private static var secured = Set<ObjectIdentifier>()

  static func start() {
    let c = NotificationCenter.default
    c.addObserver(forName: UIApplication.didBecomeActiveNotification, object: nil, queue: .main) { _ in apply() }
    c.addObserver(forName: UIWindow.didBecomeKeyNotification, object: nil, queue: .main) { _ in apply() }
    DispatchQueue.main.async { apply() }
  }

  private static func apply() {
    for scene in UIApplication.shared.connectedScenes {
      guard let ws = scene as? UIWindowScene else { continue }
      for w in ws.windows where !secured.contains(ObjectIdentifier(w)) {
        secure(w)
        secured.insert(ObjectIdentifier(w))
      }
    }
  }

  private static func secure(_ window: UIWindow) {
    let field = UITextField()
    field.isSecureTextEntry = true
    field.isUserInteractionEnabled = false
    window.addSubview(field)
    window.layer.superlayer?.addSublayer(field.layer)
    if #available(iOS 17.0, *) {
      field.layer.sublayers?.last?.addSublayer(window.layer)
    } else {
      field.layer.sublayers?.first?.addSublayer(window.layer)
    }
  }
}
SWIFT
  grep -q "ScreenGuard.start()" "$APPDEL" || echo "WARNING: ScreenGuard.start() not inserted"
fi

# ---------- الأيقونة ----------
dart run flutter_launcher_icons
echo "prepared"
