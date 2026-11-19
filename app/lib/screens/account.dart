import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../ui.dart';
import 'auth.dart';
import 'chat.dart';
import 'home.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});
  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final code = TextEditingController();
  Key k = UniqueKey();

  Future<void> redeem() async {
    if (code.text.replaceAll('-', '').length < 8) {
      toast(context, 'اكتب كود القسيمة كاملا.', error: true);
      return;
    }
    FocusScope.of(context).unfocus();
    try {
      dynamic r;
      await withBusy(context, () async => r = await Api.i.req('POST', '/wallet/redeem', body: {'code': code.text}));
      if (!mounted) return;
      code.clear();
      toast(context, r['message'] ?? 'تم.');
      setState(() => k = UniqueKey());
      final c = r['course'];
      if (c is Map && c['slug'] != null) go(context, CourseScreen(slug: c['slug']));
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Loader<Map<String, dynamic>>(
          key: k,
          load: () async => asMap(await Api.i.req('GET', '/wallet')),
          builder: (ctx, d, _) => ListView(padding: const EdgeInsets.fromLTRB(20, 20, 20, 24), children: [
            const Title2('المحفظة', size: 30),
            const SizedBox(height: 16),
            Container(
              height: 190,
              padding: const EdgeInsets.all(22),
              decoration: patternBox(radius: 26, colors: const [Color(0xFF6A45E6), Color(0xFF2E1A86), C.night]).copyWith(
                boxShadow: [BoxShadow(color: C.deep.withValues(alpha: .35), blurRadius: 30, offset: const Offset(0, 14))],
              ),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  const Text('رصيدك الحالي', style: TextStyle(color: C.lavender)),
                  const Spacer(),
                  Image.asset('assets/img/logo-dark.png', height: 26),
                ]),
                const Spacer(),
                Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: [
                  TweenAnimationBuilder<double>(
                    tween: Tween(begin: 0, end: ((d['balance'] ?? 0) as num).toDouble()),
                    duration: const Duration(milliseconds: 800),
                    curve: Curves.easeOutCubic,
                    builder: (_, v, __) => Text(iqd(v), style: head(40, color: Colors.white, w: FontWeight.w800)),
                  ),
                  const SizedBox(width: 6),
                  const Text('د.ع', style: TextStyle(color: C.lavender, fontSize: 17)),
                ]),
                const Spacer(),
                Text(Api.i.user?['phone'] ?? '', textDirection: TextDirection.ltr, style: const TextStyle(color: C.lavender)),
              ]),
            ),
            const SizedBox(height: 18),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: cardBox(),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                Text('شحن بقسيمة', style: head(18)),
                const SizedBox(height: 12),
                TextField(
                  controller: code,
                  textAlign: TextAlign.center,
                  textDirection: TextDirection.ltr,
                  textCapitalization: TextCapitalization.characters,
                  autocorrect: false,
                  style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w600, letterSpacing: 2),
                  inputFormatters: [VoucherFormatter()],
                  decoration: const InputDecoration(hintText: 'XXXX-XXXX-XXXX'),
                  onSubmitted: (_) => redeem(),
                ),
                const SizedBox(height: 6),
                Text('القسيمة إما تضيف رصيدا أو تفتح كورسا مباشرة.', style: TextStyle(color: C.text3, fontSize: 12.5)),
                const SizedBox(height: 14),
                FilledButton(onPressed: redeem, child: const Text('تفعيل القسيمة')),
              ]),
            ),
            const SizedBox(height: 22),
            Text('سجل العمليات', style: head(18)),
            const SizedBox(height: 8),
            for (final t in asList(d['transactions']))
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(color: (t['amount'] as num) > 0 ? C.mist : C.bgAlt, borderRadius: BorderRadius.circular(12)),
                  child: Icon(t['type'] == 'voucher' ? CupertinoIcons.ticket : ((t['amount'] as num) > 0 ? CupertinoIcons.plus : CupertinoIcons.play_circle),
                      color: (t['amount'] as num) > 0 ? C.accent : C.text2, size: 20),
                ),
                title: Text(t['type_label'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                subtitle: Text(t['note'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12.5, color: C.text3)),
                trailing: Text('${(t['amount'] as num) > 0 ? '+' : ''}${iqd(t['amount'])}',
                    textDirection: TextDirection.ltr,
                    style: TextStyle(fontWeight: FontWeight.w600, color: (t['amount'] as num) > 0 ? C.success : C.text)),
              ),
            if (asList(d['transactions']).isEmpty) Text('لا توجد عمليات بعد.', style: TextStyle(color: C.text3)),
          ]),
        ),
      ),
    );
  }
}

/// تنسيق الكود أثناء الكتابة: XXXX-XXXX-XXXX
class VoucherFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    var v = newValue.text.toUpperCase().replaceAll(RegExp(r'[^A-Z0-9]'), '');
    if (v.length > 12) v = v.substring(0, 12);
    final parts = <String>[];
    for (var i = 0; i < v.length; i += 4) {
      parts.add(v.substring(i, i + 4 > v.length ? v.length : i + 4));
    }
    final s = parts.join('-');
    return TextEditingValue(text: s, selection: TextSelection.collapsed(offset: s.length));
  }
}

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  Widget _darkTile() => ValueListenableBuilder<bool>(
        valueListenable: darkMode,
        builder: (_, v, __) => ListTile(
          leading: Icon(v ? CupertinoIcons.moon_fill : CupertinoIcons.sun_max, color: C.accent),
          title: const Text('الوضع الليلي'),
          trailing: CupertinoSwitch(value: v, activeTrackColor: C.violet, onChanged: (x) => darkMode.value = x),
          onTap: () => darkMode.value = !v,
        ),
      );

  Widget _supportTile(BuildContext ctx) => ListTile(
        leading: Icon(CupertinoIcons.headphones, color: C.accent),
        title: const Text('محادثة الدعم'),
        subtitle: Text('نرد عليك داخل التطبيق', style: TextStyle(color: C.text3, fontSize: 12.5)),
        trailing: Icon(CupertinoIcons.chevron_left, size: 16, color: C.text3),
        onTap: () async {
          if (!await ensureLogin(ctx)) return;
          if (!ctx.mounted) return;
          try {
            dynamic r;
            await withBusy(ctx, () async => r = await Api.i.req('POST', '/chats/support'));
            if (!ctx.mounted) return;
            final c = asMap(r['data']);
            goRoot(ctx, ChatScreen(id: c['id'] as int, title: c['title'] ?? 'الدعم', subtitle: c['subtitle']));
          } catch (e) {
            if (ctx.mounted) toast(ctx, err(e), error: true);
          }
        },
      );

  Widget _waTile() => ListTile(
        leading: const Icon(CupertinoIcons.chat_bubble_2, color: C.success),
        title: const Text('واتساب الأكاديمية'),
        subtitle: Text('0776 663 7444', textDirection: TextDirection.ltr, textAlign: TextAlign.end, style: TextStyle(color: C.text3)),
        onTap: () => launchUrl(Uri.parse('https://wa.me/$whatsapp'), mode: LaunchMode.externalApplication),
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: C.bgAlt,
      body: SafeArea(
        child: ValueListenableBuilder<bool>(
          valueListenable: Api.i.loggedIn,
          builder: (ctx, inApp, _) => inApp ? _member(ctx) : _guest(ctx),
        ),
      ),
    );
  }

  Widget _guest(BuildContext ctx) => ListView(padding: const EdgeInsets.fromLTRB(20, 20, 20, 24), children: [
        const Title2('حسابي', size: 30),
        const SizedBox(height: 16),
        EmptyState(
          icon: CupertinoIcons.person_crop_circle,
          title: 'أهلا بيك بغني أكاديمي',
          text: 'تصفح الكورسات بحرية. سجل دخولك لما تريد تشترك أو تتعلم.',
          action: FilledButton(onPressed: () => ensureLogin(ctx), child: const Text('دخول أو حساب جديد')),
        ),
        _label('الإعدادات'),
        _group([_darkTile()]),
        _label('الدعم'),
        _group([_supportTile(ctx), _waTile()]),
      ]);

  Widget _member(BuildContext context) => Loader<List<dynamic>>(
        load: () => Future.wait<dynamic>([
          Api.i.me(),
          Api.i.req('GET', '/me/devices'),
          Api.i.req('GET', '/me/certificates'),
        ]),
        builder: (ctx, r, _) {
          final u = asMap(r[0]);
          final devices = asList((r[1] as Map)['data']);
          final certs = asList((r[2] as Map)['data']);
          return ListView(padding: const EdgeInsets.fromLTRB(20, 20, 20, 24), children: [
            const Title2('حسابي', size: 30),
            const SizedBox(height: 16),
            FadeSlide(
              child: _group([
                ListTile(
                  leading: CircleAvatar(backgroundColor: C.violet, child: Text((u['name'] ?? ' ').toString().substring(0, 1), style: const TextStyle(color: Colors.white))),
                  title: Text(u['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text(u['phone'] ?? '', textDirection: TextDirection.ltr, textAlign: TextAlign.end, style: TextStyle(color: C.text3)),
                ),
              ]),
            ),
            _label('الإعدادات'),
            _group([_darkTile()]),
            _label('الشهادات'),
            _group([
              if (certs.isEmpty) ListTile(title: Text('أكمل أي كورس وتصدر شهادتك هنا.', style: TextStyle(color: C.text3, fontSize: 14))),
              for (final c in certs)
                ListTile(
                  leading: const Icon(CupertinoIcons.rosette, color: C.gold),
                  title: Text(c['course'] ?? ''),
                  subtitle: Text(c['code'] ?? '', textDirection: TextDirection.ltr, textAlign: TextAlign.end, style: TextStyle(color: C.text3)),
                  trailing: Icon(CupertinoIcons.chevron_left, size: 16, color: C.text3),
                  onTap: () => launchUrl(Uri.parse(c['url']), mode: LaunchMode.externalApplication),
                ),
            ]),
            _label('الأجهزة المربوطة (${devices.length}/${(r[1] as Map)['max_devices']})'),
            _group([
              for (final d in devices)
                ListTile(
                  leading: Icon(d['platform'] == 'web' ? CupertinoIcons.desktopcomputer : CupertinoIcons.device_phone_portrait, color: C.accent),
                  title: Text(d['name'] ?? d['platform'] ?? ''),
                  trailing: d['is_current'] == true
                      ? Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(color: C.mist, borderRadius: BorderRadius.circular(99)),
                          child: Text('هذا الجهاز', style: TextStyle(color: C.accent, fontSize: 11.5)),
                        )
                      : null,
                ),
            ]),
            Padding(
              padding: const EdgeInsets.fromLTRB(6, 8, 6, 0),
              child: Text('إذا تغير جهازك راسل الدعم حتى نعيد تعيين الأجهزة.', style: TextStyle(color: C.text3, fontSize: 12.5)),
            ),
            _label('الدعم'),
            _group([_supportTile(ctx), _waTile()]),
            const SizedBox(height: 22),
            TextButton(
              style: TextButton.styleFrom(
                foregroundColor: C.danger,
                minimumSize: const Size.fromHeight(50),
                backgroundColor: C.card,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              onPressed: () async {
                tab.value = 0;
                await Api.i.logout();
              },
              child: const Text('تسجيل الخروج'),
            ),
          ]);
        },
      );

  Widget _label(String t) => Padding(padding: const EdgeInsets.fromLTRB(6, 22, 6, 8), child: Text(t, style: TextStyle(color: C.text3, fontWeight: FontWeight.w600, fontSize: 13)));

  Widget _group(List<Widget> children) => Container(
        decoration: BoxDecoration(color: C.card, borderRadius: BorderRadius.circular(16)),
        clipBehavior: Clip.antiAlias,
        child: Column(children: children),
      );
}
