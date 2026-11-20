import 'dart:ui';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';
import 'push.dart';
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
  darkMode.value = saved == null ? true : saved == 'dark'; // الافتراضي: الأسود الذهبي
  darkMode.addListener(() => _prefs.write(key: 'theme', value: darkMode.value ? 'dark' : 'light'));
  await Api.i.init();
  await Push.init();
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
          key: ValueKey(dark),
          title: 'غني أكاديمي',
          debugShowCheckedModeBanner: false,
          theme: buildTheme(dark),
          scaffoldMessengerKey: Push.messenger,
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

class _Tab {
  final IconData icon, active;
  final String label;
  final Widget page;
  const _Tab(this.icon, this.active, this.label, this.page);
}

/// التطبيق يفتح بدون حساب. شريط تبويب كبسولة زجاجية عائمة مثل iOS.
class Shell extends StatelessWidget {
  const Shell({super.key});

  List<_Tab> get tabs => [
        const _Tab(CupertinoIcons.house, CupertinoIcons.house_fill, 'الرئيسية', HomeScreen()),
        const _Tab(CupertinoIcons.play_rectangle, CupertinoIcons.play_rectangle_fill, 'كورساتي',
            AuthGate(title: 'كورساتي', icon: CupertinoIcons.play_circle, text: 'سجل دخولك حتى تشوف كورساتك وتكمل من حيث توقفت.', child: MyCoursesScreen())),
        const _Tab(CupertinoIcons.chat_bubble_2, CupertinoIcons.chat_bubble_2_fill, 'الرسائل',
            AuthGate(title: 'الرسائل', icon: CupertinoIcons.chat_bubble_2, text: 'سجل دخولك حتى تراسل الدعم أو مدرب كورسك مباشرة.', child: ChatsScreen())),
        if (!Api.storeBuild)
          const _Tab(CupertinoIcons.creditcard, CupertinoIcons.creditcard_fill, 'المحفظة',
              AuthGate(title: 'المحفظة', icon: CupertinoIcons.creditcard, text: 'سجل دخولك حتى تشحن رصيدك بقسيمة.', child: WalletScreen())),
        const _Tab(CupertinoIcons.person, CupertinoIcons.person_fill, 'حسابي', AccountScreen()),
      ];

  @override
  Widget build(BuildContext context) {
    final t = tabs;
    return ValueListenableBuilder<int>(
      valueListenable: tab,
      builder: (ctx, raw, __) {
        final i = raw.clamp(0, t.length - 1);
        if (kDesktop) {
          // الكمبيوتر: شريط جانبي ثابت + محتوى بعرض مريح
          return Scaffold(
            body: Row(children: [
              _SideBar(tabs: t, index: i),
              Expanded(
                child: ColoredBox(
                  color: C.bg,
                  child: Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 1120),
                      child: IndexedStack(index: i, children: [for (final x in t) _nav(x.page, pad: 0)]),
                    ),
                  ),
                ),
              ),
            ]),
          );
        }
        final bottom = MediaQuery.of(ctx).padding.bottom;
        return Scaffold(
          extendBody: true,
          body: Stack(children: [
            IndexedStack(index: i, children: [for (final x in t) _nav(x.page)]),
            Positioned(
              left: 16,
              right: 16,
              bottom: bottom > 0 ? bottom : 14,
              child: _TabBar(tabs: t, index: i),
            ),
          ]),
        );
      },
    );
  }

  // كل تبويب عنده Navigator خاص، ومساحة بالأسفل للشريط العائم
  Widget _nav(Widget root, {double pad = 84}) => Navigator(
        onGenerateRoute: (_) => CupertinoPageRoute(builder: (c) => Padding(padding: EdgeInsets.only(bottom: pad), child: root)),
      );
}

/// شريط الكمبيوتر الجانبي: شعار، تبويبات، تبديل الوضع
class _SideBar extends StatelessWidget {
  final List<_Tab> tabs;
  final int index;
  const _SideBar({required this.tabs, required this.index});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 252,
      decoration: BoxDecoration(
        color: C.bgAlt,
        border: BorderDirectional(end: BorderSide(color: C.dark ? const Color(0x1FFFFFFF) : const Color(0x141B1446))),
      ),
      padding: const EdgeInsets.fromLTRB(16, 26, 16, 18),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Align(alignment: AlignmentDirectional.centerStart, child: Image.asset(C.dark ? 'assets/img/logo-dark.png' : 'assets/img/logo-light.png', height: 38)),
        ),
        const SizedBox(height: 30),
        for (var k = 0; k < tabs.length; k++)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Material(
              color: k == index ? (C.dark ? C.champagne.withValues(alpha: .14) : C.mist) : Colors.transparent,
              borderRadius: BorderRadius.circular(14),
              child: InkWell(
                borderRadius: BorderRadius.circular(14),
                onTap: () => tab.value = k,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                  child: Row(children: [
                    Icon(k == index ? tabs[k].active : tabs[k].icon, size: 22, color: k == index ? C.accent : C.text2),
                    const SizedBox(width: 12),
                    Text(tabs[k].label,
                        style: TextStyle(fontSize: 15, color: k == index ? C.accent : C.text, fontWeight: k == index ? FontWeight.w600 : FontWeight.w500)),
                  ]),
                ),
              ),
            ),
          ),
        const Spacer(),
        Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => darkMode.value = !darkMode.value,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Row(children: [
                Icon(C.dark ? CupertinoIcons.sun_max : CupertinoIcons.moon, size: 20, color: C.text2),
                const SizedBox(width: 12),
                Text(C.dark ? 'الوضع النهاري' : 'الوضع الليلي', style: TextStyle(color: C.text2)),
              ]),
            ),
          ),
        ),
        const SizedBox(height: 8),
        Row(children: [
          Icon(CupertinoIcons.lock_shield_fill, size: 15, color: C.accent),
          const SizedBox(width: 6),
          Expanded(child: Text('المحتوى محمي من تسجيل الشاشة', style: TextStyle(color: C.text3, fontSize: 12))),
        ]),
      ]),
    );
  }
}

class _TabBar extends StatelessWidget {
  final List<_Tab> tabs;
  final int index;
  const _TabBar({required this.tabs, required this.index});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(40),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 24, sigmaY: 24),
        child: Container(
          height: 68,
          decoration: BoxDecoration(
            color: (C.dark ? const Color(0xFF15131D) : Colors.white).withValues(alpha: .78),
            borderRadius: BorderRadius.circular(40),
            border: Border.all(color: C.dark ? const Color(0x24FFFFFF) : const Color(0x1A1B1446)),
          ),
          padding: const EdgeInsets.all(6),
          child: LayoutBuilder(builder: (ctx, box) {
            final w = box.maxWidth / tabs.length;
            return Stack(children: [
              // الكبسولة المتزحلقة خلف التبويب الفعال
              AnimatedPositionedDirectional(
                duration: const Duration(milliseconds: 420),
                curve: Curves.easeOutBack,
                start: w * index,
                top: 0,
                bottom: 0,
                width: w,
                child: Container(
                  decoration: BoxDecoration(
                    color: C.dark ? C.champagne.withValues(alpha: .14) : C.mist,
                    borderRadius: BorderRadius.circular(34),
                  ),
                ),
              ),
              Row(children: [
                for (var k = 0; k < tabs.length; k++)
                  Expanded(
                    child: GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {
                        HapticFeedback.selectionClick();
                        tab.value = k;
                      },
                      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                        AnimatedScale(
                          scale: k == index ? 1.1 : 1,
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.easeOutBack,
                          child: Icon(k == index ? tabs[k].active : tabs[k].icon, size: 23, color: k == index ? C.accent : C.text3),
                        ),
                        const SizedBox(height: 2),
                        Text(tabs[k].label,
                            style: TextStyle(fontSize: 10.5, color: k == index ? C.accent : C.text3, fontWeight: k == index ? FontWeight.w600 : FontWeight.w400)),
                      ]),
                    ),
                  ),
              ]),
            ]);
          }),
        ),
      ),
    );
  }
}
