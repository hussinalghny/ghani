#!/usr/bin/env bash
# يولد مجلد windows ويطبق حماية غني أكاديمي (منع تسجيل الشاشة + كشف برامج التسجيل)
set -euo pipefail
cd "$(dirname "$0")/.."

flutter create --org iq.ghaniacademy --project-name ghani_academy --platforms windows . >/dev/null
rm -rf test

# بدون Firebase على الكمبيوتر
perl -ni -e 'print unless /^\s*firebase_(core|messaging):/' pubspec.yaml
cp tool/windows/push.dart lib/push.dart
printf '%s\n' '// نسخة الكمبيوتر: بدون Firebase' 'Object? firebaseOptionsFor(bool ios) => null;' > lib/firebase_config.dart
# مشغل الفيديو لويندوز
grep -q 'video_player_win' pubspec.yaml || perl -pi -e 's/^(\s*)video_player: (.*)$/$1video_player: $2\n$1video_player_win: ^3.3.1/' pubspec.yaml

flutter pub get

# النافذة: عنوان وحجم
MAIN=windows/runner/main.cpp
perl -pi -e 's/window\.Create\(L"[^"]*"/window.Create(L"Ghani Academy"/; s/Win32Window::Size size\(\d+, \d+\)/Win32Window::Size size(1320, 860)/' "$MAIN"

# الحماية
cp tool/windows/guard.cpp windows/runner/ghani_guard.h
FW=windows/runner/flutter_window.cpp
grep -q ghani_guard "$FW" || {
  perl -pi -e 's|(#include "flutter_window.h")|$1\n#include "ghani_guard.h"|' "$FW"
  perl -0pi -e 's|(RegisterPlugins\(flutter_controller_->engine\(\)\);)|$1\n  ghani_guard::Protect(GetHandle(), flutter_controller_->engine()->messenger());|' "$FW"
}
grep -q 'ghani_guard::Protect' "$FW" || { echo "guard not injected"; exit 1; }

# بيانات الملف التنفيذي
RC=windows/runner/Runner.rc
perl -pi -e 's/"CompanyName", "[^"]*"/"CompanyName", "Ghani Code"/; s/"FileDescription", "[^"]*"/"FileDescription", "Ghani Academy"/; s/"ProductName", "[^"]*"/"ProductName", "Ghani Academy"/; s/"LegalCopyright", "[^"]*"/"LegalCopyright", "Copyright (C) 2026 Ghani Code"/' "$RC"

# الأيقونة
python - <<'PY'
from PIL import Image
im = Image.open('assets/img/app_icon.png').convert('RGBA')
im.save('windows/runner/resources/app_icon.ico', sizes=[(16,16),(24,24),(32,32),(48,48),(64,64),(128,128),(256,256)])
PY
echo "windows ready"
