import 'package:flutter/material.dart';

/// نسخة الكمبيوتر: بدون Firebase (الإشعارات للموبايل فقط)
class Push {
  static final messenger = GlobalKey<ScaffoldMessengerState>();
  static bool ready = false;
  static Future<void> init() async {}
  static Future<void> register() async {}
}
