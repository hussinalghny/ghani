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
import 'screens/home.dart';
import 'screens/learn.dart';
import 'screens/welcome.dart';
import 'ui.dart';

const _prefs = FlutterSecureStorage(aOptions: AndroidOptions(encryptedSharedPreferences: true));

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // منع السكرين شوت وتسجيل الشاشة مطبق بشكل أصلي:
  // Android: FLAG_SECURE بـ MainActivity — iOS: طبقة آمنة بـ AppDelegate (tool/prepare.sh)
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  final saved = await _prefs.read(key: 'theme');
  darkMode.value = saved == 'dark'; // الافتراضي: النهاري الأبيض
  darkMode.addListener(() => _prefs.write(key: 'theme', value: darkMode.value ? 'dark' : 'light'));
  welcomed.value = await _prefs.read(key: 'welcomed') == '1';
  runApp(const GhaniApp());
  // التطبيق يطلع فورا بشاشة الشعار، والتهيئة تكمل وراها
  await Api.i.init();
  ready.value = true;
  await Push.init();
}

/// جاهزية التطبيق (بعد قراءة الجلسة) وهل شاف شاشة الترحيب
final ready = ValueNotifier<bool>(false);
final welcomed = ValueNotifier<bool>(true);

class GhaniApp extends StatelessWidget {
  const GhaniApp({super.key});
  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<bool>(
      valueListenable: darkMode,
      builder: (_, dark, __) {
        // لون موحد: شريط النظام السفلي (أندرويد) والعلوي بنفس لون التطبيق
        SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
        SystemChrome.setSystemUIOverlayStyle((dark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark).copyWith(
          statusBarColor: Colors.transparent,
          systemNavigationBarColor: dark ? const Color(0xFF0E0E12) : Colors.white,
          systemNavigationBarDividerColor: Colors.transparent,
          systemNavigationBarIconBrightness: dark ? Brightness.light : Brightness.dark,
          systemNavigationBarContrastEnforced: false,
        ));
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
          home: AnimatedBuilder(
            animation: Listenable.merge([ready, welcomed]),
            builder: (_, __) => AnimatedSwitcher(
              duration: const Duration(milliseconds: 280),
              child: !ready.value
                  ? const SplashScreen()
                  : !welcomed.value
                      ? WelcomeScreen(onDone: () {
                          welcomed.value = true;
                          _prefs.write(key: 'welcomed', value: '1');
                        })
                      : const Shell(),
            ),
          ),

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
        const _Tab(CupertinoIcons.search, CupertinoIcons.search, 'بحث', CoursesScreen(tabRoot: true)),
        const _Tab(CupertinoIcons.play_rectangle, CupertinoIcons.play_rectangle_fill, 'مساري',
            AuthGate(title: 'مساري', icon: CupertinoIcons.play_circle, text: 'سجل دخولك حتى تشوف كورساتك وتكمل من حيث توقفت.', child: MyCoursesScreen())),
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
                      child: IndexedStack(index: i, children: [for (var n = 0; n < t.length; n++) _lazy(n, i, () => _nav(t[n].page, pad: 0))]),
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
            IndexedStack(index: i, children: [for (var n = 0; n < t.length; n++) _lazy(n, i, () => _nav(t[n].page))]),
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

  // التبويب ما ينبني إلا أول مرة ينفتح (حتى ما تنطلب كل الشاشات من السيرفر وقت فتح التطبيق)
  static final _seen = <int>{};
  static final _built = <int, Widget>{};
  Widget _lazy(int n, int current, Widget Function() make) {
    if (n == current) _seen.add(n);
    if (!_seen.contains(n)) return const SizedBox.shrink();
    return _built[n] ??= make();
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
              color: k == index ? C.mist : Colors.transparent,
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

/// شريط التبويبات: زجاج عائم بأسلوب آبل (ضبابية خفيفة واحدة فقط حتى ما يثقل)
class _TabBar extends StatelessWidget {
  final List<_Tab> tabs;
  final int index;
  const _TabBar({required this.tabs, required this.index});

  @override
  Widget build(BuildContext context) {
    final dark = C.dark;
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(34),
        boxShadow: [BoxShadow(color: (dark ? Colors.black : const Color(0xFF1C1446)).withValues(alpha: dark ? .45 : .16), blurRadius: 30, offset: const Offset(0, 10))],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(34),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 22, sigmaY: 22),
          child: Container(
            height: 66,
            padding: const EdgeInsets.all(5),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(34),
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: dark
                    ? [const Color(0xFF2A2A34).withValues(alpha: .72), const Color(0xFF1E1E26).withValues(alpha: .62)]
                    : [Colors.white.withValues(alpha: .78), Colors.white.withValues(alpha: .58)],
              ),
              border: Border.all(color: dark ? Colors.white.withValues(alpha: .12) : Colors.white.withValues(alpha: .8)),
            ),
            child: Row(children: [
              for (var k = 0; k < tabs.length; k++)
                Expanded(
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () {
                      HapticFeedback.selectionClick();
                      tab.value = k;
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 220),
                      curve: Curves.easeOut,
                      decoration: BoxDecoration(
                        color: k == index ? C.violet.withValues(alpha: dark ? .28 : .11) : Colors.transparent,
                        borderRadius: BorderRadius.circular(29),
                      ),
                      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Icon(k == index ? tabs[k].active : tabs[k].icon, size: 23, color: k == index ? C.accent : C.text2),
                        const SizedBox(height: 1),
                        Text(tabs[k].label,
                            style: TextStyle(fontSize: 11, color: k == index ? C.accent : C.text2, fontWeight: k == index ? FontWeight.w700 : FontWeight.w500)),
                      ]),
                    ),
                  ),
                ),
            ]),
          ),
        ),
      ),
    );
  }
}
