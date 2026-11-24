#!/usr/bin/env bash
# يولد مجلدات android و ios ويطبق إعدادات غني أكاديمي (اسم، صلاحيات، منع السكرين شوت، أيقونة)
set -euo pipefail
cd "$(dirname "$0")/.."

flutter create --org iq.ghaniacademy --project-name ghani_academy --platforms android,ios . >/dev/null
rm -rf test
flutter pub get
python3 tool/firebase_config.py

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

# Firebase يحتاج Android 7 (API 24) كحد أدنى
for f in android/app/build.gradle.kts android/app/build.gradle; do
  [ -f "$f" ] && perl -pi -e 's/minSdk\s*=\s*flutter\.minSdkVersion/minSdk = 24/; s/minSdkVersion\s+flutter\.minSdkVersion/minSdkVersion 24/' "$f"
done

# ---------- iOS ----------
PLIST=ios/Runner/Info.plist
if command -v /usr/libexec/PlistBuddy >/dev/null; then
  /usr/libexec/PlistBuddy -c "Set :CFBundleDisplayName غني أكاديمي" "$PLIST" || \
  /usr/libexec/PlistBuddy -c "Add :CFBundleDisplayName string غني أكاديمي" "$PLIST"
  /usr/libexec/PlistBuddy -c "Delete :UISupportedInterfaceOrientations~ipad" "$PLIST" 2>/dev/null || true
  # متطلبات App Store: بدون تشفير خاص (إعفاء التصدير)
  /usr/libexec/PlistBuddy -c "Add :ITSAppUsesNonExemptEncryption bool false" "$PLIST" 2>/dev/null || true
  # الإشعارات: وضع الخلفية + صلاحية Push عند وجود ملف Firebase
  if [ -f firebase/GoogleService-Info.plist ]; then
    /usr/libexec/PlistBuddy -c "Add :UIBackgroundModes array" "$PLIST" 2>/dev/null || true
    /usr/libexec/PlistBuddy -c "Add :UIBackgroundModes:0 string remote-notification" "$PLIST" 2>/dev/null || true
    cat > ios/Runner/Runner.entitlements <<'ENT'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict><key>aps-environment</key><string>production</string></dict></plist>
ENT
    grep -q "CODE_SIGN_ENTITLEMENTS" ios/Runner.xcodeproj/project.pbxproj || \
      perl -pi -e 's/(\s*)(PRODUCT_BUNDLE_IDENTIFIER = iq\.ghaniacademy\.ghaniAcademy;)/$1CODE_SIGN_ENTITLEMENTS = Runner\/Runner.entitlements;$1$2/' ios/Runner.xcodeproj/project.pbxproj
  fi
fi
# Firebase iOS يحتاج iOS 15
[ -f ios/Podfile ] || cp tool/Podfile ios/Podfile
[ -f ios/Podfile ] && perl -pi -e "s/^#?\s*platform :ios, '[0-9.]+'/platform :ios, '15.0'/" ios/Podfile
[ -f ios/Runner.xcodeproj/project.pbxproj ] && perl -pi -e 's/IPHONEOS_DEPLOYMENT_TARGET = 1[0-4]\.0;/IPHONEOS_DEPLOYMENT_TARGET = 15.0;/g' ios/Runner.xcodeproj/project.pbxproj

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

# ---------- شاشة التحميل الأصلية بالشعار ----------
RES=android/app/src/main/res
mkdir -p "$RES/drawable-xxxhdpi" "$RES/values-v31"
cp tool/splash/splash_logo.png "$RES/drawable-xxxhdpi/splash_logo.png"
for d in drawable drawable-v21; do
  [ -d "$RES/$d" ] && cat > "$RES/$d/launch_background.xml" <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<layer-list xmlns:android="http://schemas.android.com/apk/res/android">
    <item android:drawable="@android:color/white" />
    <item><bitmap android:gravity="center" android:src="@drawable/splash_logo" /></item>
</layer-list>
XML
done
cat > "$RES/values-v31/styles.xml" <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <style name="LaunchTheme" parent="@android:style/Theme.Light.NoTitleBar">
        <item name="android:windowSplashScreenBackground">#FFFFFF</item>
        <item name="android:windowSplashScreenAnimatedIcon">@mipmap/ic_launcher</item>
        <item name="android:windowBackground">@drawable/launch_background</item>
    </style>
    <style name="NormalTheme" parent="@android:style/Theme.Light.NoTitleBar">
        <item name="android:windowBackground">@android:color/white</item>
        <item name="android:navigationBarColor">@android:color/white</item>
    </style>
</resources>
XML
LI=ios/Runner/Assets.xcassets/LaunchImage.imageset
if [ -d "$LI" ]; then
  cp tool/splash/LaunchImage.png tool/splash/LaunchImage@2x.png tool/splash/LaunchImage@3x.png "$LI/"
fi
SB=ios/Runner/Base.lproj/LaunchScreen.storyboard
# خلفية بيضاء ثابتة حتى بالوضع الليلي للنظام
[ -f "$SB" ] && perl -pi -e 's#<color key="backgroundColor"[^>]*/>#<color key="backgroundColor" red="1" green="1" blue="1" alpha="1" colorSpace="custom" customColorSpace="sRGB"/>#g' "$SB"

# ---------- الأيقونة ----------
dart run flutter_launcher_icons
echo "prepared"
