import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';
import 'screens/account.dart';
import 'screens/auth.dart';
import 'screens/chat.dart';
import 'screens/home.dart';
import 'screens/learn.dart';
import 'ui.dart';

const _prefs = FlutterSecureStorage(aOptions: AndroidOptions(encryptedSharedPreferences: true));

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // منع السكرين شوت وتسجيل الشاشة مطبق بشكل أصلي:
  // Android: FLAG_SECURE بـ MainActivity — iOS: طبقة آمنة بـ AppDelegate (tool/prepare.sh)
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  final saved = await _prefs.read(key: 'theme');
  darkMode.value = saved == null
      ? WidgetsBinding.instance.platformDispatcher.platformBrightness == Brightness.dark
      : saved == 'dark';
  darkMode.addListener(() => _prefs.write(key: 'theme', value: darkMode.value ? 'dark' : 'light'));
  await Api.i.init();
  runApp(const GhaniApp());
}

class GhaniApp extends StatelessWidget {
  const GhaniApp({super.key});
  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<bool>(
      valueListenable: darkMode,
      builder: (_, dark, __) {
        SystemChrome.setSystemUIOverlayStyle(dark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark);
        return MaterialApp(
          // مفتاح جديد عند تبديل الوضع حتى كل الألوان تتحدث
          key: ValueKey(dark),
          title: 'غني أكاديمي',
          debugShowCheckedModeBanner: false,
          theme: buildTheme(dark),
          locale: const Locale('ar'),
          supportedLocales: const [Locale('ar')],
          localizationsDelegates: const [
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          home: const Shell(),
        );
      },
    );
  }
}

/// التطبيق يفتح مباشرة بدون حساب. الدخول يُطلب فقط للاشتراك والتعلم والمحفظة والرسائل.
class Shell extends StatelessWidget {
  const Shell({super.key});
  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<int>(
      valueListenable: tab,
      builder: (_, i, __) => Scaffold(
        body: IndexedStack(index: i, children: [
          _nav(const HomeScreen()),
          _nav(const AuthGate(title: 'كورساتي', icon: CupertinoIcons.play_circle, text: 'سجل دخولك حتى تشوف كورساتك وتكمل من حيث توقفت.', child: MyCoursesScreen())),
          _nav(const AuthGate(title: 'الرسائل', icon: CupertinoIcons.chat_bubble_2, text: 'سجل دخولك حتى تراسل الدعم أو مدرب كورسك مباشرة.', child: ChatsScreen())),
          _nav(const AuthGate(title: 'المحفظة', icon: CupertinoIcons.creditcard, text: 'سجل دخولك حتى تشحن رصيدك بقسيمة.', child: WalletScreen())),
          _nav(const AccountScreen()),
        ]),
        bottomNavigationBar: Container(
          decoration: BoxDecoration(border: Border(top: BorderSide(color: C.line))),
          child: CupertinoTabBar(
            currentIndex: i,
            activeColor: C.accent,
            inactiveColor: C.text3,
            backgroundColor: C.bg.withValues(alpha: .94),
            border: null,
            onTap: (v) {
              HapticFeedback.selectionClick();
              tab.value = v;
            },
            items: const [
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.house), activeIcon: Icon(CupertinoIcons.house_fill), label: 'الرئيسية'),
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.play_circle), activeIcon: Icon(CupertinoIcons.play_circle_fill), label: 'كورساتي'),
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.chat_bubble_2), activeIcon: Icon(CupertinoIcons.chat_bubble_2_fill), label: 'الرسائل'),
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.creditcard), activeIcon: Icon(CupertinoIcons.creditcard_fill), label: 'المحفظة'),
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.person), activeIcon: Icon(CupertinoIcons.person_fill), label: 'حسابي'),
            ],
          ),
        ),
      ),
    );
  }

  // كل تبويب عنده Navigator خاص حتى يبقى شريط التبويبات ظاهر
  Widget _nav(Widget root) => Navigator(onGenerateRoute: (_) => CupertinoPageRoute(builder: (_) => root));
}
