import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;

import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

import 'api.dart';

/// تبويب الشاشة الرئيسية (يتغير من أي مكان، مثلا رصيد غير كاف ← المحفظة)
final tab = ValueNotifier<int>(0);

/// الوضع الليلي
final darkMode = ValueNotifier<bool>(false);

/// ألوان غني أكاديمي: نهاري أبيض نظيف (الافتراضي) وليلي رمادي غامق، والبنفسجي للأزرار والتمييز
class C {
  static bool get dark => darkMode.value;

  static const violet = Color(0xFF5A35D6);
  static const deep = Color(0xFF3A1F9A);
  static const night = Color(0xFF1B1446);
  static const gold = Color(0xFFF5B83D);
  static const lavender = Color(0xFFD2C4FF);
  static const success = Color(0xFF12805C);
  static const danger = Color(0xFFC2364A);
  static const champagne = Color(0xFFD4AF6A);

  static Color get bg => dark ? const Color(0xFF0E0E12) : Colors.white;
  static Color get card => dark ? const Color(0xFF17171E) : Colors.white;
  static Color get bgAlt => dark ? const Color(0xFF16161C) : const Color(0xFFF7F6FB);
  static Color get text => dark ? const Color(0xFFF2F1F7) : const Color(0xFF1C1D1F);
  static Color get text2 => dark ? const Color(0xFFC2C0CE) : const Color(0xFF4A4D50);
  static Color get text3 => dark ? const Color(0xFF9895A8) : const Color(0xFF6A6F73);
  static Color get line => dark ? const Color(0x17FFFFFF) : const Color(0xFFE6E3EE);
  static Color get mist => dark ? const Color(0x248F72F5) : const Color(0xFFF1EDFF);
  static Color get accent => dark ? const Color(0xFF9D85F7) : violet;
  static Color get primary => dark ? const Color(0xFF7B5CF0) : violet;
  static Color get onPrimary => Colors.white;
  static Color get star => dark ? gold : const Color(0xFFC4710D);
  static List<Color> get heroColors => const [Color(0xFF6A45E6), violet, deep];
}

/// خط ثمانية سانس للعناوين والنص (مضمن بالتطبيق وقت البناء)
const fontHead = 'ThmanyahSans';
const fontBody = 'ThmanyahSans';

TextStyle _font(String family, TextStyle base) => base.copyWith(fontFamily: family == 'Alexandria' ? fontHead : fontBody);

TextStyle head(double size, {Color? color, FontWeight w = FontWeight.w700}) =>
    _font('Alexandria', TextStyle(fontSize: size, fontWeight: w, color: color ?? C.text, height: 1.4, letterSpacing: 0));

ThemeData buildTheme(bool dark) {
  final base = ThemeData(
    useMaterial3: true,
    fontFamily: fontBody,
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
  final tt = base.textTheme.apply(fontFamily: fontBody);
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

/// عنوان قسم واضح (بدون نقطة، أسلوب المنصات التعليمية)
class Title2 extends StatelessWidget {
  final String text;
  final double size;
  const Title2(this.text, {super.key, this.size = 28});
  @override
  Widget build(BuildContext context) => Text(text, style: head(size, w: FontWeight.w700));
}

/// كانت حركة دخول؛ صارت بدون حركة حتى تطلع الشاشات فورا وتخف على الأجهزة المتوسطة
class FadeSlide extends StatelessWidget {
  final Widget child;
  final int index;
  const FadeSlide({super.key, required this.child, this.index = 0});
  @override
  Widget build(BuildContext context) => child;
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
  /// للقوائم: نسخة مصغرة أخف (480px)
  final bool thumb;
  const Cover(this.c, {super.key, this.radius = 0, this.thumb = false});
  @override
  Widget build(BuildContext context) {
    final url = (thumb ? (c['thumb'] ?? c['cover']) : c['cover']) as String?;
    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: Container(
        color: C.mist,
        alignment: Alignment.center,
        child: url != null
            ? CachedNetworkImage(
                imageUrl: url,
                fit: BoxFit.cover,
                width: double.infinity,
                height: double.infinity,
                fadeInDuration: const Duration(milliseconds: 120),
                memCacheWidth: thumb ? 480 : 1080,
                placeholder: (_, __) => ColoredBox(color: C.mist),
                errorWidget: (_, __, ___) => Icon(catIcon((c['category'] as Map?)?['icon'] as String?), color: C.accent, size: 30),
              )
            : Icon(catIcon((c['category'] as Map?)?['icon'] as String?), color: C.accent, size: 32),
      ),
    );
  }
}

/// نجوم التقييم، أو «كورس جديد» إذا ما أكو تقييمات بعد
class Stars extends StatelessWidget {
  final Map c;
  final double size;
  const Stars(this.c, {super.key, this.size = 13});
  @override
  Widget build(BuildContext context) {
    final n = (c['rating_count'] ?? 0) as num;
    final r = ((c['rating'] ?? 0) as num).toDouble();
    if (n == 0) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 1),
        decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(4)),
        child: Text('كورس جديد', style: TextStyle(color: C.accent, fontSize: size - 1.5, fontWeight: FontWeight.w700)),
      );
    }
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Text(r.toStringAsFixed(1), style: TextStyle(color: C.star, fontWeight: FontWeight.w700, fontSize: size)),
      const SizedBox(width: 4),
      for (var i = 1; i <= 5; i++) Icon(CupertinoIcons.star_fill, size: size - 1, color: i <= r.round() ? C.star : C.line),
      const SizedBox(width: 4),
      Text('($n)', textDirection: TextDirection.ltr, style: TextStyle(color: C.text3, fontSize: size - 1)),
    ]);
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
    if (Api.storeBuild) return Text('${c['lessons_count'] ?? ''} درس', style: TextStyle(fontSize: size * .8, color: C.text2, fontWeight: FontWeight.w600));
    if (fp == 0) return Text('مجاني', style: head(size, color: C.success));
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Text('${iqd(fp)} د.ع', style: head(size, w: FontWeight.w700)),
      if (fp < p) ...[
        const SizedBox(width: 6),
        Text(iqd(p), style: TextStyle(color: C.text3, decoration: TextDecoration.lineThrough, fontSize: 13)),
      ],
    ]);
  }
}

/// بطاقة كورس: صورة، عنوان، مدرب، تقييم، سعر
class CourseCard extends StatelessWidget {
  final Map c;
  final VoidCallback onTap;
  final double? width;
  const CourseCard(this.c, {super.key, required this.onTap, this.width});
  @override
  Widget build(BuildContext context) {
    final inst = c['instructor'] as Map?;
    return Pressable(
      onTap: onTap,
      child: SizedBox(
        width: width,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          AspectRatio(
            aspectRatio: 16 / 9,
            child: DecoratedBox(
              position: DecorationPosition.foreground,
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: C.line)),
              child: Cover(c, radius: 12, thumb: true),
            ),
          ),
          const SizedBox(height: 9),
          Text(c['title'] ?? '', style: head(15.5), maxLines: 2, overflow: TextOverflow.ellipsis),
          Text(inst?['name'] ?? (c['category'] as Map?)?['name'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: C.text3, fontSize: 12.5)),
          const SizedBox(height: 3),
          Stars(c),
          const SizedBox(height: 3),
          PriceText(c, size: 15.5),
          if (c['is_featured'] == true) ...[
            const SizedBox(height: 5),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
              decoration: BoxDecoration(color: const Color(0xFFFFF2D6), borderRadius: BorderRadius.circular(4)),
              child: const Text('الأكثر طلبا', style: TextStyle(color: Color(0xFF7A4C00), fontSize: 11.5, fontWeight: FontWeight.w700)),
            ),
          ],
        ]),
      ),
    );
  }
}

/// صف كورس أفقي (صورة صغيرة + تفاصيل) للقوائم
class CourseRow extends StatelessWidget {
  final Map c;
  final VoidCallback onTap;
  const CourseRow(this.c, {super.key, required this.onTap});
  @override
  Widget build(BuildContext context) => Pressable(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(border: Border(bottom: BorderSide(color: C.line))),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            SizedBox(width: 116, height: 68, child: Cover(c, radius: 10, thumb: true)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(c['title'] ?? '', style: head(14.5), maxLines: 2, overflow: TextOverflow.ellipsis),
                const SizedBox(height: 2),
                Stars(c, size: 12.5),
                const SizedBox(height: 2),
                PriceText(c, size: 14),
              ]),
            ),
          ]),
        ),
      );
}

/// مستطيل رمادي يحجز مكان المحتوى لحد ما يوصل (بدل الدوارة)
class Skel extends StatelessWidget {
  final double? w, h;
  final double r;
  const Skel({super.key, this.w, this.h, this.r = 8});
  @override
  Widget build(BuildContext context) => Container(width: w, height: h, decoration: BoxDecoration(color: C.dark ? const Color(0xFF22222A) : const Color(0xFFEFEDF4), borderRadius: BorderRadius.circular(r)));
}

/// هيكل تحميل عام: قائمة بطاقات رمادية
class SkeletonList extends StatelessWidget {
  final bool header;
  const SkeletonList({super.key, this.header = true});
  @override
  Widget build(BuildContext context) => ListView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(18, 16, 18, 0),
        children: [
          if (header) ...[const Skel(w: 160, h: 26), const SizedBox(height: 16), const Skel(h: 48, r: 14), const SizedBox(height: 20)],
          const Skel(h: 190, r: 16),
          const SizedBox(height: 22),
          for (var i = 0; i < 4; i++)
            const Padding(
              padding: EdgeInsets.only(bottom: 16),
              child: Row(children: [
                Skel(w: 116, h: 68, r: 10),
                SizedBox(width: 12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Skel(h: 14), SizedBox(height: 8), Skel(w: 120, h: 12), SizedBox(height: 8), Skel(w: 80, h: 12)])),
              ]),
            ),
        ],
      );
}

/// نسخة محفوظة من آخر رد لكل طلب: الشاشة تطلع فورا من المحفوظ وتتحدث بالخلفية
class ResCache {
  static final _mem = <String, dynamic>{};
  static File _file(String k) => File('${Directory.systemTemp.path}/ga_cache_${k.hashCode.toUnsigned(32)}.json');

  static dynamic peek(String k) {
    if (_mem.containsKey(k)) return _mem[k];
    try {
      final f = _file(k);
      if (f.existsSync()) return _mem[k] = jsonDecode(f.readAsStringSync());
    } catch (_) {}
    return null;
  }

  static void put(String k, dynamic v) {
    _mem[k] = v;
    try {
      _file(k).writeAsStringSync(jsonEncode(v));
    } catch (_) {}
  }

  static void clear() {
    _mem.clear();
    try {
      for (final f in Directory.systemTemp.listSync()) {
        if (f.path.contains('ga_cache_')) f.deleteSync();
      }
    } catch (_) {}
  }
}

/// حالة تحميل/خطأ موحدة. مع cache: تعرض المحفوظ فورا وتحدث بصمت. مع skeleton: هيكل رمادي بدل الدوارة
class Loader<T> extends StatefulWidget {
  final Future<T> Function() load;
  final Widget Function(BuildContext, T, Future<void> Function() reload) builder;
  final String? cache;
  final Widget? skeleton;
  const Loader({super.key, required this.load, required this.builder, this.cache, this.skeleton});
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
    if (widget.cache != null) {
      final c = ResCache.peek(_key);
      if (c != null) {
        try {
          data = _cast(c);
        } catch (_) {}
      }
    }
    reload();
  }

  String get _key => '${Api.i.token != null ? 'u' : 'g'}:${widget.cache}';

  T _cast(dynamic c) {
    if (c is T) return c;
    if (c is Map) return asMap(c) as T;
    if (c is List) return asList(c) as T;
    return c as T;
  }

  Future<void> reload() async {
    if (mounted) setState(() => error = null);
    try {
      final d = await widget.load();
      if (widget.cache != null) ResCache.put(_key, d);
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
    if (busy && data == null) return widget.skeleton ?? const Center(child: CupertinoActivityIndicator(radius: 13));
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
      child: widget.builder(context, data as T, reload),
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
