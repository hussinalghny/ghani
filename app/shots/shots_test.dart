// لقطات شاشات التطبيق ببيانات تجريبية (للتأكد من التصميم بدون جهاز). تشتغل بالـ CI فقط.
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ghani_academy/api.dart';
import 'package:ghani_academy/main.dart';
import 'package:ghani_academy/screens/home.dart';
import 'package:ghani_academy/ui.dart';

Future<void> _font(String family, List<String> files) async {
  final l = FontLoader(family);
  for (final f in files) {
    l.addFont(Future.value(ByteData.view(File(f).readAsBytesSync().buffer)));
  }
  await l.load();
}

Future<void> _fonts() async {
  await _font('ThmanyahSans', ['assets/fonts/thmanyahsans-Regular.otf', 'assets/fonts/thmanyahsans-Medium.otf', 'assets/fonts/thmanyahsans-Bold.otf']);
  final cfg = jsonDecode(File('.dart_tool/package_config.json').readAsStringSync()) as Map;
  for (final p in cfg['packages'] as List) {
    if (p['name'] == 'cupertino_icons') {
      final root = Uri.parse(p['rootUri'] as String);
      final dir = root.isAbsolute ? root.toFilePath() : File('.dart_tool/${p['rootUri']}').absolute.path;
      await _font('packages/cupertino_icons/CupertinoIcons', ['$dir/assets/CupertinoIcons.ttf']);
    }
  }
}

Map<String, dynamic> course(int i, String title, int price, {double rating = 0, int count = 0}) => {
      'id': i,
      'slug': 'c$i',
      'title': title,
      'subtitle': 'تتعلم من الصفر لمشروع حقيقي تضيفه لسيرتك، مع مدرب يشتغل بالمجال.',
      'price': price,
      'final_price': price,
      'lessons_count': 12,
      'level_label': 'مبتدئ',
      'has_certificate': true,
      'is_featured': i == 1,
      'rating': rating,
      'rating_count': count,
      'students_count': 120 * i,
      'category': {'id': 1, 'name': 'الأمن السيبراني', 'slug': 'cyber'},
      'instructor': {'name': 'مدرب أمن سيبراني', 'title': 'خبير اختبار اختراق', 'bio': 'يشتغل بمجال الأمن السيبراني من 8 سنوات، ودرب أكثر من 500 طالب.', 'can_chat': true},
    };

Widget app(Widget home) => MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: buildTheme(false),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [GlobalMaterialLocalizations.delegate, GlobalWidgetsLocalizations.delegate, GlobalCupertinoLocalizations.delegate],
      home: home,
    );

void main() {
  setUpAll(() async {
    kDesktop = false;
    await _fonts();
  });

  final c1 = course(1, 'الدفاع الذكي: الأمن السيبراني بالذكاء الاصطناعي', 200000, rating: 4.9, count: 31);
  final c2 = course(2, 'برمجة المواقع من الصفر للاحتراف', 150000, rating: 4.7, count: 12);
  final c3 = course(3, 'برمجة التطبيقات بـ Flutter', 150000);

  Future<void> shot(WidgetTester t, Widget w, String name, {bool dark = false}) async {
    t.view.physicalSize = const Size(1170, 2532);
    t.view.devicePixelRatio = 3;
    darkMode.value = dark;
    await t.pumpWidget(w);
    for (var i = 0; i < 6; i++) {
      await t.pump(const Duration(milliseconds: 200));
    }
    t.takeException();
    await expectLater(find.byType(MaterialApp).first, matchesGoldenFile('shots/$name.png'));
    t.takeException();
  }

  testWidgets('screens', (t) async {
    Api.i.token = null;
    ResCache.put('g:home', {
      'popular': [c1, c2, c3],
      'latest': [c3, c2, c1],
      'categories': [
        {'name': 'الأمن السيبراني', 'slug': 'cyber'},
        {'name': 'برمجة المواقع', 'slug': 'web'},
        {'name': 'التطبيقات', 'slug': 'apps'},
      ],
      'continue': null,
    });
    await shot(t, app(const Shell()), 'home');

    ResCache.put('g:course:c1', {
      ...c1,
      'description': 'كورس عملي.',
      'outcomes': ['تفهم طريقة تفكير المهاجم', 'تبني مختبر أمن خاص بيك', 'تستخدم الذكاء الاصطناعي لكشف التهديدات'],
      'total_duration': 86400,
      'is_enrolled': false,
      'updated_at': '2026-11-23',
      'reviews': [
        {'name': 'علي', 'rating': 5, 'body': 'كورس مرتب وعملي، استفدت هواية.', 'date': '2026-11-20'},
      ],
      'rating_breakdown': [
        {'stars': 5, 'percent': 90}, {'stars': 4, 'percent': 10}, {'stars': 3, 'percent': 0}, {'stars': 2, 'percent': 0}, {'stars': 1, 'percent': 0},
      ],
      'sections': [
        {'id': 1, 'title': 'كيف يفكر المهاجم', 'lessons': [
          {'id': 1, 'title': 'مقدمة الكورس', 'duration': 600, 'is_free_preview': true},
          {'id': 2, 'title': 'أنواع الهجمات', 'duration': 1690},
        ]},
        {'id': 2, 'title': 'بناء مختبرك', 'lessons': [{'id': 3, 'title': 'تنصيب الأدوات', 'duration': 1900}]},
      ],
    });
    await shot(t, app(const CourseScreen(slug: 'c1')), 'course');
    await t.drag(find.byType(ListView).first, const Offset(0, -1400));
    await t.pump(const Duration(milliseconds: 300));
    await expectLater(find.byType(MaterialApp).first, matchesGoldenFile('shots/course-2.png'));

    await shot(t, app(const Shell()), 'home-dark', dark: true);

    Api.i.token = 'x';
    Api.i.loggedIn.value = true;
    ResCache.put('u:home', {'popular': [c1, c2], 'latest': [c3], 'categories': [], 'continue': {'course': c1, 'lesson': {'id': 7, 'title': 'الدرس 7: كشف الهجمات'}, 'progress': 58}});
    await shot(t, app(const Shell()), 'home-user');
    ResCache.put('u:my', [
      [{'course': c1, 'progress': 58}, {'course': c2, 'progress': 20}],
      [],
    ]);
    tab.value = 2;
    await shot(t, app(const Shell()), 'my');
    tab.value = 0;
  });
}
