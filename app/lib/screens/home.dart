import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../api.dart';
import '../ui.dart';
import 'auth.dart';
import 'chat.dart';
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
                padding: const EdgeInsets.fromLTRB(20, 12, 12, 0),
                child: Row(children: [
                  Image.asset(C.dark ? 'assets/img/logo-dark.png' : 'assets/img/logo-light.png', height: 36),
                  const Spacer(),
                  IconButton(onPressed: () => go(ctx, const CoursesScreen()), icon: Icon(CupertinoIcons.search, color: C.text)),
                  ValueListenableBuilder<bool>(
                    valueListenable: Api.i.loggedIn,
                    builder: (_, inApp, __) => inApp
                        ? const SizedBox.shrink()
                        : TextButton(onPressed: () => ensureLogin(ctx), child: Text('دخول', style: TextStyle(color: C.accent, fontWeight: FontWeight.w600))),
                  ),
                ]),
              ),
              FadeSlide(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 16),
                  child: Text(_greeting(), style: head(26, w: FontWeight.w800)),
                ),
              ),
              if (featured.isNotEmpty) FadeSlide(index: 1, child: _Hero(c: featured.first, onTap: () => go(ctx, CourseScreen(slug: featured.first['slug'])))),
              if (cats.isNotEmpty) ...[
                const SizedBox(height: 18),
                SizedBox(
                  height: 42,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    scrollDirection: Axis.horizontal,
                    itemCount: cats.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (_, i) => FadeSlide(
                      index: i,
                      child: Pressable(
                        onTap: () => go(ctx, CoursesScreen(category: cats[i])),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          decoration: BoxDecoration(color: C.bgAlt, borderRadius: BorderRadius.circular(99)),
                          child: Row(children: [
                            Icon(catIcon(cats[i]['icon']), size: 17, color: C.accent),
                            const SizedBox(width: 6),
                            Text(cats[i]['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w500)),
                          ]),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
              if (featured.length > 1) ...[
                const Padding(padding: EdgeInsets.fromLTRB(20, 26, 20, 12), child: Title2('مختارات للنخبة', size: 22)),
                SizedBox(
                  height: 316,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    scrollDirection: Axis.horizontal,
                    itemCount: featured.length - 1,
                    separatorBuilder: (_, __) => const SizedBox(width: 14),
                    itemBuilder: (_, i) => FadeSlide(
                      index: i + 2,
                      child: CourseCard(featured[i + 1], width: 260, onTap: () => go(ctx, CourseScreen(slug: featured[i + 1]['slug']))),
                    ),
                  ),
                ),
              ],
              if (latest.isNotEmpty) ...[
                const Padding(padding: EdgeInsets.fromLTRB(20, 26, 20, 12), child: Title2('أحدث الكورسات', size: 22)),
                if (kDesktop)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: AutoGrid(children: [
                      for (var i = 0; i < latest.length; i++)
                        FadeSlide(index: i + 3, child: CourseCard(latest[i], onTap: () => go(ctx, CourseScreen(slug: latest[i]['slug'])))),
                    ]),
                  )
                else
                  for (var i = 0; i < latest.length; i++)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 14),
                      child: FadeSlide(index: i + 3, child: CourseCard(latest[i], onTap: () => go(ctx, CourseScreen(slug: latest[i]['slug'])))),
                    ),
              ],
            ]);
          },
        ),
      ),
    );
  }

  String _greeting() {
    final n = (Api.i.user?['name'] as String?)?.split(' ').first;
    return n != null && n.isNotEmpty ? 'أهلا $n' : 'تعلم. طبق. اشتغل';
  }
}

/// بطاقة البطل الكبيرة (مثل البروتوتايب): أسود وذهب ليلا، بنفسجي نهارا
class _Hero extends StatelessWidget {
  final Map c;
  final VoidCallback onTap;
  const _Hero({required this.c, required this.onTap});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Pressable(
        onTap: onTap,
        child: Container(
          height: 400,
          clipBehavior: Clip.antiAlias,
          decoration: patternBox(radius: 30).copyWith(
            border: Border.all(color: C.dark ? C.champagne.withValues(alpha: .28) : Colors.transparent),
            boxShadow: [BoxShadow(color: (C.dark ? Colors.black : C.deep).withValues(alpha: .35), blurRadius: 40, offset: const Offset(0, 18))],
          ),
          child: Stack(children: [
            if (c['cover'] != null) Positioned.fill(child: Image.network(c['cover'], fit: BoxFit.cover)),
            Align(
              alignment: const Alignment(0, -.35),
              child: Icon(catIcon((c['category'] as Map?)?['icon']), size: 110, color: (C.dark ? C.champagne : Colors.white).withValues(alpha: .85)),
            ),
            const Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Color(0xB8000000)], stops: [.35, 1])),
              ),
            ),
            Positioned(
              left: 20,
              right: 20,
              bottom: 20,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 5),
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99), border: Border.all(color: Colors.white.withValues(alpha: .22))),
                  child: const Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(CupertinoIcons.star_fill, size: 12, color: C.gold),
                    SizedBox(width: 6),
                    Text('مختار هذا الشهر', style: TextStyle(color: Colors.white, fontSize: 12)),
                  ]),
                ),
                const SizedBox(height: 12),
                Text.rich(TextSpan(children: [TextSpan(text: c['title'] ?? ''), const TextSpan(text: '.', style: TextStyle(color: C.gold))]),
                    style: head(30, color: Colors.white, w: FontWeight.w800)),
                if (c['subtitle'] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(c['subtitle'], maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .82), height: 1.6)),
                  ),
                const SizedBox(height: 16),
                Row(children: [
                  Container(
                    height: 48,
                    padding: const EdgeInsets.symmetric(horizontal: 22),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFFF1D79C), Color(0xFFC9A04F)]),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: C.champagne.withValues(alpha: .3), blurRadius: 20, offset: const Offset(0, 8))],
                    ),
                    child: const Text('ابدأ الآن', style: TextStyle(color: Color(0xFF1A1206), fontWeight: FontWeight.w600)),
                  ),
                  if (!Api.storeBuild && (c['final_price'] ?? 0) > 0) ...[
                    const SizedBox(width: 10),
                    Container(
                      height: 48,
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white.withValues(alpha: .24))),
                      child: Text('${iqd(c['final_price'])} د.ع', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
                    ),
                  ],
                ]),
              ]),
            ),
          ]),
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
            style: TextStyle(color: C.text),
            backgroundColor: C.bgAlt,
            onSubmitted: (v) => setState(() {
              q = v.trim();
              k = UniqueKey();
            }),
          ),
        ),
        Expanded(
          child: Loader<List<Map<String, dynamic>>>(
            key: k,
            load: () async => asList((await Api.i.req('GET', '/courses?${slug != null ? 'category=$slug&' : ''}q=${Uri.encodeQueryComponent(q)}'))['data']),
            builder: (ctx, list, _) => list.isEmpty
                ? ListView(padding: const EdgeInsets.all(20), children: const [
                    EmptyState(icon: CupertinoIcons.search, title: 'ما لقينا كورسات', text: 'جرب كلمة ثانية أو تصفح كل الأقسام.'),
                  ])
                : kDesktop
                    ? ListView(padding: const EdgeInsets.all(20), children: [
                        AutoGrid(children: [
                          for (var i = 0; i < list.length; i++)
                            FadeSlide(index: i, child: CourseCard(list[i], onTap: () => go(ctx, CourseScreen(slug: list[i]['slug'])))),
                        ]),
                      ])
                    : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: list.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 14),
                    itemBuilder: (_, i) => FadeSlide(index: i, child: CourseCard(list[i], onTap: () => go(ctx, CourseScreen(slug: list[i]['slug'])))),
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
    if (!await ensureLogin(context)) return;
    if (!mounted) return;
    final free = (c['final_price'] ?? 0) == 0;
    final ok = await showCupertinoDialog<bool>(
      context: context,
      builder: (dctx) => CupertinoAlertDialog(
        title: Text(free ? 'تسجيل في الكورس؟' : 'تأكيد الاشتراك'),
        content: free ? null : Text('سيتم خصم ${iqd(c['final_price'])} د.ع من رصيدك.'),
        actions: [
          CupertinoDialogAction(onPressed: () => Navigator.pop(dctx, false), child: const Text('إلغاء')),
          CupertinoDialogAction(isDefaultAction: true, onPressed: () => Navigator.pop(dctx, true), child: const Text('اشترك')),
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
      if (e.code == 'insufficient_balance') tab.value = 3;
    }
  }

  Future<void> askInstructor() async {
    try {
      dynamic r;
      await withBusy(context, () async => r = await Api.i.req('POST', '/courses/${widget.slug}/chat'));
      if (!mounted) return;
      final c = asMap(r['data']);
      goRoot(context, ChatScreen(id: c['id'] as int, title: c['title'] ?? 'المدرب', subtitle: c['subtitle']));
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: C.bgAlt,
      appBar: AppBar(backgroundColor: C.bgAlt.withValues(alpha: .94)),
      body: Loader<Map<String, dynamic>>(
        key: k,
        load: () async => asMap((await Api.i.req('GET', '/courses/${widget.slug}'))['data']),
        builder: (ctx, c, _) {
          final sections = asList(c['sections']);
          final enrolled = c['is_enrolled'] == true;
          final outcomes = (c['outcomes'] as List? ?? []).cast<String>();
          final inst = c['instructor'] as Map?;
          return Stack(children: [
            ListView(padding: const EdgeInsets.fromLTRB(20, 4, 20, 130), children: [
              FadeSlide(child: Text((c['category'] as Map?)?['name'] ?? '', style: TextStyle(color: C.accent, fontWeight: FontWeight.w600))),
              const SizedBox(height: 4),
              FadeSlide(index: 1, child: Title2(c['title'] ?? '', size: 30)),
              if (c['subtitle'] != null) FadeSlide(index: 2, child: Text(c['subtitle'], style: TextStyle(color: C.text2, fontSize: 16, height: 1.7))),
              const SizedBox(height: 14),
              FadeSlide(
                index: 3,
                child: Wrap(spacing: 8, runSpacing: 8, children: [
                  _chip(CupertinoIcons.play_circle, '${c['lessons_count'] ?? 0} درس'),
                  if (c['duration_label'] != null) _chip(CupertinoIcons.time, c['duration_label']),
                  _chip(CupertinoIcons.chart_bar, c['level_label'] ?? ''),
                  if (c['has_certificate'] == true) _chip(CupertinoIcons.rosette, 'شهادة إتمام'),
                ]),
              ),
              const SizedBox(height: 18),
              FadeSlide(
                index: 4,
                child: kDesktop
                    ? Align(alignment: AlignmentDirectional.centerStart, child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 640), child: AspectRatio(aspectRatio: 16 / 9, child: Cover(c, radius: 20))))
                    : AspectRatio(aspectRatio: 16 / 9, child: Cover(c, radius: 20)),
              ),
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
                decoration: cardBox(radius: 18),
                clipBehavior: Clip.antiAlias,
                child: Column(children: [
                  for (final s in sections) ...[
                    Container(
                      width: double.infinity,
                      color: C.mist.withValues(alpha: .5),
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
                              child: Text('معاينة', style: TextStyle(color: C.accent, fontSize: 11.5, fontWeight: FontWeight.w600)),
                            ),
                          const SizedBox(width: 6),
                          if (!enrolled && l['is_free_preview'] != true) Icon(CupertinoIcons.lock, size: 15, color: C.text3),
                          const SizedBox(width: 6),
                          Text(mmss(l['duration']), style: TextStyle(color: C.text3, fontSize: 12.5)),
                        ]),
                        onTap: (enrolled || l['is_free_preview'] == true)
                            ? () => goRoot(ctx, PlayerScreen(lessonId: l['id'] as int, title: l['title'] ?? ''))
                            : null,
                      ),
                  ],
                ]),
              ),
              if (inst != null) ...[
                const SizedBox(height: 22),
                const Title2('المدرب', size: 21),
                const SizedBox(height: 10),
                Container(
                  decoration: cardBox(radius: 18),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: C.mist,
                      backgroundImage: inst['photo'] != null ? NetworkImage(inst['photo']) : null,
                      child: inst['photo'] == null ? Text((inst['name'] ?? ' ').toString().substring(0, 1), style: TextStyle(color: C.accent)) : null,
                    ),
                    title: Text(inst['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: inst['title'] != null ? Text(inst['title'], style: TextStyle(color: C.text2)) : null,
                    trailing: enrolled && inst['can_chat'] == true
                        ? TextButton.icon(onPressed: askInstructor, icon: const Icon(CupertinoIcons.chat_bubble_text, size: 18), label: const Text('اسأل'))
                        : null,
                  ),
                ),
              ],
              if (c['description'] != null) ...[
                const SizedBox(height: 16),
                const Title2('عن الكورس', size: 21),
                const SizedBox(height: 8),
                Text(c['description'], style: TextStyle(color: C.text2, height: 1.8)),
              ],
            ]),
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                padding: EdgeInsets.fromLTRB(20, 14, 20, 14 + MediaQuery.of(ctx).padding.bottom),
                decoration: BoxDecoration(color: C.bg.withValues(alpha: .96), border: Border(top: BorderSide(color: C.line))),
                child: enrolled
                    ? FilledButton(onPressed: () => go(ctx, CurriculumScreen(slug: widget.slug, title: c['title'] ?? '')), child: const Text('أكمل التعلم'))
                    : Api.storeBuild
                        ? (Api.i.loggedIn.value
                            ? Text('هذا الكورس غير مفعل لحسابك حاليا.', textAlign: TextAlign.center, style: TextStyle(color: C.text2))
                            : FilledButton(onPressed: () async { if (await ensureLogin(ctx)) setState(() => k = UniqueKey()); }, child: const Text('سجل دخولك')))
                        : Row(children: [
                        Expanded(child: PriceText(c, size: 20)),
                        SizedBox(
                          width: 170,
                          child: FilledButton(
                            style: FilledButton.styleFrom(backgroundColor: C.dark ? C.champagne : C.gold, foregroundColor: C.night),
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
        decoration: BoxDecoration(color: C.card, borderRadius: BorderRadius.circular(99), border: Border.all(color: C.line)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(i, size: 15, color: C.accent),
          const SizedBox(width: 6),
          Text(t, style: TextStyle(fontSize: 13, color: C.text2)),
        ]),
      );
}
