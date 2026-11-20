import 'dart:io';

import 'package:flutter/services.dart';

/// حماية نسخة الكمبيوتر: النافذة مستثناة من التصوير بالنظام (أسود بأي تسجيل)،
/// وفوگاها نكشف برامج التسجيل الشائعة ونوقف الفيديو.
class DesktopGuard {
  static bool get isDesktop => Platform.isWindows || Platform.isMacOS || Platform.isLinux;
  static const _ch = MethodChannel('ghani/guard');

  /// اسم برنامج التسجيل إذا شغال، أو null
  static Future<String?> recorder() async {
    if (!Platform.isWindows) return null;
    try {
      final r = await _ch.invokeMethod<String>('recorder');
      return (r == null || r.isEmpty) ? null : r;
    } catch (_) {
      return null;
    }
  }
}
