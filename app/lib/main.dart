import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'api.dart';
import 'screens/account.dart';
import 'screens/auth.dart';
import 'screens/home.dart';
import 'screens/learn.dart';
import 'ui.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // منع السكرين شوت وتسجيل الشاشة مطبق بشكل أصلي:
  // Android: FLAG_SECURE بـ MainActivity — iOS: طبقة آمنة بـ AppDelegate (tool/prepare.sh)
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  await Api.i.init();
  runApp(const GhaniApp());
}

class GhaniApp extends StatelessWidget {
  const GhaniApp({super.key});
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'غني أكاديمي',
      debugShowCheckedModeBanner: false,
      theme: buildTheme(),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: ValueListenableBuilder<bool>(
        valueListenable: Api.i.loggedIn,
        builder: (_, inApp, __) => inApp ? const Shell() : const LoginScreen(),
      ),
    );
  }
}

class Shell extends StatelessWidget {
  const Shell({super.key});
  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<int>(
      valueListenable: tab,
      builder: (_, i, __) => Scaffold(
        body: IndexedStack(index: i, children: [
          _nav(const HomeScreen()),
          _nav(const MyCoursesScreen()),
          _nav(const WalletScreen()),
          _nav(const AccountScreen()),
        ]),
        bottomNavigationBar: Container(
          decoration: const BoxDecoration(border: Border(top: BorderSide(color: C.line))),
          child: CupertinoTabBar(
            currentIndex: i,
            activeColor: C.violet,
            inactiveColor: C.text3,
            backgroundColor: Colors.white.withValues(alpha: .94),
            border: null,
            onTap: (v) => tab.value = v,
            items: const [
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.house), activeIcon: Icon(CupertinoIcons.house_fill), label: 'الرئيسية'),
              BottomNavigationBarItem(icon: Icon(CupertinoIcons.play_circle), activeIcon: Icon(CupertinoIcons.play_circle_fill), label: 'كورساتي'),
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
