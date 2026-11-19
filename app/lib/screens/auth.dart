import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../ui.dart';

const whatsapp = '9647766637444';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  bool register = false;
  bool busy = false;
  final name = TextEditingController();
  final phone = TextEditingController();
  final city = TextEditingController();
  final pass = TextEditingController();

  Future<void> submit() async {
    if (phone.text.trim().isEmpty || pass.text.isEmpty || (register && name.text.trim().length < 3)) {
      toast(context, 'أكمل الحقول المطلوبة.', error: true);
      return;
    }
    setState(() => busy = true);
    try {
      if (register) {
        await Api.i.register(name.text.trim(), phone.text.trim(), city.text.trim(), pass.text);
      } else {
        await Api.i.login(phone.text.trim(), pass.text);
      }
    } catch (e) {
      if (mounted) toast(context, err(e), error: true);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Widget field(String label, TextEditingController c, {bool ltr = false, bool secret = false, TextInputType? type, String? hint, String? help}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14)),
        const SizedBox(height: 7),
        TextField(
          controller: c,
          obscureText: secret,
          keyboardType: type,
          textDirection: ltr ? TextDirection.ltr : null,
          textAlign: ltr ? TextAlign.left : TextAlign.start,
          decoration: InputDecoration(hintText: hint),
        ),
        if (help != null) Padding(padding: const EdgeInsets.only(top: 5), child: Text(help, style: const TextStyle(color: C.text3, fontSize: 12.5))),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: C.bgAlt,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Container(
              constraints: const BoxConstraints(maxWidth: 420),
              padding: const EdgeInsets.fromLTRB(24, 30, 24, 24),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(26), border: Border.all(color: C.line)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                Center(child: ClipRRect(borderRadius: BorderRadius.circular(15), child: Image.asset('assets/img/icon.png', width: 58))),
                const SizedBox(height: 16),
                Center(child: Title2(register ? 'ابدأ مهنتك هذا الشهر' : 'أهلا بعودتك', size: 25)),
                const SizedBox(height: 4),
                Text(register ? 'حساب واحد للموقع والتطبيق.' : 'سجل دخولك وأكمل من حيث توقفت.',
                    textAlign: TextAlign.center, style: const TextStyle(color: C.text2)),
                const SizedBox(height: 24),
                if (register) field('الاسم الكامل', name, help: 'يظهر على شهادة الإتمام.'),
                field('رقم الهاتف', phone, ltr: true, type: TextInputType.phone, hint: '07XXXXXXXXX'),
                if (register) field('المحافظة', city, hint: 'بغداد'),
                field('كلمة المرور', pass, ltr: true, secret: true, help: register ? '8 أحرف على الأقل.' : null),
                const SizedBox(height: 6),
                FilledButton(
                  onPressed: busy ? null : submit,
                  child: busy
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text(register ? 'إنشاء الحساب' : 'دخول'),
                ),
                const SizedBox(height: 14),
                TextButton(
                  onPressed: () => setState(() => register = !register),
                  child: Text(register ? 'عندك حساب؟ سجل دخولك' : 'ما عندك حساب؟ أنشئ حسابك'),
                ),
                if (!register)
                  TextButton(
                    onPressed: () => launchUrl(Uri.parse('https://wa.me/$whatsapp'), mode: LaunchMode.externalApplication),
                    child: const Text('نسيت كلمة المرور؟ راسلنا على واتساب', style: TextStyle(color: C.text3, fontSize: 13)),
                  ),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
