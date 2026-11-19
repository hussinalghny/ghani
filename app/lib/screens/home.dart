import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../api.dart';
import '../ui.dart';
import 'learn.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Loader<Map<String, dynamic>>(
          load: () async => asMap(await Api.i.req('GET', '/home')),
          builder: (ctx, d, _) {
            final featured = asList(d['featured']);
            final latest = asList(d['latest']);
            final cats = asList(d['categories']);
            return ListView(padding: const EdgeInsets.only(bottom: 24), children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
                child: Row(children: [
                  Image.asset('assets/img/logo-light.png', height: 36),
                  const Spacer(),
                  IconButton(
                    onPressed: () => go(ctx, const CoursesScreen()),
                    icon: const Icon(CupertinoIcons.search, color: C.night),
                  ),
                ]),
              ),
              const Padding(padding: EdgeInsets.fromLTRB(20, 18, 20, 4), child: Title2('تعلم. طبق. اشتغل', size: 30)),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: Text('كورسات تقنية بمدربين ممارسين، تطلع منها بمشروع حقيقي وشهادة.', style: TextStyle(color: C.text2, height: 1.7)),
              ),
              if (cats.isNotEmpty) ...[
                const SizedBox(height: 18),
                SizedBox(
                  height: 42,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    scrollDirection: Axis.horizontal,
                    itemCount: cats.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (_, i) => ActionChip(
                      avatar: Icon(catIcon(cats[i]['icon']), size: 17, color: C.violet),
                      label: Text(cats[i]['name'] ?? ''),
                      backgroundColor: C.bgAlt,
                      side: BorderSide.none,
                      shape: const StadiumBorder(),
                      onPressed: () => go(ctx, CoursesScreen(category: cats[i])),
                    ),
                  ),
                ),
              ],
              if (featured.isNotEmpty) ...[
                const Padding(padding: EdgeInsets.fromLTRB(20, 26, 20, 12), child: Title2('كورسات مميزة', size: 22)),
                SizedBox(
                  height: 316,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    scrollDirection: Axis.horizontal,
                    itemCount: featured.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 14),
                    itemBuilder: (_, i) => CourseCard(featured[i], width: 280, onTap: () => go(ctx, CourseScreen(slug: featured[i]['slug']))),
                  ),
                ),
              ],
              if (latest.isNotEmpty) ...[
                const Padding(padding: EdgeInsets.fromLTRB(20, 26, 20, 12), child: Title2('أحدث الكورسات', size: 22)),
                for (final c in latest)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 14),
                    child: CourseCard(c, onTap: () => go(ctx, CourseScreen(slug: c['slug']))),
                  ),
              ],
            ]);
          },
        ),
      ),
    );
  }
}

class CoursesScreen extends StatefulWidget {
  final Map? category;
  const CoursesScreen({super.key, this.category});
  @override
  State<CoursesScreen> createState() => _CoursesScreenState();
}

class _CoursesScreenState extends State<CoursesScreen> {
  String q = '';
  Key k = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final slug = widget.category?['slug'];
    return Scaffold(
      appBar: AppBar(title: Text(widget.category?['name'] ?? 'الكورسات')),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: CupertinoSearchTextField(
            placeholder: 'ابحث عن كورس',
            onSubmitted: (v) => setState(() { q = v.trim(); k = UniqueKey(); }),
          ),
        ),
        Expanded(
          child: Loader<List<Map<String, dynamic>>>(
            key: k,
            load: () async => asList((await Api.i.req('GET',
                '/courses?${slug != null ? 'category=$slug&' : ''}q=${Uri.encodeQueryComponent(q)}'))['data']),
            builder: (ctx, list, _) => list.isEmpty
                ? ListView(children: const [SizedBox(height: 120), Center(child: Text('ما لقينا كورسات.', style: TextStyle(color: C.text2)))])
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: list.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 14),
                    itemBuilder: (_, i) => CourseCard(list[i], onTap: () => go(ctx, CourseScreen(slug: list[i]['slug']))),
                  ),
          ),
        ),
      ]),
    );
  }
}

class CourseScreen extends StatefulWidget {
  final String slug;
  const CourseScreen({super.key, required this.slug});
  @override
  State<CourseScreen> createState() => _CourseScreenState();
}

class _CourseScreenState extends State<CourseScreen> {
  Key k = UniqueKey();

  Future<void> enroll(Map c) async {
    final free = (c['final_price'] ?? 0) == 0;
    final ok = await showCupertinoDialog<bool>(
      context: context,
      builder: (_) => CupertinoAlertDialog(
        title: Text(free ? 'تسجيل في الكورس؟' : 'تأكيد الاشتراك'),
        content: free ? null : Text('سيتم خصم ${iqd(c['final_price'])} د.ع من رصيدك.'),
        actions: [
          CupertinoDialogAction(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
          CupertinoDialogAction(isDefaultAction: true, onPressed: () => Navigator.pop(context, true), child: const Text('اشترك')),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      await withBusy(context, () async {
        final r = await Api.i.req('POST', '/courses/${widget.slug}/enroll');
        if (mounted) toast(context, r['message'] ?? 'تم التسجيل.');
      });
      setState(() => k = UniqueKey());
    } on ApiException catch (e) {
      if (!mounted) return;
      toast(context, e.message, error: true);
      if (e.code == 'insufficient_balance') tab.value = 2;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: C.bgAlt,
      appBar: AppBar(),
      body: Loader<Map<String, dynamic>>(
        key: k,
        load: () async => asMap((await Api.i.req('GET', '/courses/${widget.slug}'))['data']),
        builder: (ctx, c, _) {
          final sections = asList(c['sections']);
          final enrolled = c['is_enrolled'] == true;
          final outcomes = (c['outcomes'] as List? ?? []).cast<String>();
          return Stack(children: [
            ListView(padding: const EdgeInsets.fromLTRB(20, 4, 20, 120), children: [
              Text((c['category'] as Map?)?['name'] ?? '', style: const TextStyle(color: C.violet, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Title2(c['title'] ?? '', size: 30),
              if (c['subtitle'] != null) Text(c['subtitle'], style: const TextStyle(color: C.text2, fontSize: 16, height: 1.7)),
              const SizedBox(height: 14),
              Wrap(spacing: 8, runSpacing: 8, children: [
                _chip(CupertinoIcons.play_circle, '${c['lessons_count'] ?? 0} درس'),
                if (c['duration_label'] != null) _chip(CupertinoIcons.time, c['duration_label']),
                _chip(CupertinoIcons.chart_bar, c['level_label'] ?? ''),
                if (c['has_certificate'] == true) _chip(CupertinoIcons.rosette, 'شهادة إتمام'),
              ]),
              const SizedBox(height: 18),
              AspectRatio(aspectRatio: 16 / 9, child: Cover(c, radius: 20)),
              if (outcomes.isNotEmpty) ...[
                const SizedBox(height: 26),
                const Title2('ماذا ستتعلم', size: 21),
                const SizedBox(height: 10),
                for (final o in outcomes)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Padding(padding: EdgeInsets.only(top: 3), child: Icon(CupertinoIcons.checkmark_alt, color: C.success, size: 18)),
                      const SizedBox(width: 8),
                      Expanded(child: Text(o, style: const TextStyle(height: 1.6))),
                    ]),
                  ),
              ],
              const SizedBox(height: 22),
              const Title2('محتوى الكورس', size: 21),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: C.line)),
                clipBehavior: Clip.antiAlias,
                child: Column(children: [
                  for (final s in sections) ...[
                    Container(
                      width: double.infinity,
                      color: C.bgAlt,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      child: Text(s['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                    ),
                    for (final l in asList(s['lessons']))
                      ListTile(
                        dense: true,
                        leading: Icon(l['type'] == 'video' ? CupertinoIcons.play_circle : CupertinoIcons.doc_text, color: C.text3),
                        title: Text(l['title'] ?? '', style: const TextStyle(fontSize: 14.5)),
                        trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                          if (l['is_free_preview'] == true && !enrolled)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(99)),
                              child: const Text('معاينة', style: TextStyle(color: C.violet, fontSize: 11.5, fontWeight: FontWeight.w600)),
                            ),
                          const SizedBox(width: 6),
                          if (!enrolled && l['is_free_preview'] != true) const Icon(CupertinoIcons.lock, size: 15, color: C.text3),
                          const SizedBox(width: 6),
                          Text(mmss(l['duration']), style: const TextStyle(color: C.text3, fontSize: 12.5)),
                        ]),
                        onTap: (enrolled || l['is_free_preview'] == true)
                            ? () => goRoot(ctx, PlayerScreen(lessonId: l['id'] as int, title: l['title'] ?? ''))
                            : null,
                      ),
                  ],
                ]),
              ),
              if ((c['instructor'] as Map?) != null) ...[
                const SizedBox(height: 22),
                const Title2('المدرب', size: 21),
                const SizedBox(height: 10),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: CircleAvatar(backgroundColor: C.mist, child: Text((c['instructor']['name'] ?? ' ').toString().substring(0, 1), style: const TextStyle(color: C.violet))),
                  title: Text(c['instructor']['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: c['instructor']['title'] != null ? Text(c['instructor']['title']) : null,
                ),
              ],
              if (c['description'] != null) ...[
                const SizedBox(height: 16),
                const Title2('عن الكورس', size: 21),
                const SizedBox(height: 8),
                Text(c['description'], style: const TextStyle(color: C.text2, height: 1.8)),
              ],
            ]),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                padding: EdgeInsets.fromLTRB(20, 14, 20, 14 + MediaQuery.of(ctx).padding.bottom),
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: .96), border: const Border(top: BorderSide(color: C.line))),
                child: enrolled
                    ? FilledButton(
                        onPressed: () => go(ctx, CurriculumScreen(slug: widget.slug, title: c['title'] ?? '')),
                        child: const Text('أكمل التعلم'),
                      )
                    : Row(children: [
                        Expanded(child: PriceText(c, size: 20)),
                        SizedBox(
                          width: 170,
                          child: FilledButton(
                            style: FilledButton.styleFrom(backgroundColor: C.gold, foregroundColor: C.night),
                            onPressed: () => enroll(c),
                            child: const Text('اشترك الآن'),
                          ),
                        ),
                      ]),
              ),
            ),
          ]);
        },
      ),
    );
  }

  Widget _chip(IconData i, String t) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(99), border: Border.all(color: C.line)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(i, size: 15, color: C.violet),
          const SizedBox(width: 6),
          Text(t, style: const TextStyle(fontSize: 13, color: C.text2)),
        ]),
      );
}
