import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

import 'api.dart';
import 'firebase_config.dart';
import 'ui.dart';

@pragma('vm:entry-point')
Future<void> _background(RemoteMessage m) async {
  final o = firebaseOptionsFor(Platform.isIOS);
  if (o != null) await Firebase.initializeApp(options: o);
}

/// إشعارات Firebase: تشتغل تلقائيا لما تنضاف ملفات Firebase بمجلد app/firebase
class Push {
  static final messenger = GlobalKey<ScaffoldMessengerState>();
  static bool ready = false;

  static Future<void> init() async {
    final options = firebaseOptionsFor(Platform.isIOS);
    if (options == null) return;
    try {
      await Firebase.initializeApp(options: options);
      FirebaseMessaging.onBackgroundMessage(_background);
      final fm = FirebaseMessaging.instance;
      await fm.requestPermission(alert: true, badge: true, sound: true);
      await fm.setForegroundNotificationPresentationOptions(alert: true, badge: true, sound: true);
      await fm.subscribeToTopic('all');
      ready = true;

      // إشعار والتطبيق مفتوح: رسالة لطيفة بالأسفل
      FirebaseMessaging.onMessage.listen((m) {
        final n = m.notification;
        if (n == null) return;
        messenger.currentState?.showSnackBar(SnackBar(
          behavior: SnackBarBehavior.floating,
          backgroundColor: C.night.withValues(alpha: .96),
          content: Text('${n.title ?? ''}\n${n.body ?? ''}', style: const TextStyle(color: Colors.white)),
        ));
      });
      // فتح التطبيق من إشعار محادثة
      FirebaseMessaging.onMessageOpenedApp.listen((m) {
        if (m.data['type'] == 'chat') tab.value = 2;
      });

      Api.i.loggedIn.addListener(register);
      fm.onTokenRefresh.listen((_) => register());
      await register();
    } catch (_) {}
  }

  /// يربط توكن الجهاز بحساب الطالب
  static Future<void> register() async {
    if (!ready || !Api.i.loggedIn.value) return;
    try {
      if (Platform.isIOS) await FirebaseMessaging.instance.getAPNSToken();
      final t = await FirebaseMessaging.instance.getToken();
      if (t == null) return;
      Api.i.pushToken = t;
      await Api.i.req('POST', '/me/push-token', body: {'token': t, 'platform': Platform.isIOS ? 'ios' : 'android'});
    } catch (_) {}
  }
}
