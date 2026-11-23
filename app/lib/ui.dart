import 'dart:io';
import 'dart:math' as math;

import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'api.dart';

/// تبويب الشاشة الرئيسية (يتغير من أي مكان، مثلا رصيد غير كاف ← المحفظة)
final tab = ValueNotifier<int>(0);

/// الوضع الليلي
final darkMode = ValueNotifier<bool>(false);

/// ألوان غني أكاديمي (تتبدل حسب الوضع الليلي)
class C {
  static bool get dark => darkMode.value;

  static const violet = Color(0xFF5A35D6);
  static const deep = Color(0xFF3A1F9A);
  static const night = Color(0xFF1B1446);
  static const gold = Color(0xFFF5B83D);
  static const lavender = Color(0xFFD2C4FF);
  static const success = Color(0xFF12805C);
  static const danger = Color(0xFFC2364A);

  // الليلي: أسود فاخر وذهب شمبانيا — النهاري: أبيض وبنفسجي الأكاديمية
  static const champagne = Color(0xFFD4AF6A);
  static Color get bg => dark ? const Color(0xFF000000) : Colors.white;
  static Color get card => dark ? const Color(0xFF141219) : Colors.white;
  static Color get bgAlt => dark ? const Color(0xFF0E0D12) : const Color(0xFFF7F5FC);
  static Color get text => dark ? const Color(0xFFF5F1E8) : night;
  static Color get text2 => dark ? const Color(0xFFA9A3B8) : const Color(0xFF5F5A7A);
  static Color get text3 => dark ? const Color(0xFF6E6880) : const Color(0xFF8C87A6);
  static Color get line => dark ? const Color(0x14FFFFFF) : const Color(0xFFECE8F6);
  static Color get mist => dark ? const Color(0xFF1F1C2B) : const Color(0xFFF1EDFF);
  static Color get accent => dark ? champagne : violet;
  static Color get primary => dark ? champagne : violet;
  static Color get onPrimary => dark ? const Color(0xFF1A1206) : Colors.white;
  static List<Color> get heroColors => dark
      ? const [Color(0xFF5B4A2A), Color(0xFF2A2316), Color(0xFF0B0A10)]
      : const [Color(0xFF6A45E6), violet, deep];
}

TextStyle _font(String family, TextStyle base) {
  try {
    return GoogleFonts.getFont(family, textStyle: base);
  } catch (_) {
    return base;
  }
}

TextStyle head(double size, {Color? color, FontWeight w = FontWeight.w700}) =>
    _font('Alexandria', TextStyle(fontSize: size, fontWeight: w, color: color ?? C.text, height: 1.4, letterSpacing: 0));

ThemeData buildTheme(bool dark) {
  final base = ThemeData(
    useMaterial3: true,
    brightness: dark ? Brightness.dark : Brightness.light,
    colorScheme: ColorScheme.fromSeed(
      seedColor: C.violet,
      brightness: dark ? Brightness.dark : Brightness.light,
      primary: C.primary,
      onPrimary: C.onPrimary,
      surface: C.bg,
    ),
    scaffoldBackgroundColor: C.bg,
    splashFactory: NoSplash.splashFactory,
    pageTransitionsTheme: const PageTransitionsTheme(builders: {
      TargetPlatform.android: CupertinoPageTransitionsBuilder(),
      TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
    }),
  );
  TextTheme tt;
  try {
    tt = GoogleFonts.getTextTheme('Readex Pro', base.textTheme);
  } catch (_) {
    tt = base.textTheme;
  }
  final border = OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: dark ? const Color(0xFF3A3268) : const Color(0xFFE4DDFB)));
  return base.copyWith(
    textTheme: tt.apply(bodyColor: C.text, displayColor: C.text),
    appBarTheme: AppBarTheme(
      backgroundColor: C.bg.withValues(alpha: .94),
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: .5,
      centerTitle: true,
      foregroundColor: C.text,
      titleTextStyle: head(17),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: C.card,
      hintStyle: TextStyle(color: C.text3),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: border,
      enabledBorder: border,
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: C.primary, width: 1.6)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: C.primary,
        foregroundColor: C.onPrimary,
        disabledBackgroundColor: C.line,
        minimumSize: const Size.fromHeight(50),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15.5),
      ),
    ),
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating),
    dividerColor: C.line,
    listTileTheme: ListTileThemeData(iconColor: C.accent, textColor: C.text),
  );
}

String iqd(num? v) {
  final s = (v ?? 0).round().abs().toString();
  final b = StringBuffer();
  for (var i = 0; i < s.length; i++) {
    if (i > 0 && (s.length - i) % 3 == 0) b.write(',');
    b.write(s[i]);
  }
  return '${(v ?? 0) < 0 ? '-' : ''}$b';
}

String mmss(num? secs) {
  final s = (secs ?? 0).toInt();
  return '${s ~/ 60}:${(s % 60).toString().padLeft(2, '0')}';
}

void toast(BuildContext ctx, String msg, {bool error = false}) {
  ScaffoldMessenger.of(ctx)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Row(children: [
        Icon(error ? CupertinoIcons.exclamationmark_circle : CupertinoIcons.check_mark_circled,
            color: error ? const Color(0xFFFF97A6) : const Color(0xFF6FE3B5), size: 20),
        const SizedBox(width: 10),
        Expanded(child: Text(msg, style: const TextStyle(color: Colors.white))),
      ]),
      backgroundColor: C.night.withValues(alpha: .96),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
    ));
}

/// عنوان كبير بنقطة ذهبية (توقيع الأكاديمية)
class Title2 extends StatelessWidget {
  final String text;
  final double size;
  const Title2(this.text, {super.key, this.size = 28});
  @override
  Widget build(BuildContext context) => Text.rich(
        TextSpan(children: [TextSpan(text: text), const TextSpan(text: '.', style: TextStyle(color: C.gold))]),
        style: head(size, w: FontWeight.w800),
      );
}

/// حركة دخول خفيفة: ظهور تدريجي مع صعود بسيط، متتالية حسب الترتيب
class FadeSlide extends StatelessWidget {
  final Widget child;
  final int index;
  const FadeSlide({super.key, required this.child, this.index = 0});
  @override
  Widget build(BuildContext context) {
    final ms = 380 + (index.clamp(0, 8) * 60);
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: ms),
      curve: Curves.easeOutCubic,
      builder: (_, t, c) => Opacity(opacity: t, child: Transform.translate(offset: Offset(0, 14 * (1 - t)), child: c)),
      child: child,
    );
  }
}

/// ضغطة ناعمة تصغر العنصر قليلا (مثل iOS)
class Pressable extends StatefulWidget {
  final Widget child;
  final VoidCallback? onTap;
  const Pressable({super.key, required this.child, this.onTap});
  @override
  State<Pressable> createState() => _PressableState();
}

class _PressableState extends State<Pressable> {
  bool down = false;
  @override
  Widget build(BuildContext context) => GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTapDown: (_) => setState(() => down = true),
        onTapUp: (_) => setState(() => down = false),
        onTapCancel: () => setState(() => down = false),
        onTap: widget.onTap,
        child: AnimatedScale(scale: down ? .97 : 1, duration: const Duration(milliseconds: 120), child: widget.child),
      );
}

BoxDecoration patternBox({double radius = 0, List<Color>? colors}) => BoxDecoration(
      borderRadius: BorderRadius.circular(radius),
      gradient: LinearGradient(
        begin: Alignment.topRight,
        end: Alignment.bottomLeft,
        colors: colors ?? C.heroColors,
      ),
      image: const DecorationImage(image: AssetImage('assets/img/pattern.png'), repeat: ImageRepeat.repeat, scale: 1.6),
    );

BoxDecoration cardBox({double radius = 20}) => BoxDecoration(
      color: C.card,
      borderRadius: BorderRadius.circular(radius),
      border: Border.all(color: C.line),
    );

IconData catIcon(String? n) => switch (n) {
      'shield' => CupertinoIcons.shield_lefthalf_fill,
      'globe' => CupertinoIcons.globe,
      'phone' => CupertinoIcons.device_phone_portrait,
      'cpu' => CupertinoIcons.memories,
      'database' => CupertinoIcons.tray_2,
      'chart' => CupertinoIcons.chart_bar,
      'pen' => CupertinoIcons.pencil,
      'book' => CupertinoIcons.book,
      _ => CupertinoIcons.chevron_left_slash_chevron_right,
    };

class Cover extends StatelessWidget {
  final Map c;
  final double radius;
  const Cover(this.c, {super.key, this.radius = 0});
  @override
  Widget build(BuildContext context) {
    final url = c['cover'] as String?;
    return ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: Container(
          decoration: patternBox(),
          alignment: Alignment.center,
          child: url != null
              ? Image.network(url, fit: BoxFit.cover, width: double.infinity, height: double.infinity)
              : Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .16),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.white.withValues(alpha: .25)),
                  ),
                  child: Icon(catIcon((c['category'] as Map?)?['icon'] as String?), color: Colors.white, size: 28),
                ),
        ),
    );
  }
}

class PriceText extends StatelessWidget {
  final Map c;
  final double size;
  const PriceText(this.c, {super.key, this.size = 17});
  @override
  Widget build(BuildContext context) {
    final fp = (c['final_price'] ?? 0) as num;
    final p = (c['price'] ?? 0) as num;
    if (Api.storeBuild) return Text('${c['lessons_count'] ?? ''} درس', style: head(size * .85, color: C.text2));
    if (fp == 0) return Text('مجاني', style: head(size, color: C.success));
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Text('${iqd(fp)} د.ع', style: head(size)),
      if (fp < p) ...[
        const SizedBox(width: 6),
        Text(iqd(p), style: TextStyle(color: C.text3, decoration: TextDecoration.lineThrough, fontSize: 13)),
      ],
    ]);
  }
}

class CourseCard extends StatelessWidget {
  final Map c;
  final VoidCallback onTap;
  final double? width;
  const CourseCard(this.c, {super.key, required this.onTap, this.width});
  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        width: width,
        decoration: cardBox().copyWith(
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: C.dark ? .25 : .05), blurRadius: 18, offset: const Offset(0, 6))],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          AspectRatio(aspectRatio: 16 / 9, child: Cover(c)),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text((c['category'] as Map?)?['name'] ?? '', style: TextStyle(color: C.accent, fontSize: 12.5, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Text(c['title'] ?? '', style: head(17), maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 6),
              Row(children: [
                Icon(CupertinoIcons.play_circle, size: 15, color: C.text3),
                const SizedBox(width: 4),
                Text('${c['lessons_count'] ?? 0} درس', style: TextStyle(color: C.text3, fontSize: 13)),
                const SizedBox(width: 12),
                if (c['duration_label'] != null)
                  Flexible(child: Text(c['duration_label'], style: TextStyle(color: C.text3, fontSize: 13), overflow: TextOverflow.ellipsis)),
              ]),
              const SizedBox(height: 10),
              PriceText(c),
            ]),
          ),
        ]),
      ),
    );
  }
}

/// حالة تحميل/خطأ موحدة
class Loader<T> extends StatefulWidget {
  final Future<T> Function() load;
  final Widget Function(BuildContext, T, Future<void> Function() reload) builder;
  const Loader({super.key, required this.load, required this.builder});
  @override
  State<Loader<T>> createState() => _LoaderState<T>();
}

class _LoaderState<T> extends State<Loader<T>> {
  T? data;
  String? error;
  bool busy = true;

  @override
  void initState() {
    super.initState();
    reload();
  }

  Future<void> reload() async {
    setState(() => error = null);
    try {
      final d = await widget.load();
      if (mounted) {
        setState(() {
          data = d;
          busy = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = e.toString();
          busy = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (busy && data == null) return const Center(child: CupertinoActivityIndicator(radius: 13));
    if (error != null && data == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(CupertinoIcons.wifi_exclamationmark, size: 40, color: C.text3),
            const SizedBox(height: 12),
            Text(error!, textAlign: TextAlign.center, style: TextStyle(color: C.text2)),
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () {
                setState(() => busy = true);
                reload();
              },
              child: const Text('أعد المحاولة'),
            ),
          ]),
        ),
      );
    }
    return RefreshIndicator(
      color: C.primary,
      onRefresh: reload,
      child: AnimatedSwitcher(duration: const Duration(milliseconds: 250), child: widget.builder(context, data as T, reload)),
    );
  }
}

/// شاشة فارغة بأيقونة وعنوان وزر
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String text;
  final Widget? action;
  const EmptyState({super.key, required this.icon, required this.title, required this.text, this.action});
  @override
  Widget build(BuildContext context) => FadeSlide(
        child: Container(
          padding: const EdgeInsets.all(28),
          decoration: cardBox(),
          child: Column(children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(20)),
              child: Icon(icon, size: 30, color: C.accent),
            ),
            const SizedBox(height: 14),
            Text(title, style: head(18), textAlign: TextAlign.center),
            const SizedBox(height: 6),
            Text(text, textAlign: TextAlign.center, style: TextStyle(color: C.text2, height: 1.6)),
            if (action != null) ...[const SizedBox(height: 18), action!],
          ]),
        ),
      );
}

Map<String, dynamic> asMap(dynamic v) => Map<String, dynamic>.from((v ?? {}) as Map);
List<Map<String, dynamic>> asList(dynamic v) => ((v ?? []) as List).map((e) => asMap(e)).toList();

void go(BuildContext ctx, Widget page) => Navigator.of(ctx).push(CupertinoPageRoute(builder: (_) => page));

/// فوق شريط التبويبات (للمشغل والمحادثة حتى تملأ الشاشة)
Future<void> goRoot(BuildContext ctx, Widget page) =>
    Navigator.of(ctx, rootNavigator: true).push(CupertinoPageRoute(builder: (_) => page));

Future<void> withBusy(BuildContext ctx, Future<void> Function() f) async {
  showCupertinoDialog(context: ctx, builder: (_) => const Center(child: CupertinoActivityIndicator(radius: 14, color: Colors.white)));
  try {
    await f();
  } finally {
    if (ctx.mounted) Navigator.of(ctx, rootNavigator: true).pop();
  }
}

String err(Object e) => e is ApiException ? e.message : 'حدث خطأ. أعد المحاولة.';


/// نسخة الكمبيوتر (ويندوز/ماك)
final bool kDesktop = !kIsWeb && (Platform.isWindows || Platform.isMacOS || Platform.isLinux);

/// شبكة تتوزع حسب عرض الشاشة (للكمبيوتر)
class AutoGrid extends StatelessWidget {
  final List<Widget> children;
  final double minItem, gap, runGap;
  const AutoGrid({super.key, required this.children, this.minItem = 300, this.gap = 18, this.runGap = 18});
  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (ctx, box) {
      final n = math.max(1, ((box.maxWidth + gap) / (minItem + gap)).floor());
      final w = (box.maxWidth - gap * (n - 1)) / n;
      return Wrap(spacing: gap, runSpacing: runGap, children: [for (final x in children) SizedBox(width: w, child: x)]);
    });
  }
}
