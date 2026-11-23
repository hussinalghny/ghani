import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../api.dart';
import '../ui.dart';

/// قائمة المحادثات: الدعم + مدربي الكورسات
class ChatsScreen extends StatefulWidget {
  const ChatsScreen({super.key});
  @override
  State<ChatsScreen> createState() => _ChatsScreenState();
}

class _ChatsScreenState extends State<ChatsScreen> {
  Key k = UniqueKey();

  Future<void> openSupport() async {
    try {
      dynamic r;
      await withBusy(context, () async => r = await Api.i.req('POST', '/chats/support'));
      if (!mounted) return;
      final c = asMap(r['data']);
      await goRoot(context, ChatScreen(id: c['id'] as int, title: c['title'] ?? 'الدعم', subtitle: c['subtitle']));
      if (mounted) setState(() => k = UniqueKey());
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: Navigator.of(context).canPop() ? AppBar() : null,
      body: SafeArea(
        top: !Navigator.of(context).canPop(),
        child: Loader<Map<String, dynamic>>(
          key: k,
          load: () async => asMap(await Api.i.req('GET', '/chats')),
          builder: (ctx, d, _) {
            final list = asList(d['data']);
            return ListView(padding: const EdgeInsets.fromLTRB(20, 20, 20, 24), children: [
              Row(children: [
                const Expanded(child: Title2('الرسائل', size: 30)),
                FilledButton.icon(
                  style: FilledButton.styleFrom(minimumSize: const Size(0, 42), padding: const EdgeInsets.symmetric(horizontal: 16)),
                  onPressed: openSupport,
                  icon: const Icon(CupertinoIcons.headphones, size: 18),
                  label: const Text('الدعم'),
                ),
              ]),
              const SizedBox(height: 16),
              if (list.isEmpty)
                EmptyState(
                  icon: CupertinoIcons.chat_bubble_2,
                  title: 'ما عندك محادثات بعد',
                  text: 'راسل الدعم لأي سؤال، أو افتح كورسك واضغط «اسأل» حتى تراسل المدرب.',
                  action: FilledButton(onPressed: openSupport, child: const Text('راسل الدعم')),
                ),
              for (var i = 0; i < list.length; i++)
                FadeSlide(
                  index: i,
                  child: Pressable(
                    onTap: () async {
                      final c = list[i];
                      await goRoot(ctx, ChatScreen(id: c['id'] as int, title: c['title'] ?? '', subtitle: c['subtitle']));
                      if (mounted) setState(() => k = UniqueKey());
                    },
                    child: Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(14),
                      decoration: cardBox(radius: 18),
                      child: Row(children: [
                        Container(
                          width: 46,
                          height: 46,
                          decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(14)),
                          child: Icon(list[i]['type'] == 'instructor' ? CupertinoIcons.person_crop_circle_badge_checkmark : CupertinoIcons.headphones, color: C.accent),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(list[i]['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15.5)),
                            const SizedBox(height: 2),
                            Text(list[i]['last'] ?? list[i]['subtitle'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: C.text3, fontSize: 13)),
                          ]),
                        ),
                        if ((list[i]['unread'] ?? 0) > 0)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(color: C.gold, borderRadius: BorderRadius.circular(99)),
                            child: Text('${list[i]['unread']}', style: const TextStyle(color: C.night, fontWeight: FontWeight.w700, fontSize: 12)),
                          ),
                      ]),
                    ),
                  ),
                ),
            ]);
          },
        ),
      ),
    );
  }
}

/// محادثة مباشرة: تتحدث تلقائيا كل 4 ثواني
class ChatScreen extends StatefulWidget {
  final int id;
  final String title;
  final String? subtitle;
  const ChatScreen({super.key, required this.id, required this.title, this.subtitle});
  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final msgs = <Map<String, dynamic>>[];
  final input = TextEditingController();
  final scroll = ScrollController();
  Timer? timer;
  bool loading = true;
  bool sending = false;

  int get lastId => msgs.isEmpty ? 0 : msgs.last['id'] as int;

  @override
  void initState() {
    super.initState();
    fetch();
    timer = Timer.periodic(const Duration(seconds: 4), (_) => fetch());
  }

  @override
  void dispose() {
    timer?.cancel();
    input.dispose();
    scroll.dispose();
    super.dispose();
  }

  Future<void> fetch() async {
    try {
      final r = await Api.i.req('GET', '/chats/${widget.id}/messages?after=$lastId');
      final list = asList(r['data']);
      if (!mounted) return;
      if (list.isNotEmpty) {
        setState(() {
          for (final m in list) {
            if (!msgs.any((x) => x['id'] == m['id'])) msgs.add(m);
          }
        });
        _bottom();
      }
    } catch (_) {}
    if (mounted && loading) setState(() => loading = false);
  }

  void _bottom() => WidgetsBinding.instance.addPostFrameCallback((_) {
        if (scroll.hasClients) scroll.animateTo(0, duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
      });

  Future<void> send() async {
    final body = input.text.trim();
    if (body.isEmpty || sending) return;
    HapticFeedback.lightImpact();
    setState(() => sending = true);
    input.clear();
    try {
      final r = await Api.i.req('POST', '/chats/${widget.id}/messages', body: {'body': body});
      if (!mounted) return;
      final m = asMap(r['data']);
      setState(() {
        if (!msgs.any((x) => x['id'] == m['id'])) msgs.add(m);
      });
      _bottom();
    } catch (e) {
      if (mounted) {
        input.text = body;
        toast(context, err(e), error: true);
      }
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final items = msgs.reversed.toList();
    return Scaffold(
      appBar: AppBar(
        title: Column(children: [
          Text(widget.title, style: head(16)),
          if (widget.subtitle != null) Text(widget.subtitle!, style: TextStyle(fontSize: 12, color: C.text3, fontWeight: FontWeight.w400)),
        ]),
      ),
      body: Column(children: [
        Expanded(
          child: loading
              ? const Center(child: CupertinoActivityIndicator())
              : msgs.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Text('اكتب أول رسالة، ونرد عليك هنا.', textAlign: TextAlign.center, style: TextStyle(color: C.text3)),
                      ),
                    )
                  : ListView.builder(
                      controller: scroll,
                      reverse: true,
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      itemCount: items.length,
                      itemBuilder: (_, i) => _bubble(items[i]),
                    ),
        ),
        Container(
          padding: EdgeInsets.fromLTRB(12, 8, 12, 8 + MediaQuery.of(context).padding.bottom),
          decoration: BoxDecoration(color: C.bg, border: Border(top: BorderSide(color: C.line))),
          child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Expanded(
              child: TextField(
                controller: input,
                minLines: 1,
                maxLines: 5,
                textInputAction: TextInputAction.newline,
                decoration: InputDecoration(
                  hintText: 'اكتب رسالتك...',
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide(color: C.line)),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide(color: C.line)),
                  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide(color: C.primary)),
                ),
              ),
            ),
            const SizedBox(width: 8),
            AnimatedScale(
              scale: sending ? .9 : 1,
              duration: const Duration(milliseconds: 150),
              child: Material(
                color: C.primary,
                shape: const CircleBorder(),
                child: IconButton(
                  onPressed: send,
                  icon: Icon(CupertinoIcons.arrow_up, color: C.onPrimary),
                ),
              ),
            ),
          ]),
        ),
      ]),
    );
  }

  Widget _bubble(Map<String, dynamic> m) {
    final mine = m['mine'] == true;
    return FadeSlide(
      key: ValueKey(m['id']),
      child: Align(
        alignment: mine ? AlignmentDirectional.centerStart : AlignmentDirectional.centerEnd,
        child: Container(
          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * .78),
          margin: const EdgeInsets.symmetric(vertical: 4),
          child: Column(crossAxisAlignment: mine ? CrossAxisAlignment.start : CrossAxisAlignment.end, children: [
            if (!mine)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                child: Text(m['sender'] ?? '', style: TextStyle(fontSize: 11.5, color: C.accent, fontWeight: FontWeight.w600)),
              ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: mine ? C.primary : C.mist,
                borderRadius: BorderRadiusDirectional.only(
                  topStart: const Radius.circular(18),
                  topEnd: const Radius.circular(18),
                  bottomStart: Radius.circular(mine ? 6 : 18),
                  bottomEnd: Radius.circular(mine ? 18 : 6),
                ),
              ),
              child: Text(m['body'] ?? '', style: TextStyle(color: mine ? C.onPrimary : C.text, height: 1.55, fontSize: 15)),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              child: Text(m['time'] ?? '', style: TextStyle(fontSize: 10.5, color: C.text3)),
            ),
          ]),
        ),
      ),
    );
  }
}
