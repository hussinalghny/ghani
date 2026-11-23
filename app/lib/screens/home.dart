import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../api.dart';
import '../ui.dart';
import 'account.dart';
import 'auth.dart';
import 'chat.dart';
import 'learn.dart';

void openCourse(BuildContext ctx, Map c) => goRoot(ctx, CourseScreen(slug: c['slug'] as String));

/// الرئيسية بأسلوب المنصات التعليمية: بحث، أقسام، كمل التعلم، صفوف كورسات
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: ValueListenableBuilder<bool>(
          valueListenable: Api.i.loggedIn,
          builder: (_, inApp, __) => Loader<Map<String, dynamic>>(
            key: ValueKey(inApp),
            cache: 'home',
            skeleton: const SkeletonList(),
            load: () async => asMap(await Api.i.req('GET', '/home')),
            builder: (ctx, d, _) {
              final popular = asList(d['popular']).isNotEmpty ? asList(d['popular']) : asList(d['featured']);
              final latest = asList(d['latest']);
              final cats = asList(d['categories']);
              final cont = d['continue'] == null ? null : asMap(d['continue']);
              return ListView(padding: const EdgeInsets.only(bottom: 28), children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(18, 8, 12, 0),
                  child: Row(children: [
                    Image.asset(C.dark ? 'assets/img/logo-dark.png' : 'assets/img/logo-light.png', height: 34),
                    const Spacer(),
                    if (inApp)
                      _IconBtn(icon: CupertinoIcons.chat_bubble_2, onTap: () => go(ctx, const ChatsScreen()))
                    else
                      TextButton(onPressed: () => ensureLogin(ctx), child: Text('دخول', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700))),
                  ]),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(18, 10, 18, 0),
                  child: Pressable(
                    onTap: () => tab.value = 1,
                    child: Container(
                      height: 48,
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(color: C.bgAlt, borderRadius: BorderRadius.circular(14), border: Border.all(color: C.line)),
                      child: Row(children: [
                        Icon(CupertinoIcons.search, size: 19, color: C.text3),
                        const SizedBox(width: 10),
                        Text('ابحث عن كورس أو مهارة', style: TextStyle(color: C.text3, fontSize: 14.5)),
                      ]),
                    ),
                  ),
                ),
                if (cats.isNotEmpty)
                  SizedBox(
                    height: 52,
                    child: ListView.separated(
                      padding: const EdgeInsets.fromLTRB(18, 14, 18, 0),
                      scrollDirection: Axis.horizontal,
                      itemCount: cats.length + 1,
                      separatorBuilder: (_, __) => const SizedBox(width: 8),
                      itemBuilder: (_, i) => _Chip(
                        label: i == 0 ? 'الكل' : cats[i - 1]['name'] ?? '',
                        on: i == 0,
                        onTap: () => i == 0 ? tab.value = 1 : go(ctx, CoursesScreen(category: cats[i - 1])),
                      ),
                    ),
                  ),
                if (cont != null) ...[
                  const _SecHead(title: 'كمل التعلم'),
                  Padding(padding: const EdgeInsets.symmetric(horizontal: 18), child: ContinueCard(data: cont)),
                ],
                Padding(padding: const EdgeInsets.fromLTRB(18, 20, 18, 0), child: _Banner(onTap: () => tab.value = 1)),
                if (popular.isNotEmpty) ...[
                  _SecHead(title: 'الأكثر تسجيلا', action: 'عرض الكل', onAction: () => tab.value = 1),
                  if (kDesktop)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      child: AutoGrid(minItem: 240, children: [for (final c in popular) CourseCard(c, onTap: () => openCourse(ctx, c))]),
                    )
                  else
                    SizedBox(
                      height: 292,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 18),
                        scrollDirection: Axis.horizontal,
                        itemCount: popular.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 14),
                        itemBuilder: (_, i) => CourseCard(popular[i], width: 240, onTap: () => openCourse(ctx, popular[i])),
                      ),
                    ),
                ],
                if (latest.isNotEmpty) ...[
                  _SecHead(title: 'جديد بالأكاديمية', action: 'عرض الكل', onAction: () => tab.value = 1),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 18),
                    child: kDesktop
                        ? AutoGrid(minItem: 380, runGap: 0, children: [for (final c in latest.take(6)) CourseRow(c, onTap: () => openCourse(ctx, c))])
                        : Column(children: [for (final c in latest.take(6)) CourseRow(c, onTap: () => openCourse(ctx, c))]),
                  ),
                ],
              ]);
            },
          ),
        ),
      ),
    );
  }
}

class _IconBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _IconBtn({required this.icon, required this.onTap});
  @override
  Widget build(BuildContext context) => Pressable(
        onTap: onTap,
        child: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: C.line)),
          child: Icon(icon, size: 21, color: C.text),
        ),
      );
}

class _Chip extends StatelessWidget {
  final String label;
  final bool on;
  final VoidCallback onTap;
  const _Chip({required this.label, required this.on, required this.onTap});
  @override
  Widget build(BuildContext context) => Pressable(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 15),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: on ? C.text : Colors.transparent,
            borderRadius: BorderRadius.circular(99),
            border: Border.all(color: on ? C.text : C.line),
          ),
          child: Text(label, style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w500, color: on ? C.bg : C.text)),
        ),
      );
}

class _SecHead extends StatelessWidget {
  final String title;
  final String? action;
  final VoidCallback? onAction;
  const _SecHead({required this.title, this.action, this.onAction});
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(18, 26, 18, 10),
        child: Row(children: [
          Expanded(child: Text(title, style: head(19))),
          if (action != null)
            GestureDetector(onTap: onAction, child: Text(action!, style: TextStyle(color: C.accent, fontWeight: FontWeight.w700, fontSize: 13.5))),
        ]),
      );
}

/// بطاقة «كمل التعلم»: آخر درس فتحه الطالب ونسبة تقدمه
class ContinueCard extends StatelessWidget {
  final Map<String, dynamic> data;
  const ContinueCard({super.key, required this.data});
  @override
  Widget build(BuildContext context) {
    final c = asMap(data['course']);
    final l = asMap(data['lesson']);
    final p = ((data['progress'] ?? 0) as num).toDouble();
    return Pressable(
      onTap: () => goRoot(context, PlayerScreen(lessonId: l['id'] as int, title: l['title'] ?? '')),
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(color: C.card, borderRadius: BorderRadius.circular(16), border: Border.all(color: C.line)),
        child: Row(children: [
          SizedBox(width: 88, height: 62, child: Cover(c, radius: 10, thumb: true)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(c['title'] ?? '', style: head(14.5), maxLines: 1, overflow: TextOverflow.ellipsis),
              Text(l['title'] ?? '', style: TextStyle(color: C.text3, fontSize: 12.5), maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 7),
              ClipRRect(borderRadius: BorderRadius.circular(5), child: LinearProgressIndicator(value: p / 100, minHeight: 5, backgroundColor: C.mist, color: C.primary)),
            ]),
          ),
          const SizedBox(width: 12),
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(color: C.primary, shape: BoxShape.circle),
            child: const Icon(CupertinoIcons.play_fill, color: Colors.white, size: 18),
          ),
        ]),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  final VoidCallback onTap;
  const _Banner({required this.onTap});
  @override
  Widget build(BuildContext context) {
    return Pressable(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(20),
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(color: C.violet, borderRadius: BorderRadius.circular(18)),
        child: Stack(clipBehavior: Clip.none, children: [
          Positioned(
            left: -70,
            bottom: -90,
            child: Container(width: 200, height: 200, decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.white.withValues(alpha: .08), width: 28))),
          ),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
              decoration: BoxDecoration(color: C.gold, borderRadius: BorderRadius.circular(6)),
              child: const Text('غني أكاديمي', style: TextStyle(color: C.night, fontWeight: FontWeight.w700, fontSize: 12)),
            ),
            const SizedBox(height: 10),
            Text('ابدأ مهنتك الجديدة خلال 4 أسابيع', style: head(20, color: Colors.white)),
            const SizedBox(height: 4),
            const Text('12 جلسة عملية، مشروع حقيقي، وشهادة إتمام.', style: TextStyle(color: Color(0xFFDDD4FF), fontSize: 13.5)),
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)),
              child: const Text('تصفح الكورسات', style: TextStyle(color: C.night, fontWeight: FontWeight.w700)),
            ),
          ]),
        ]),
      ),
    );
  }
}

/// البحث وقائمة الكورسات (تبويب «بحث» أو قسم معين)
class CoursesScreen extends StatefulWidget {
  final Map? category;
  final bool tabRoot;
  const CoursesScreen({super.key, this.category, this.tabRoot = false});
  @override
  State<CoursesScreen> createState() => _CoursesScreenState();
}

class _CoursesScreenState extends State<CoursesScreen> {
  String q = '';
  Key k = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final slug = widget.category?['slug'];
    final body = Column(children: [
      if (widget.tabRoot)
        Padding(padding: const EdgeInsets.fromLTRB(18, 10, 18, 4), child: Align(alignment: AlignmentDirectional.centerStart, child: Text('بحث', style: head(26)))),
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
        child: CupertinoSearchTextField(
          placeholder: 'ابحث عن كورس أو مهارة',
          style: TextStyle(color: C.text),
          backgroundColor: C.bgAlt,
          borderRadius: BorderRadius.circular(14),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
          onSubmitted: (v) => setState(() {
            q = v.trim();
            k = UniqueKey();
          }),
        ),
      ),
      Expanded(
        child: Loader<List<Map<String, dynamic>>>(
          key: k,
          cache: q.isEmpty ? 'courses:${slug ?? ''}' : null,
          skeleton: const SkeletonList(header: false),
          load: () async => asList((await Api.i.req('GET', '/courses?${slug != null ? 'category=$slug&' : ''}q=${Uri.encodeQueryComponent(q)}'))['data']),
          builder: (ctx, list, _) => list.isEmpty
              ? ListView(padding: const EdgeInsets.all(20), children: const [
                  EmptyState(icon: CupertinoIcons.search, title: 'ما لقينا كورسات', text: 'جرب كلمة ثانية أو تصفح كل الأقسام.'),
                ])
              : kDesktop
                  ? ListView(padding: const EdgeInsets.all(20), children: [
                      AutoGrid(minItem: 250, children: [for (final c in list) CourseCard(c, onTap: () => openCourse(ctx, c))]),
                    ])
                  : ListView(padding: const EdgeInsets.fromLTRB(18, 0, 18, 20), children: [for (final c in list) CourseRow(c, onTap: () => openCourse(ctx, c))]),
        ),
      ),
    ]);
    return Scaffold(
      appBar: widget.tabRoot ? null : AppBar(title: Text(widget.category?['name'] ?? 'الكورسات')),
      body: widget.tabRoot ? SafeArea(bottom: false, child: body) : body,
    );
  }
}

/// صفحة الكورس: فيديو المقدمة، شنو راح تتعلم، يشمل، المحتوى، المدرب، آراء الطلاب، وزر تسجيل ثابت
class CourseScreen extends StatefulWidget {
  final String slug;
  const CourseScreen({super.key, required this.slug});
  @override
  State<CourseScreen> createState() => _CourseScreenState();
}

class _CourseScreenState extends State<CourseScreen> {
  Key k = UniqueKey();
  final open = <int>{0};

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
          CupertinoDialogAction(isDefaultAction: true, onPressed: () => Navigator.pop(dctx, true), child: const Text('سجل')),
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
      if (e.code == 'insufficient_balance' && !Api.storeBuild) go(context, const WalletScreen());
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

  Future<void> writeReview(Map<String, dynamic> c) async {
    final mine = c['my_review'] == null ? null : asMap(c['my_review']);
    var rating = (mine?['rating'] ?? 5) as int;
    final body = TextEditingController(text: mine?['body'] ?? '');
    final sent = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: C.card,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (sctx) => StatefulBuilder(
        builder: (sctx, set) => Padding(
          padding: EdgeInsets.fromLTRB(20, 18, 20, 20 + MediaQuery.of(sctx).viewInsets.bottom),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text(mine == null ? 'قيم الكورس' : 'عدل تقييمك', style: head(19)),
            const SizedBox(height: 12),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                for (var i = 1; i <= 5; i++)
                  IconButton(
                    onPressed: () => set(() => rating = i),
                    icon: Icon(i <= rating ? CupertinoIcons.star_fill : CupertinoIcons.star, color: C.star, size: 34),
                  ),
              ]),
            ),
            const SizedBox(height: 8),
            TextField(controller: body, maxLines: 3, maxLength: 1000, decoration: const InputDecoration(hintText: 'شنو عجبك بالكورس؟ (اختياري)')),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: () async {
                try {
                  await Api.i.req('POST', '/courses/${widget.slug}/review', body: {'rating': rating, 'body': body.text.trim()});
                  if (sctx.mounted) Navigator.pop(sctx, true);
                } catch (e) {
                  if (sctx.mounted) toast(sctx, err(e), error: true);
                }
              },
              child: const Text('إرسال التقييم'),
            ),
          ]),
        ),
      ),
    );
    if (sent == true && mounted) {
      toast(context, 'شكرا. تقييمك وصل.');
      setState(() => k = UniqueKey());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Loader<Map<String, dynamic>>(
        key: k,
        cache: 'course:${widget.slug}',
        skeleton: const _CourseSkeleton(),
        load: () async => asMap((await Api.i.req('GET', '/courses/${widget.slug}'))['data']),
        builder: (ctx, c, _) {
          final sections = asList(c['sections']);
          final enrolled = c['is_enrolled'] == true;
          final outcomes = (c['outcomes'] as List? ?? []).cast<String>();
          final inst = c['instructor'] == null ? null : asMap(c['instructor']);
          final reviews = asList(c['reviews']);
          final lessons = [for (final s in sections) ...asList(s['lessons'])];
          final preview = lessons.where((l) => l['is_free_preview'] == true).firstOrNull;
          final students = (c['students_count'] ?? 0) as num;
          final total = (c['total_duration'] ?? 0) as num;
          final top = MediaQuery.of(ctx).padding.top;

          return Stack(children: [
            ListView(padding: const EdgeInsets.only(bottom: 130), children: [
              // فيديو المقدمة / الغلاف
              AspectRatio(
                aspectRatio: 16 / 9,
                child: Stack(fit: StackFit.expand, children: [
                  Cover(c),
                  if (preview != null || enrolled)
                    GestureDetector(
                      onTap: () => enrolled
                          ? go(ctx, CurriculumScreen(slug: widget.slug, title: c['title'] ?? ''))
                          : goRoot(ctx, PlayerScreen(lessonId: preview!['id'] as int, title: preview['title'] ?? '')),
                      child: Container(
                        color: Colors.black.withValues(alpha: .28),
                        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Container(
                            width: 62,
                            height: 62,
                            decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                            child: const Icon(CupertinoIcons.play_fill, color: Color(0xFF1C1D1F), size: 26),
                          ),
                          const SizedBox(height: 8),
                          Text(enrolled ? 'كمل التعلم' : 'شاهد درس المعاينة',
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, shadows: [Shadow(color: Colors.black54, blurRadius: 6)])),
                        ]),
                      ),
                    ),
                ]),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 0),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text((c['category'] as Map?)?['name'] ?? '', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700, fontSize: 12.5)),
                  const SizedBox(height: 2),
                  Text(c['title'] ?? '', style: head(23)),
                  if (c['subtitle'] != null) ...[
                    const SizedBox(height: 6),
                    Text(c['subtitle'], style: TextStyle(color: C.text2, fontSize: 14.5, height: 1.6)),
                  ],
                  const SizedBox(height: 10),
                  Wrap(spacing: 14, runSpacing: 6, crossAxisAlignment: WrapCrossAlignment.center, children: [
                    Stars(c),
                    if ((c['rating_count'] ?? 0) > 0) _muted('${c['rating_count']} تقييم'),
                    if (students > 0) _muted('${iqd(students)} طالب'),
                  ]),
                  if (inst != null) ...[
                    const SizedBox(height: 10),
                    Row(children: [
                      CircleAvatar(radius: 17, backgroundColor: C.primary, child: Text((inst['name'] ?? ' ').toString().substring(0, 1), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700))),
                      const SizedBox(width: 10),
                      Text('المدرب: ', style: TextStyle(color: C.text2, fontSize: 13.5)),
                      Flexible(child: Text(inst['name'] ?? '', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700, fontSize: 13.5), overflow: TextOverflow.ellipsis)),
                    ]),
                  ],
                  const SizedBox(height: 8),
                  Wrap(spacing: 14, children: [
                    if (c['updated_at'] != null) _muted('آخر تحديث ${c['updated_at']}'),
                    _muted(c['level_label'] ?? ''),
                    _muted('عربي'),
                  ]),
                ]),
              ),
              if (outcomes.isNotEmpty)
                _Box(title: 'شنو راح تتعلم', children: [
                  for (final o in outcomes)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 5),
                      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Padding(padding: const EdgeInsets.only(top: 3), child: Icon(CupertinoIcons.checkmark_alt, size: 18, color: C.text)),
                        const SizedBox(width: 10),
                        Expanded(child: Text(o, style: const TextStyle(fontSize: 14, height: 1.55))),
                      ]),
                    ),
                ]),
              _Box(title: 'الكورس يشمل', children: [
                Wrap(runSpacing: 10, children: [
                  if (total > 0) _inc(CupertinoIcons.time, '${(total / 3600).ceil()} ساعة فيديو'),
                  _inc(CupertinoIcons.play_rectangle, '${c['lessons_count'] ?? lessons.length} درس'),
                  if (c['has_certificate'] == true) _inc(CupertinoIcons.rosette, 'شهادة إتمام'),
                  _inc(CupertinoIcons.device_laptop, 'موبايل وكمبيوتر'),
                ]),
              ]),
              _Box(
                title: 'محتوى الكورس',
                sub: '${sections.length} أقسام · ${lessons.length} درس',
                children: [
                  for (var i = 0; i < sections.length; i++) _section(ctx, i, sections[i], enrolled),
                ],
              ),
              if (inst != null)
                _Box(title: 'المدرب', children: [
                  Row(children: [
                    CircleAvatar(
                      radius: 30,
                      backgroundColor: C.primary,
                      backgroundImage: inst['photo'] != null ? NetworkImage(inst['photo']) : null,
                      child: inst['photo'] == null ? Text((inst['name'] ?? ' ').toString().substring(0, 1), style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700)) : null,
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(inst['name'] ?? '', style: head(16.5, color: C.accent)),
                        if (inst['title'] != null) Text(inst['title'], style: TextStyle(color: C.text3, fontSize: 13)),
                      ]),
                    ),
                  ]),
                  const SizedBox(height: 12),
                  Wrap(spacing: 18, children: [
                    if ((c['rating_count'] ?? 0) > 0) _muted('${c['rating']} تقييم'),
                    if (students > 0) _muted('${iqd(students)} طالب'),
                  ]),
                  if ((inst['bio'] ?? '').toString().isNotEmpty) ...[
                    const SizedBox(height: 10),
                    Text(inst['bio'], style: TextStyle(color: C.text2, fontSize: 14, height: 1.75)),
                  ],
                  if (enrolled && inst['can_chat'] == true) ...[
                    const SizedBox(height: 12),
                    OutlinedButton.icon(onPressed: askInstructor, icon: const Icon(CupertinoIcons.chat_bubble_text, size: 18), label: const Text('راسل المدرب')),
                  ],
                ]),
              _Box(title: 'آراء الطلاب', children: [
                if ((c['rating_count'] ?? 0) > 0) ...[
                  _summary(c),
                  for (final r in reviews) _review(r),
                ] else
                  Text('هذا كورس جديد. أول الطلاب يكتبون آرائهم بعد ما يكملون ${c['review_min_progress'] ?? 30}% منه.', style: TextStyle(color: C.text2, fontSize: 13.5, height: 1.6)),
                if (c['can_review'] == true) ...[
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () => writeReview(c),
                    icon: const Icon(CupertinoIcons.star, size: 18),
                    label: Text(c['my_review'] == null ? 'قيم الكورس' : 'عدل تقييمك'),
                  ),
                ] else if (enrolled) ...[
                  const SizedBox(height: 10),
                  Text('تقدر تقيم الكورس بعد ما تكمل ${c['review_min_progress'] ?? 30}% منه.', style: TextStyle(color: C.text3, fontSize: 12.5)),
                ],
              ]),
              if ((c['description'] ?? '').toString().isNotEmpty)
                _Box(title: 'عن الكورس', children: [Text(c['description'], style: TextStyle(color: C.text2, height: 1.8))]),
            ]),
            // زر الرجوع فوق الفيديو
            Positioned(
              top: top + 8,
              right: 14,
              child: GestureDetector(
                onTap: () => Navigator.of(ctx).maybePop(),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(color: Colors.black.withValues(alpha: .45), shape: BoxShape.circle),
                  child: const Icon(CupertinoIcons.chevron_right, color: Colors.white, size: 20),
                ),
              ),
            ),
            // شريط التسجيل الثابت
            Positioned(left: 0, right: 0, bottom: 0, child: _buyBar(ctx, c, enrolled)),
          ]);
        },
      ),
    );
  }

  Widget _buyBar(BuildContext ctx, Map<String, dynamic> c, bool enrolled) {
    final Widget child;
    if (enrolled) {
      child = FilledButton(onPressed: () => go(ctx, CurriculumScreen(slug: widget.slug, title: c['title'] ?? '')), child: const Text('كمل التعلم'));
    } else if (Api.storeBuild) {
      child = Api.i.loggedIn.value
          ? Text('هذا الكورس غير مفعل لحسابك حاليا.', textAlign: TextAlign.center, style: TextStyle(color: C.text2))
          : FilledButton(
              onPressed: () async {
                if (await ensureLogin(ctx)) setState(() => k = UniqueKey());
              },
              child: const Text('سجل دخولك'));
    } else {
      child = Row(children: [
        Expanded(
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            PriceText(c, size: 20),
            Text('وصول كامل + شهادة', style: TextStyle(color: C.text3, fontSize: 12)),
          ]),
        ),
        SizedBox(width: 170, child: FilledButton(onPressed: () => enroll(c), child: const Text('سجل الآن'))),
      ]);
    }
    return Container(
      padding: EdgeInsets.fromLTRB(18, 12, 18, 12 + MediaQuery.of(ctx).padding.bottom),
      decoration: BoxDecoration(
        color: C.card,
        border: Border(top: BorderSide(color: C.line)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .05), blurRadius: 20, offset: const Offset(0, -6))],
      ),
      child: child,
    );
  }

  Widget _section(BuildContext ctx, int i, Map<String, dynamic> s, bool enrolled) {
    final ls = asList(s['lessons']);
    final isOpen = open.contains(i);
    return Container(
      decoration: BoxDecoration(border: i == 0 ? null : Border(top: BorderSide(color: C.line))),
      child: Column(children: [
        InkWell(
          onTap: () => setState(() => isOpen ? open.remove(i) : open.add(i)),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Row(children: [
              Text('${i + 1}', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700)),
              const SizedBox(width: 8),
              Expanded(child: Text(s['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5))),
              Text('${ls.length} دروس', style: TextStyle(color: C.text3, fontSize: 12.5)),
              const SizedBox(width: 6),
              Icon(isOpen ? CupertinoIcons.chevron_up : CupertinoIcons.chevron_down, size: 15, color: C.text3),
            ]),
          ),
        ),
        if (isOpen)
          for (final l in ls)
            InkWell(
              onTap: (enrolled || l['is_free_preview'] == true) ? () => goRoot(ctx, PlayerScreen(lessonId: l['id'] as int, title: l['title'] ?? '')) : null,
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 7),
                child: Row(children: [
                  Icon(enrolled || l['is_free_preview'] == true ? CupertinoIcons.play_circle : CupertinoIcons.lock, size: 17, color: C.text3),
                  const SizedBox(width: 10),
                  Expanded(child: Text(l['title'] ?? '', style: TextStyle(fontSize: 13.5, color: C.text2))),
                  if (l['is_free_preview'] == true && !enrolled)
                    Padding(padding: const EdgeInsets.only(left: 8), child: Text('معاينة', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700, fontSize: 12))),
                  Text(mmss(l['duration']), textDirection: TextDirection.ltr, style: TextStyle(color: C.text3, fontSize: 12)),
                ]),
              ),
            ),
      ]),
    );
  }

  Widget _summary(Map<String, dynamic> c) {
    final b = asList(c['rating_breakdown']);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(children: [
        Column(children: [
          Text('${c['rating']}', style: head(40, color: C.star)),
          Row(mainAxisSize: MainAxisSize.min, children: [
            for (var i = 1; i <= 5; i++) Icon(CupertinoIcons.star_fill, size: 12, color: i <= ((c['rating'] ?? 0) as num).round() ? C.star : C.line),
          ]),
        ]),
        const SizedBox(width: 18),
        Expanded(
          child: Column(children: [
            for (final x in b)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 2),
                child: Row(children: [
                  Expanded(child: ClipRRect(borderRadius: BorderRadius.circular(5), child: LinearProgressIndicator(value: ((x['percent'] ?? 0) as num) / 100, minHeight: 7, backgroundColor: C.mist, color: C.star))),
                  const SizedBox(width: 8),
                  SizedBox(width: 62, child: Text('${x['stars']}★ ${x['percent']}%', textDirection: TextDirection.ltr, style: TextStyle(color: C.text3, fontSize: 11.5))),
                ]),
              ),
          ]),
        ),
      ]),
    );
  }

  Widget _review(Map<String, dynamic> r) => Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(border: Border(top: BorderSide(color: C.line))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(r['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700))),
            for (var i = 1; i <= 5; i++) Icon(CupertinoIcons.star_fill, size: 12, color: i <= (r['rating'] ?? 0) ? C.star : C.line),
          ]),
          if ((r['body'] ?? '').toString().isNotEmpty) Padding(padding: const EdgeInsets.only(top: 4), child: Text(r['body'], style: TextStyle(color: C.text2, height: 1.6))),
          if (r['date'] != null) Text(r['date'], style: TextStyle(color: C.text3, fontSize: 11.5)),
        ]),
      );

  Widget _muted(String t) => Text(t, style: TextStyle(color: C.text3, fontSize: 13));

  Widget _inc(IconData i, String t) => SizedBox(
        width: 160,
        child: Row(children: [
          Icon(i, size: 18, color: C.accent),
          const SizedBox(width: 8),
          Flexible(child: Text(t, style: const TextStyle(fontSize: 13.5))),
        ]),
      );
}

class _Box extends StatelessWidget {
  final String title;
  final String? sub;
  final List<Widget> children;
  const _Box({required this.title, this.sub, required this.children});
  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.fromLTRB(18, 18, 18, 0),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: C.card, borderRadius: BorderRadius.circular(16), border: Border.all(color: C.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title, style: head(17)),
          if (sub != null) Text(sub!, style: TextStyle(color: C.text3, fontSize: 12.5)),
          const SizedBox(height: 10),
          ...children,
        ]),
      );
}

class _CourseSkeleton extends StatelessWidget {
  const _CourseSkeleton();
  @override
  Widget build(BuildContext context) => ListView(physics: const NeverScrollableScrollPhysics(), padding: EdgeInsets.zero, children: const [
        AspectRatio(aspectRatio: 16 / 9, child: Skel(r: 0)),
        Padding(
          padding: EdgeInsets.all(18),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Skel(w: 90, h: 12),
            SizedBox(height: 10),
            Skel(h: 22),
            SizedBox(height: 8),
            Skel(w: 220, h: 22),
            SizedBox(height: 14),
            Skel(h: 13),
            SizedBox(height: 6),
            Skel(w: 260, h: 13),
            SizedBox(height: 22),
            Skel(h: 150, r: 16),
            SizedBox(height: 16),
            Skel(h: 110, r: 16),
          ]),
        ),
      ]);
}
