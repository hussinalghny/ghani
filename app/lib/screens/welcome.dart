import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

import '../ui.dart';
import 'auth.dart';

/// شاشة التحميل: الشعار بنص الشاشة لحد ما يجهز التطبيق
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
        body: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Image.asset(C.dark ? 'assets/img/logo-dark.png' : 'assets/img/logo-light.png', width: 190),
            const SizedBox(height: 28),
            CupertinoActivityIndicator(radius: 10, color: C.text3),
          ]),
        ),
      );
}

/// شاشة الترحيب: تطلع أول مرة يفتح بيها التطبيق فقط
class WelcomeScreen extends StatefulWidget {
  final VoidCallback onDone;
  const WelcomeScreen({super.key, required this.onDone});
  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  final pc = PageController();
  int page = 0;

  static const pages = [
    (CupertinoIcons.play_rectangle_fill, 'تعلم. طبق. اشتغل.', 'كورسات عملية بالأمن السيبراني وبرمجة المواقع والتطبيقات، بالعربي ومن موبايلك.'),
    (CupertinoIcons.person_2_fill, 'مدرب يشتغل بالمجال', '12 جلسة خلال 4 أسابيع، وأسئلتك يجاوبها المدرب تحت كل درس.'),
    (CupertinoIcons.rosette, 'شهادة تثبت مهارتك', 'تكمل الكورس وتطلع بمشروع حقيقي وشهادة إتمام قابلة للتحقق.'),
  ];

  void next() {
    if (page < pages.length - 1) {
      pc.nextPage(duration: const Duration(milliseconds: 320), curve: Curves.easeOutCubic);
    } else {
      widget.onDone();
    }
  }

  @override
  Widget build(BuildContext context) {
    final last = page == pages.length - 1;
    return Scaffold(
      body: SafeArea(
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 10, 12, 0),
            child: Row(children: [
              Image.asset(C.dark ? 'assets/img/logo-dark.png' : 'assets/img/logo-light.png', height: 32),
              const Spacer(),
              if (!last) TextButton(onPressed: widget.onDone, child: Text('تخطي', style: TextStyle(color: C.text3))),
            ]),
          ),
          Expanded(
            child: PageView.builder(
              controller: pc,
              itemCount: pages.length,
              onPageChanged: (i) => setState(() => page = i),
              itemBuilder: (_, i) {
                final (icon, title, text) = pages[i];
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 28),
                  child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                    Container(
                      width: 220,
                      height: 220,
                      decoration: BoxDecoration(color: C.mist, shape: BoxShape.circle),
                      child: Center(
                        child: Container(
                          width: 116,
                          height: 116,
                          decoration: BoxDecoration(color: C.violet, borderRadius: BorderRadius.circular(34)),
                          child: Icon(icon, color: Colors.white, size: 54),
                        ),
                      ),
                    ),
                    const SizedBox(height: 40),
                    Text(title, textAlign: TextAlign.center, style: head(28)),
                    const SizedBox(height: 10),
                    Text(text, textAlign: TextAlign.center, style: TextStyle(color: C.text2, fontSize: 15.5, height: 1.7)),
                  ]),
                );
              },
            ),
          ),
          Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            for (var i = 0; i < pages.length; i++)
              AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: i == page ? 22 : 7,
                height: 7,
                decoration: BoxDecoration(color: i == page ? C.primary : C.line, borderRadius: BorderRadius.circular(7)),
              ),
          ]),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 8),
            child: SizedBox(width: double.infinity, child: FilledButton(onPressed: next, child: Text(last ? 'ابدأ التعلم' : 'التالي'))),
          ),
          SizedBox(
            height: 48,
            child: last
                ? TextButton(
                    onPressed: () async {
                      await ensureLogin(context);
                      widget.onDone();
                    },
                    child: Text('عندي حساب، سجل دخول', style: TextStyle(color: C.accent, fontWeight: FontWeight.w700)),
                  )
                : null,
          ),
          const SizedBox(height: 8),
        ]),
      ),
    );
  }
}
