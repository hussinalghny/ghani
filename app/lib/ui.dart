import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'api.dart';

/// تبويب الشاشة الرئيسية (يتغير من أي مكان، مثلا رصيد غير كاف ← المحفظة)
final tab = ValueNotifier<int>(0);

class C {
  static const violet = Color(0xFF5A35D6);
  static const deep = Color(0xFF3A1F9A);
  static const night = Color(0xFF1B1446);
  static const gold = Color(0xFFF5B83D);
  static const mist = Color(0xFFF1EDFF);
  static const lavender = Color(0xFFD2C4FF);
  static const bgAlt = Color(0xFFF7F5FC);
  static const text2 = Color(0xFF5F5A7A);
  static const text3 = Color(0xFF8C87A6);
  static const line = Color(0xFFECE8F6);
  static const success = Color(0xFF12805C);
}

TextStyle _font(String family, TextStyle base) {
  try {
    return GoogleFonts.getFont(family, textStyle: base);
  } catch (_) {
    return base;
  }
}

TextStyle head(double size, {Color color = C.night, FontWeight w = FontWeight.w700}) =>
    _font('Alexandria', TextStyle(fontSize: size, fontWeight: w, color: color, height: 1.4, letterSpacing: 0));

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(seedColor: C.violet, primary: C.violet, surface: Colors.white),
    scaffoldBackgroundColor: Colors.white,
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
  return base.copyWith(
    textTheme: tt.apply(bodyColor: C.night, displayColor: C.night),
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.white.withValues(alpha: .94),
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: .5,
      centerTitle: true,
      foregroundColor: C.night,
      titleTextStyle: head(17),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE4DDFB))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE4DDFB))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: C.violet, width: 1.6)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: C.violet,
        minimumSize: const Size.fromHeight(50),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15.5),
      ),
    ),
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
        Expanded(child: Text(msg)),
      ]),
      behavior: SnackBarBehavior.floating,
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
  Widget build(BuildContext context) => Text.rich(TextSpan(children: [
        TextSpan(text: text),
        const TextSpan(text: '.', style: TextStyle(color: C.gold)),
      ]), style: head(size, w: FontWeight.w800));
}

BoxDecoration patternBox({double radius = 0, List<Color>? colors}) => BoxDecoration(
      borderRadius: BorderRadius.circular(radius),
      gradient: LinearGradient(
        begin: Alignment.topRight,
        end: Alignment.bottomLeft,
        colors: colors ?? const [Color(0xFF6A45E6), C.violet, C.deep],
      ),
      image: const DecorationImage(image: AssetImage('assets/img/pattern.png'), repeat: ImageRepeat.repeat, scale: 1.6),
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
    if (fp == 0) return Text('مجاني', style: head(size, color: C.success));
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Text('${iqd(fp)} د.ع', style: head(size)),
      if (fp < p) ...[
        const SizedBox(width: 6),
        Text(iqd(p), style: const TextStyle(color: C.text3, decoration: TextDecoration.lineThrough, fontSize: 13)),
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
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: width,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: C.line),
          boxShadow: [BoxShadow(color: C.night.withValues(alpha: .05), blurRadius: 18, offset: const Offset(0, 6))],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          AspectRatio(aspectRatio: 16 / 9, child: Cover(c)),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text((c['category'] as Map?)?['name'] ?? '', style: const TextStyle(color: C.violet, fontSize: 12.5, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Text(c['title'] ?? '', style: head(17), maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 6),
              Row(children: [
                const Icon(CupertinoIcons.play_circle, size: 15, color: C.text3),
                const SizedBox(width: 4),
                Text('${c['lessons_count'] ?? 0} درس', style: const TextStyle(color: C.text3, fontSize: 13)),
                const SizedBox(width: 12),
                if (c['duration_label'] != null)
                  Flexible(child: Text(c['duration_label'], style: const TextStyle(color: C.text3, fontSize: 13), overflow: TextOverflow.ellipsis)),
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
      if (mounted) setState(() { data = d; busy = false; });
    } catch (e) {
      if (mounted) setState(() { error = e.toString(); busy = false; });
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
            const Icon(CupertinoIcons.wifi_exclamationmark, size: 40, color: C.text3),
            const SizedBox(height: 12),
            Text(error!, textAlign: TextAlign.center, style: const TextStyle(color: C.text2)),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: () { setState(() => busy = true); reload(); }, child: const Text('أعد المحاولة')),
          ]),
        ),
      );
    }
    return RefreshIndicator(color: C.violet, onRefresh: reload, child: widget.builder(context, data as T, reload));
  }
}

Map<String, dynamic> asMap(dynamic v) => Map<String, dynamic>.from((v ?? {}) as Map);
List<Map<String, dynamic>> asList(dynamic v) => ((v ?? []) as List).map((e) => asMap(e)).toList();

void go(BuildContext ctx, Widget page) => Navigator.of(ctx).push(CupertinoPageRoute(builder: (_) => page));

/// فوق شريط التبويبات (للمشغل حتى يملأ الشاشة)
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
