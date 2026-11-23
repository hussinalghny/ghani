#!/usr/bin/env bash
# ينزل خط ثمانية من الرابط الرسمي ويحطه بالتطبيق وقت البناء (ما ينرفع للمستودع)
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p assets/fonts /tmp/thm
curl -fsSL --retry 3 -o /tmp/thm/f.zip https://public-assets.thmanyah.com/font/Thmanyah-Font-Family.zip
unzip -qo /tmp/thm/f.zip -d /tmp/thm/x
find /tmp/thm/x -path '*__MACOSX*' -prune -o -name '*.otf' -print | while read -r f; do cp "$f" assets/fonts/; done
for f in thmanyahsans-Regular thmanyahsans-Medium thmanyahsans-Bold thmanyahsans-Black thmanyahserifdisplay-Medium thmanyahserifdisplay-Bold thmanyahserifdisplay-Black; do
  [ -f "assets/fonts/$f.otf" ] || { echo "missing $f.otf"; ls assets/fonts; exit 1; }
done
echo "thmanyah fonts ready"
