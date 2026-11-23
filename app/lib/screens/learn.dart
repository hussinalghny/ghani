import 'dart:async';
import 'dart:convert';
import 'dart:math';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../api.dart';
import '../desktop_guard.dart';
import '../ui.dart';

/// مساري: الكورسات اللي دتتعلمها، المكتملة، والشهادات
class MyCoursesScreen extends StatefulWidget {
  const MyCoursesScreen({super.key});
  @override
  State<MyCoursesScreen> createState() => _MyCoursesScreenState();
}

class _MyCoursesScreenState extends State<MyCoursesScreen> {
  int seg = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Loader<List<dynamic>>(
          cache: 'my',
          skeleton: const SkeletonList(),
          load: () async => [asList((await Api.i.req('GET', '/me/courses'))['data']), asList((await Api.i.req('GET', '/me/certificates'))['data'])],
          builder: (ctx, d, _) {
            final all = asList(d[0]);
            final certs = asList(d[1]);
            final going = all.where((e) => ((e['progress'] ?? 0) as num) < 100).toList();
            final done = all.where((e) => ((e['progress'] ?? 0) as num) >= 100).toList();
            final shown = seg == 0 ? going : done;
            return ListView(padding: const EdgeInsets.fromLTRB(18, 10, 18, 28), children: [
              Text('مساري', style: head(26)),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(border: Border(bottom: BorderSide(color: C.line))),
                child: Row(children: [
                  for (final (i, l) in [(0, 'قيد التعلم'), (1, 'مكتملة'), (2, 'الشهادات')])
                    GestureDetector(
                      onTap: () => setState(() => seg = i),
                      child: Container(
                        margin: const EdgeInsetsDirectional.only(end: 22),
                        padding: const EdgeInsets.only(bottom: 10),
                        decoration: BoxDecoration(border: Border(bottom: BorderSide(color: seg == i ? C.text : Colors.transparent, width: 2.5))),
                        child: Text(l, style: TextStyle(fontSize: 14.5, fontWeight: seg == i ? FontWeight.w700 : FontWeight.w500, color: seg == i ? C.text : C.text3)),
                      ),
                    ),
                ]),
              ),
              const SizedBox(height: 16),
              Row(children: [
                _stat('${all.length}', 'كورس'),
                const SizedBox(width: 10),
                _stat('${done.length}', 'مكتمل'),
                const SizedBox(width: 10),
                _stat('${certs.length}', 'شهادة'),
              ]),
              const SizedBox(height: 6),
              if (seg == 2) ...[
                if (certs.isEmpty) Padding(padding: const EdgeInsets.only(top: 20), child: Text('أكمل أي كورس وتصدر شهادتك هنا.', style: TextStyle(color: C.text3))),
                for (final c in certs)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Container(width: 44, height: 44, decoration: BoxDecoration(color: const Color(0xFFFFF2D6), borderRadius: BorderRadius.circular(12)), child: const Icon(CupertinoIcons.rosette, color: Color(0xFFB7790F))),
                    title: Text(c['course'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text(c['code'] ?? '', textDirection: TextDirection.ltr, textAlign: TextAlign.end, style: TextStyle(color: C.text3)),
                    trailing: Icon(CupertinoIcons.chevron_left, size: 16, color: C.text3),
                    onTap: () => launchUrl(Uri.parse(c['url']), mode: LaunchMode.externalApplication),
                  ),
              ] else ...[
                if (shown.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 16),
                    child: EmptyState(
                      icon: CupertinoIcons.play_circle,
                      title: seg == 0 ? 'ما عندك كورسات قيد التعلم' : 'ما كملت كورس بعد',
                      text: seg == 0 ? (Api.storeBuild ? 'كورساتك المفعلة على حسابك تظهر هنا.' : 'اختر كورسك الأول وابدأ اليوم.') : 'كمل كورساتك وتطلع هنا ويا شهادتها.',
                      action: seg == 0 ? FilledButton(onPressed: () => tab.value = 0, child: const Text('تصفح الكورسات')) : null,
                    ),
                  ),
                for (final e in shown) _item(ctx, e),
                if (seg == 0 && going.isNotEmpty && certs.isEmpty)
                  _hint(going.first),
              ],
            ]);
          },
        ),
      ),
    );
  }

  Widget _stat(String n, String l) => Expanded(
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(color: C.bgAlt, borderRadius: BorderRadius.circular(14)),
          child: Column(children: [Text(n, style: head(20)), Text(l, style: TextStyle(color: C.text3, fontSize: 12))]),
        ),
      );

  Widget _hint(Map<String, dynamic> e) {
    final c = asMap(e['course']);
    final total = (c['lessons_count'] ?? 0) as num;
    final left = (total - (total * ((e['progress'] ?? 0) as num) / 100)).ceil();
    if (c['has_certificate'] != true || left <= 0) return const SizedBox.shrink();
    return Container(
      margin: const EdgeInsets.only(top: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: C.mist.withValues(alpha: .5), borderRadius: BorderRadius.circular(16), border: Border.all(color: C.lavender)),
      child: Row(children: [
        Container(width: 44, height: 44, decoration: BoxDecoration(color: const Color(0xFFFFF2D6), borderRadius: BorderRadius.circular(12)), child: const Icon(CupertinoIcons.rosette, color: Color(0xFFB7790F))),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('شهادتك قريبة', style: TextStyle(fontWeight: FontWeight.w700)),
            Text('باقي $left دروس على شهادة ${c['title']}.', style: TextStyle(color: C.text2, fontSize: 13)),
          ]),
        ),
      ]),
    );
  }

  Widget _item(BuildContext ctx, Map<String, dynamic> e) {
    final c = asMap(e['course']);
    final p = (e['progress'] ?? 0) as num;
    return Pressable(
      onTap: () => go(ctx, CurriculumScreen(slug: c['slug'], title: c['title'] ?? '')),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(border: Border(bottom: BorderSide(color: C.line))),
        child: Row(children: [
          SizedBox(width: 120, height: 74, child: Cover(c, radius: 10, thumb: true)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(c['title'] ?? '', style: head(14.5), maxLines: 2, overflow: TextOverflow.ellipsis),
              Text((c['instructor'] as Map?)?['name'] ?? '', style: TextStyle(color: C.text3, fontSize: 12)),
              const SizedBox(height: 7),
              ClipRRect(borderRadius: BorderRadius.circular(5), child: LinearProgressIndicator(value: p / 100, minHeight: 5, backgroundColor: C.mist, color: p >= 100 ? C.success : C.primary)),
              const SizedBox(height: 5),
              Text(p >= 100 ? 'مكتمل' : (p > 0 ? 'مكتمل $p% · كمل' : 'ابدأ الكورس'),
                  style: TextStyle(color: p >= 100 ? C.success : C.accent, fontWeight: FontWeight.w700, fontSize: 12.5)),
            ]),
          ),
        ]),
      ),
    );
  }
}

class CurriculumScreen extends StatefulWidget {
  final String slug;
  final String title;
  const CurriculumScreen({super.key, required this.slug, required this.title});
  @override
  State<CurriculumScreen> createState() => _CurriculumScreenState();
}

class _CurriculumScreenState extends State<CurriculumScreen> {
  Key k = UniqueKey();
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: C.bgAlt,
      appBar: AppBar(title: Text(widget.title)),
      body: Loader<Map<String, dynamic>>(
        key: k,
        load: () async => asMap(await Api.i.req('GET', '/courses/${widget.slug}/curriculum')),
        builder: (ctx, d, _) {
          final p = (d['progress'] ?? 0) as num;
          return ListView(padding: const EdgeInsets.all(16), children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: cardBox(radius: 18),
              child: Column(children: [
                Row(children: [const Text('تقدمك', style: TextStyle(fontWeight: FontWeight.w600)), const Spacer(), Text('$p%')]),
                const SizedBox(height: 10),
                ClipRRect(borderRadius: BorderRadius.circular(99), child: TweenAnimationBuilder<double>(tween: Tween(begin: 0, end: p / 100), duration: const Duration(milliseconds: 700), curve: Curves.easeOutCubic, builder: (_, v, __) => LinearProgressIndicator(value: v, minHeight: 8, backgroundColor: C.mist, color: C.primary))),
              ]),
            ),
            for (final s in asList(d['sections'])) ...[
              Padding(padding: const EdgeInsets.fromLTRB(4, 20, 4, 8), child: Text(s['title'] ?? '', style: TextStyle(color: C.text3, fontWeight: FontWeight.w600, fontSize: 13))),
              Container(
                decoration: cardBox(radius: 18),
                clipBehavior: Clip.antiAlias,
                child: Column(children: [
                  for (final l in asList(s['lessons']))
                    ListTile(
                      leading: Container(
                        width: 26,
                        height: 26,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: l['completed'] == true ? C.success : Colors.transparent,
                          border: Border.all(color: l['completed'] == true ? C.success : C.line, width: 1.5),
                        ),
                        child: l['completed'] == true ? const Icon(Icons.check, size: 15, color: Colors.white) : null,
                      ),
                      title: Text(l['title'] ?? '', style: const TextStyle(fontSize: 15)),
                      subtitle: Text(mmss(l['duration']), style: TextStyle(color: C.text3, fontSize: 12.5)),
                      trailing: Icon(CupertinoIcons.chevron_left, size: 16, color: C.text3),
                      onTap: () async {
                        await goRoot(ctx, PlayerScreen(lessonId: l['id'] as int, title: l['title'] ?? ''));
                        if (mounted) setState(() => k = UniqueKey());
                      },
                    ),
                ]),
              ),
            ],
          ]);
        },
      ),
    );
  }
}

/// مشغل الدروس: فيديو Bunny داخل WebView + علامة مائية متحركة برقم المشترك
class PlayerScreen extends StatefulWidget {
  final int lessonId;
  final String title;
  const PlayerScreen({super.key, required this.lessonId, required this.title});
  @override
  State<PlayerScreen> createState() => _PlayerScreenState();
}

class _PlayerScreenState extends State<PlayerScreen> {
  Map<String, dynamic>? lesson;
  String? error;
  WebViewController? web;
  VideoPlayerController? vp;
  bool showCtl = true;
  Timer? hideT;
  bool full = false;
  bool done = false;
  int lastSent = 0;
  final playerKey = GlobalKey();
  Timer? guardT;
  String? recorder;

  @override
  void initState() {
    super.initState();
    load();
    if (DesktopGuard.isDesktop) {
      guardT = Timer.periodic(const Duration(seconds: 3), (_) async {
        final r = await DesktopGuard.recorder();
        if (!mounted) return;
        if (r != null) vp?.pause();
        if (r != recorder) setState(() => recorder = r);
      });
    }
  }

  @override
  void dispose() {
    vp?.removeListener(_onTick);
    vp?.dispose();
    hideT?.cancel();
    guardT?.cancel();
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    super.dispose();
  }

  Future<void> load() async {
    try {
      final d = asMap((await Api.i.req('GET', '/lessons/${widget.lessonId}'))['data']);
      final pb = asMap(d['playback']);
      final embed = pb['embed_url'] as String?;
      final local = pb['video_url'] as String?;
      if (local != null) {
        // المشغل المجاني: فيديو من سيرفر المنصة برابط موقع مؤقت
        // الرابط ما يشتغل بدون توكن الطالب بالهيدر، فنسخه ما يفيد
        final c = VideoPlayerController.networkUrl(Uri.parse(local), httpHeaders: Api.i.authHeaders, videoPlayerOptions: VideoPlayerOptions(allowBackgroundPlayback: false));
        await c.initialize();
        final start = (d['position'] ?? 0) as int;
        if (start > 10 && start < c.value.duration.inSeconds - 5) await c.seekTo(Duration(seconds: start - 3));
        c.addListener(_onTick);
        vp = c;
      } else if (embed != null) {
        final c = WebViewController()
          ..setJavaScriptMode(JavaScriptMode.unrestricted)
          ..setBackgroundColor(Colors.black)
          ..addJavaScriptChannel('Ghani', onMessageReceived: (m) => onPlayer(m.message))
          ..setNavigationDelegate(NavigationDelegate(
            // لا نسمح بالخروج من المشغل لأي رابط آخر
            onNavigationRequest: (r) => r.url.startsWith('https://iframe.mediadelivery.net') || r.url.startsWith(apiBase) || r.url == 'about:blank'
                ? NavigationDecision.navigate
                : NavigationDecision.prevent,
          ));
        // baseUrl = دومين المنصة حتى يقبل Bunny الـ Referer (Allowed Domains)
        await c.loadHtmlString(_html(embed, (d['position'] ?? 0) as int), baseUrl: '$apiBase/');
        web = c;
      }
      setState(() {
        lesson = d;
        done = d['completed'] == true;
      });
    } catch (e) {
      setState(() => error = err(e));
    }
  }

  String _html(String embed, int start) => '''
<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
<style>html,body{margin:0;height:100%;background:#000;overflow:hidden;-webkit-user-select:none;user-select:none}iframe{border:0;width:100%;height:100%}</style>
</head><body><iframe id="v" src="${const HtmlEscape().convert(embed)}" allow="autoplay; encrypted-media"></iframe>
<script src="https://assets.mediadelivery.net/playerjs/player-0.1.0.min.js"></script>
<script>
document.addEventListener('contextmenu',function(e){e.preventDefault()});
try{var p=new playerjs.Player(document.getElementById('v'));
p.on('ready',function(){ if($start>10)p.setCurrentTime(${start - 3});
 p.on('timeupdate',function(d){Ghani.postMessage(JSON.stringify({t:Math.floor(d.seconds||0)}))});
 p.on('ended',function(){Ghani.postMessage(JSON.stringify({ended:true}))});
});}catch(e){}
</script></body></html>''';

  void _onTick() {
    final v = vp?.value;
    if (v == null || !v.isInitialized) return;
    if (recorder != null && v.isPlaying) vp?.pause();
    final t = v.position.inSeconds;
    if ((t - lastSent).abs() >= 15) {
      lastSent = t;
      Api.i.req('POST', '/lessons/${widget.lessonId}/progress', body: {'position': t}).catchError((_) => null);
    }
    if (v.duration.inSeconds > 0 && v.position >= v.duration - const Duration(milliseconds: 600) && !v.isPlaying) markDone();
    if (mounted) setState(() {});
  }

  void _togglePlay() {
    final c = vp;
    if (c == null) return;
    c.value.isPlaying ? c.pause() : c.play();
    _poke();
  }

  void _poke() {
    setState(() => showCtl = true);
    hideT?.cancel();
    hideT = Timer(const Duration(seconds: 3), () {
      if (mounted && (vp?.value.isPlaying ?? false)) setState(() => showCtl = false);
    });
  }

  Widget _localPlayer() {
    final c = vp!;
    final v = c.value;
    final dur = v.duration.inMilliseconds.toDouble();
    final pos = v.position.inMilliseconds.toDouble().clamp(0, dur > 0 ? dur : 1).toDouble();
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: _poke,
      onDoubleTapDown: (d) {
        final w = context.size?.width ?? 400;
        final fwd = d.localPosition.dx < w / 2; // RTL: اليسار تقديم
        c.seekTo(v.position + Duration(seconds: fwd ? 10 : -10));
        _poke();
      },
      child: Stack(children: [
        Center(child: AspectRatio(aspectRatio: v.aspectRatio, child: VideoPlayer(c))),
        AnimatedOpacity(
          opacity: showCtl || !v.isPlaying ? 1 : 0,
          duration: const Duration(milliseconds: 250),
          child: IgnorePointer(
            ignoring: !(showCtl || !v.isPlaying),
            child: Container(
              decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Color(0x99000000)], stops: [.55, 1])),
              child: Stack(children: [
                Center(
                  child: GestureDetector(
                    onTap: _togglePlay,
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(color: Colors.white.withValues(alpha: .92), shape: BoxShape.circle),
                      child: Icon(v.isPlaying ? CupertinoIcons.pause_fill : CupertinoIcons.play_fill, color: C.primary, size: 28),
                    ),
                  ),
                ),
                Positioned(
                  left: 8,
                  right: 8,
                  bottom: 4,
                  child: Directionality(
                    textDirection: TextDirection.ltr,
                    child: Row(children: [
                      Text('${mmss(v.position.inSeconds)} / ${mmss(v.duration.inSeconds)}', style: const TextStyle(color: Colors.white, fontSize: 12)),
                      Expanded(
                        child: SliderTheme(
                          data: SliderTheme.of(context).copyWith(trackHeight: 3, thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 6), overlayShape: SliderComponentShape.noOverlay),
                          child: Slider(
                            value: pos,
                            max: dur > 0 ? dur : 1,
                            activeColor: C.gold,
                            inactiveColor: Colors.white30,
                            onChanged: (x) {
                              c.seekTo(Duration(milliseconds: x.round()));
                              _poke();
                            },
                          ),
                        ),
                      ),
                      PopupMenuButton<double>(
                        tooltip: 'السرعة',
                        initialValue: v.playbackSpeed,
                        onSelected: (x) => c.setPlaybackSpeed(x),
                        itemBuilder: (_) => [for (final x in [0.75, 1.0, 1.25, 1.5, 2.0]) PopupMenuItem(value: x, child: Text('${x}x'))],
                        child: Padding(padding: const EdgeInsets.symmetric(horizontal: 6), child: Text('${v.playbackSpeed}x', style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600))),
                      ),
                      IconButton(
                        onPressed: toggleFull,
                        icon: Icon(full ? CupertinoIcons.fullscreen_exit : CupertinoIcons.fullscreen, color: Colors.white, size: 20),
                      ),
                    ]),
                  ),
                ),
              ]),
            ),
          ),
        ),
        if (v.isBuffering) const Center(child: CupertinoActivityIndicator(color: Colors.white, radius: 14)),
      ]),
    );
  }

  void onPlayer(String msg) {
    try {
      final m = jsonDecode(msg) as Map;
      if (m['ended'] == true) {
        markDone();
      } else if (m['t'] is int && ((m['t'] as int) - lastSent).abs() >= 15) {
        lastSent = m['t'] as int;
        Api.i.req('POST', '/lessons/${widget.lessonId}/progress', body: {'position': lastSent}).catchError((_) => null);
      }
    } catch (_) {}
  }

  Future<void> markDone() async {
    if (done) return;
    try {
      final r = await Api.i.req('POST', '/lessons/${widget.lessonId}/progress', body: {'completed': true});
      if (!mounted) return;
      setState(() => done = true);
      if (r['certificate'] != null) {
        toast(context, 'أكملت الكورس. شهادتك جاهزة بصفحة حسابي.');
      }
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    }
  }

  void toggleFull() {
    setState(() => full = !full);
    if (full) {
      SystemChrome.setPreferredOrientations([DeviceOrientation.landscapeLeft, DeviceOrientation.landscapeRight]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    } else {
      SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
  }

  Widget player() {
    final wm = asMap(lesson?['playback'])['watermark'] as String?;
    return Container(
      key: playerKey,
      color: Colors.black,
      child: Stack(children: [
        if (vp != null)
          Positioned.fill(child: _localPlayer())
        else if (web != null)
          Positioned.fill(child: WebViewWidget(controller: web!))
        else if (asMap(lesson?['playback'])['provider'] == 'blocked')
          Positioned.fill(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  const Icon(CupertinoIcons.desktopcomputer, color: C.lavender, size: 34),
                  const SizedBox(height: 10),
                  Text(asMap(lesson?['playback'])['blocked_message'] ?? '', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, height: 1.7)),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => launchUrl(Uri.parse(asMap(lesson?['playback'])['download_url'] ?? '$apiBase/app'), mode: LaunchMode.externalApplication),
                    child: const Text('تنزيل التطبيق'),
                  ),
                ]),
              ),
            ),
          )
        else
          const Positioned.fill(child: Center(child: Text('الفيديو قيد التجهيز', style: TextStyle(color: C.lavender)))),
        if (wm != null) Positioned.fill(child: IgnorePointer(child: Watermark(text: wm))),
        if (recorder != null)
          Positioned.fill(
            child: Container(
              color: Colors.black,
              alignment: Alignment.center,
              padding: const EdgeInsets.all(24),
              child: Text('أغلق برنامج تسجيل الشاشة ($recorder) حتى يكمل الفيديو.', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 15, height: 1.7)),
            ),
          ),
        if (vp == null) Positioned(
          top: 8,
          left: 8,
          child: Material(
            color: Colors.black.withValues(alpha: .45),
            shape: const CircleBorder(),
            child: IconButton(
              onPressed: toggleFull,
              icon: Icon(full ? CupertinoIcons.fullscreen_exit : CupertinoIcons.fullscreen, color: Colors.white, size: 20),
            ),
          ),
        ),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (error != null) {
      return Scaffold(appBar: AppBar(title: Text(widget.title)), body: Center(child: Padding(padding: const EdgeInsets.all(28), child: Text(error!, textAlign: TextAlign.center))));
    }
    if (lesson == null) {
      return Scaffold(appBar: AppBar(title: Text(widget.title)), body: const Center(child: CupertinoActivityIndicator()));
    }
    if (full) {
      return PopScope(
        canPop: false,
        onPopInvokedWithResult: (_, __) => toggleFull(),
        child: Scaffold(backgroundColor: Colors.black, body: player()),
      );
    }
    final l = lesson!;
    final att = l['attachment'] as Map?;
    final isVideo = l['type'] == 'video';
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: ListView(padding: EdgeInsets.only(bottom: MediaQuery.of(context).padding.bottom + 20), children: [
        if (isVideo) AspectRatio(aspectRatio: 16 / 9, child: player()),
        Padding(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            if (l['index'] != null)
              Text('الدرس ${l['index']} من ${l['total']} · ${asMap(l['course'])['title'] ?? ''}', style: TextStyle(color: C.accent, fontSize: 12.5, fontWeight: FontWeight.w700)),
            const SizedBox(height: 2),
            Text(l['title'] ?? '', style: head(21)),
            const SizedBox(height: 14),
            if (l['body'] != null && (l['body'] as String).isNotEmpty)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                margin: const EdgeInsets.only(bottom: 14),
                decoration: cardBox(radius: 18),
                child: Text(l['body'], style: const TextStyle(height: 1.9, fontSize: 15.5)),
              ),
            if (att != null)
              ListTile(
                tileColor: C.card,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: C.line)),
                leading: Icon(CupertinoIcons.doc_text, color: C.accent),
                title: Text(att['name'] ?? 'ملف الدرس'),
                subtitle: const Text('الرابط صالح لمدة 10 دقائق', style: TextStyle(fontSize: 12)),
                trailing: Icon(CupertinoIcons.arrow_down_circle, color: C.accent),
                onTap: () => launchUrl(Uri.parse(att['url']), mode: LaunchMode.externalApplication),
              ),
            const SizedBox(height: 18),
            FilledButton.icon(
              onPressed: done ? null : markDone,
              icon: Icon(done ? CupertinoIcons.checkmark_alt_circle_fill : CupertinoIcons.checkmark_alt),
              label: Text(done ? 'مكتمل' : 'أكملت الدرس'),
            ),
            if (l['next'] != null) ...[
              const SizedBox(height: 14),
              Pressable(
                onTap: () => Navigator.of(context).pushReplacement(CupertinoPageRoute(
                    builder: (_) => PlayerScreen(lessonId: asMap(l['next'])['id'] as int, title: asMap(l['next'])['title'] ?? ''))),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: cardBox(radius: 16),
                  child: Row(children: [
                    Container(width: 42, height: 42, decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(12)), child: Icon(CupertinoIcons.forward_end_fill, color: C.accent, size: 18)),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('الدرس الجاي', style: TextStyle(color: C.text3, fontSize: 12)),
                        Text(asMap(l['next'])['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                      ]),
                    ),
                    Icon(CupertinoIcons.chevron_left, size: 16, color: C.text3),
                  ]),
                ),
              ),
            ],
            if (asMap(l['course'])['slug'] != null) ...[
              const SizedBox(height: 10),
              TextButton.icon(
                onPressed: () => go(context, CurriculumScreen(slug: asMap(l['course'])['slug'], title: asMap(l['course'])['title'] ?? '')),
                icon: const Icon(CupertinoIcons.list_bullet, size: 18),
                label: const Text('كل دروس الكورس'),
              ),
            ],
            if (l['can_ask'] == true) ...[
              const SizedBox(height: 18),
              LessonQA(lessonId: widget.lessonId),
            ],
            const SizedBox(height: 10),
            Row(children: [
              Icon(CupertinoIcons.lock_shield, size: 16, color: C.text3),
              const SizedBox(width: 6),
              Expanded(child: Text('المحتوى محمي. التصوير غير مسموح والفيديو معلم برقم حسابك.', style: TextStyle(color: C.text3, fontSize: 12.5))),
            ]),
          ]),
        ),
      ]),
    );
  }
}

/// أسئلة الدرس: الطالب يسأل والمدرب يجاوب (يظهر الأسئلة المجاوبة وأسئلتك)
class LessonQA extends StatefulWidget {
  final int lessonId;
  const LessonQA({super.key, required this.lessonId});
  @override
  State<LessonQA> createState() => _LessonQAState();
}

class _LessonQAState extends State<LessonQA> {
  List<Map<String, dynamic>>? list;
  final ctl = TextEditingController();
  bool sending = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    try {
      final d = await Api.i.req('GET', '/lessons/${widget.lessonId}/questions');
      if (mounted) setState(() => list = asList(d['data']));
    } catch (_) {
      if (mounted) setState(() => list = []);
    }
  }

  Future<void> send() async {
    final b = ctl.text.trim();
    if (b.length < 5) {
      toast(context, 'اكتب سؤالك بشكل أوضح.', error: true);
      return;
    }
    setState(() => sending = true);
    try {
      final r = await Api.i.req('POST', '/lessons/${widget.lessonId}/questions', body: {'body': b});
      ctl.clear();
      FocusScope.of(context).unfocus();
      if (mounted) {
        toast(context, r['message'] ?? 'وصل سؤالك.');
        setState(() => list = [asMap(r['data']), ...?list]);
      }
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Text('أسئلة الدرس', style: head(18)),
      const SizedBox(height: 10),
      TextField(controller: ctl, minLines: 1, maxLines: 4, maxLength: 1500, decoration: const InputDecoration(hintText: 'عندك سؤال عن هذا الدرس؟ اكتبه والمدرب يجاوبك', counterText: '')),
      const SizedBox(height: 8),
      Align(
        alignment: AlignmentDirectional.centerStart,
        child: FilledButton(
          style: FilledButton.styleFrom(minimumSize: const Size(140, 44)),
          onPressed: sending ? null : send,
          child: sending ? const CupertinoActivityIndicator(color: Colors.white) : const Text('إرسال السؤال'),
        ),
      ),
      const SizedBox(height: 6),
      if (list == null)
        const Padding(padding: EdgeInsets.all(16), child: CupertinoActivityIndicator())
      else if (list!.isEmpty)
        Padding(padding: const EdgeInsets.symmetric(vertical: 10), child: Text('ما أكو أسئلة بعد. كن أول من يسأل.', style: TextStyle(color: C.text3, fontSize: 13)))
      else
        for (final q in list!)
          Container(
            padding: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(border: Border(top: BorderSide(color: C.line))),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(child: Text(q['mine'] == true ? 'سؤالك' : (q['name'] ?? ''), style: const TextStyle(fontWeight: FontWeight.w700))),
                Text(q['date'] ?? '', style: TextStyle(color: C.text3, fontSize: 11.5)),
              ]),
              const SizedBox(height: 4),
              Text(q['body'] ?? '', style: const TextStyle(height: 1.6)),
              if (q['answer'] != null)
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(top: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(12)),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('جواب المدرب', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700, fontSize: 12.5)),
                    const SizedBox(height: 2),
                    Text(q['answer'], style: const TextStyle(height: 1.6)),
                  ]),
                )
              else
                Padding(padding: const EdgeInsets.only(top: 4), child: Text('بانتظار جواب المدرب', style: TextStyle(color: C.text3, fontSize: 12))),
            ]),
          ),
    ]);
  }
}

/// نص يتحرك فوق الفيديو كل 4 ثواني برقم واسم المشترك
class Watermark extends StatefulWidget {
  final String text;
  const Watermark({super.key, required this.text});
  @override
  State<Watermark> createState() => _WatermarkState();
}

class _WatermarkState extends State<Watermark> {
  final rnd = Random();
  Alignment pos = const Alignment(-.6, -.6);
  Timer? t;

  @override
  void initState() {
    super.initState();
    t = Timer.periodic(const Duration(seconds: 4), (_) {
      if (mounted) setState(() => pos = Alignment(rnd.nextDouble() * 1.8 - .9, rnd.nextDouble() * 1.8 - .9));
    });
  }

  @override
  void dispose() {
    t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedAlign(
      alignment: pos,
      duration: const Duration(milliseconds: 1200),
      curve: Curves.easeInOut,
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Text(
          widget.text,
          textDirection: TextDirection.ltr,
          style: TextStyle(
            color: Colors.white.withValues(alpha: .42),
            fontSize: 13,
            fontWeight: FontWeight.w600,
            shadows: const [Shadow(color: Colors.black38, blurRadius: 3)],
          ),
        ),
      ),
    );
  }
}
