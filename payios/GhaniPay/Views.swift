import SwiftUI

// MARK: - مكونات

struct Card<Content: View>: View {
    var stroke: Color = Theme.line
    @ViewBuilder var content: Content
    var body: some View {
        VStack(alignment: .leading, spacing: 10) { content }
            .padding(18)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(RoundedRectangle(cornerRadius: 22, style: .continuous).fill(Theme.card))
            .overlay(RoundedRectangle(cornerRadius: 22, style: .continuous).stroke(stroke, lineWidth: 1))
    }
}

struct GoldButton: View {
    let title: String
    var primary = true
    var small = false
    let action: () -> Void
    var body: some View {
        Button(action: action) {
            Text(title).font(Theme.semi(small ? 13 : 15))
                .foregroundColor(primary ? Theme.bg : Theme.text)
                .padding(.horizontal, 16)
                .frame(minHeight: small ? 40 : 50)
                .frame(maxWidth: small ? nil : .infinity)
                .background(
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .fill(primary ? AnyShapeStyle(LinearGradient(colors: [Theme.gold2, Theme.gold], startPoint: .topLeading, endPoint: .bottomTrailing))
                              : AnyShapeStyle(Color.white.opacity(0.06)))
                )
                .overlay(RoundedRectangle(cornerRadius: 14, style: .continuous).stroke(primary ? .clear : Theme.line, lineWidth: 1))
        }
        .buttonStyle(Press())
    }
}

struct Press: ButtonStyle {
    func makeBody(configuration: Configuration) -> some View {
        configuration.label.scaleEffect(configuration.isPressed ? 0.97 : 1).animation(.easeOut(duration: 0.12), value: configuration.isPressed)
    }
}

struct Pill: View {
    let text: String
    let color: Color
    var body: some View {
        Text(text).font(Theme.semi(12)).foregroundColor(color)
            .padding(.horizontal, 10).padding(.vertical, 5)
            .background(Capsule().fill(color.opacity(0.14)))
    }
}

struct Avatar: View {
    let name: String
    var color: Color = Theme.gold
    var body: some View {
        Text(String(name.trimmingCharacters(in: .whitespaces).prefix(1)).isEmpty ? "؟" : String(name.trimmingCharacters(in: .whitespaces).prefix(1)))
            .font(Theme.head(16)).foregroundColor(color)
            .frame(width: 42, height: 42)
            .background(Circle().fill(color.opacity(0.14)))
    }
}

struct Block<Content: View>: View {
    let title: String
    var count: Int? = nil
    var color: Color = Theme.gold
    @ViewBuilder var content: Content
    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text(title).font(Theme.head(17)).foregroundColor(Theme.text)
                Spacer()
                if let c = count { Pill(text: "\(c)", color: color) }
            }
            .padding(.horizontal, 4).padding(.top, 14)
            content
        }
    }
}

struct Header: View {
    @EnvironmentObject var store: Store
    var body: some View {
        HStack(spacing: 12) {
            Text("G").font(Theme.head(20)).foregroundColor(Theme.bg)
                .frame(width: 44, height: 44)
                .background(RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .fill(LinearGradient(colors: [Theme.gold2, Theme.gold], startPoint: .topLeading, endPoint: .bottomTrailing)))
            VStack(alignment: .leading, spacing: 4) {
                Text("غني باي").font(Theme.head(22)).foregroundColor(Theme.text)
                Text("بوابة الدفع · غني أكاديمي").font(Theme.body(12.5)).foregroundColor(Theme.text2)
            }
            Spacer()
            let ok = store.online == true && store.feed?.device?.online != false
            Pill(text: store.online == nil ? "..." : ok ? "● يعمل" : "● متوقف", color: store.online == nil ? Theme.text2 : ok ? Theme.green : Theme.red)
        }
        .padding(.bottom, 6)
    }
}

// MARK: - الرئيسية

struct HomeView: View {
    @EnvironmentObject var store: Store
    @State private var confirmAttach: Feed.Review?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 12) {
                Header()
                if let f = store.feed {
                    hero(f.stats)
                    device(f.device)
                    if !f.review.isEmpty {
                        Block(title: "تحتاج مراجعة", count: f.review.count, color: Theme.amber) {
                            ForEach(f.review) { n in review(n) }
                        }
                    }
                    if !f.wallets.isEmpty {
                        Block(title: "المحافظ") {
                            ForEach(f.wallets) { w in
                                Card {
                                    HStack {
                                        VStack(alignment: .leading, spacing: 6) {
                                            Text(w.label).font(Theme.body(12.5)).foregroundColor(Theme.text2)
                                            Text(w.number).font(Theme.head(20)).foregroundColor(Theme.text).environment(\.layoutDirection, .leftToRight)
                                        }
                                        Spacer()
                                        GoldButton(title: "نسخ", small: true) { store.copy("الرقم", w.number.replacingOccurrences(of: " ", with: "")) }
                                    }
                                }
                            }
                        }
                    }
                    RecentList(orders: Array(f.recent.prefix(5)))
                } else {
                    Card { Text(store.online == false ? "ما گدرت أتصل بالمنصة. شيك الإعدادات والنت." : "جاري التحميل…").font(Theme.body(14)).foregroundColor(Theme.text2) }
                }
            }
            .padding(18)
        }
        .refreshable { await store.load() }
        .confirmationDialog("ربط الدفعة بالطلب وتفعيله؟", isPresented: Binding(get: { confirmAttach != nil }, set: { if !$0 { confirmAttach = nil } }), titleVisibility: .visible) {
            if let n = confirmAttach, let s = n.suggest {
                Button("ربط بطلب #\(s.id) وتفعيله") { Task { await store.attach(n, to: s.id) } }
            }
            Button("إلغاء", role: .cancel) {}
        }
    }

    private func hero(_ s: Feed.Stats) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            Text("مدفوعات اليوم").font(Theme.semi(13)).foregroundColor(Theme.gold2.opacity(0.85))
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(money(s.today)).font(Theme.head(38)).foregroundColor(Theme.gold2)
                    .contentTransition(.numericText())
                Text("د.ع").font(Theme.body(14)).foregroundColor(Theme.text2)
            }
            .padding(.top, 8).padding(.bottom, 16)
            Rectangle().fill(Theme.gold.opacity(0.16)).frame(height: 1)
            HStack {
                stat("الشهر", money(s.month))
                stat("معلقة", "\(s.pending)")
                stat("مراجعة", "\(s.review)", s.review > 0 ? Theme.amber : Theme.text)
            }
            .padding(.top, 14)
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(
            RoundedRectangle(cornerRadius: 26, style: .continuous)
                .fill(LinearGradient(colors: [Color(red: 58 / 255, green: 46 / 255, blue: 22 / 255), Color(red: 30 / 255, green: 25 / 255, blue: 18 / 255), Theme.card],
                                     startPoint: .topLeading, endPoint: .bottomTrailing))
        )
        .overlay(RoundedRectangle(cornerRadius: 26, style: .continuous).stroke(Theme.gold.opacity(0.28), lineWidth: 1))
    }

    private func stat(_ label: String, _ value: String, _ color: Color = Theme.text) -> some View {
        VStack(alignment: .leading, spacing: 5) {
            Text(label).font(Theme.body(12)).foregroundColor(Theme.text2)
            Text(value).font(Theme.head(17)).foregroundColor(color)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    /** حالة موبايل الأندرويد اللي يقرأ الإشعارات */
    private func device(_ d: Feed.Device?) -> some View {
        let online = d?.online
        let color: Color = online == true ? Theme.green : online == false ? Theme.red : Theme.text2
        return Card {
            HStack(spacing: 12) {
                Circle().fill(color).frame(width: 10, height: 10)
                    .shadow(color: color.opacity(0.7), radius: online == true ? 5 : 0)
                VStack(alignment: .leading, spacing: 4) {
                    Text("موبايل الاستلام (أندرويد)").font(Theme.body(12)).foregroundColor(Theme.text2)
                    Text(online == true ? "متصل ويقرأ الإشعارات" : online == false ? "منقطع، الدفعات ما تتأكد تلقائيا" : "غير مربوط")
                        .font(Theme.semi(14)).foregroundColor(Theme.text)
                }
                Spacer()
                if let b = d?.battery {
                    Pill(text: "\(b)%" + ((d?.charging ?? false) ? " ⚡" : ""), color: b <= 15 && !(d?.charging ?? false) ? Theme.red : Theme.text2)
                }
            }
            if let seen = d?.seen {
                Text("آخر نبض \(seen)" + ((d?.queue ?? 0) > 0 ? " · بالطابور \(d?.queue ?? 0)" : "")).font(Theme.body(11.5)).foregroundColor(Theme.text2)
            }
        }
    }

    private func review(_ n: Feed.Review) -> some View {
        Card(stroke: Theme.amber.opacity(0.35)) {
            HStack {
                Text(n.amount.map(money) ?? "—").font(Theme.head(24)).foregroundColor(Theme.amber)
                Spacer()
                Text(n.at ?? "").font(Theme.body(12)).foregroundColor(Theme.text2)
            }
            Text(n.text).font(Theme.body(12.5)).foregroundColor(Theme.text2).lineSpacing(4)
            if let s = n.suggest {
                Text("مقترح: طلب #\(s.id) · \(s.user ?? "") · \(money(s.amount))").font(Theme.semi(13)).foregroundColor(Theme.text)
                    .padding(12).frame(maxWidth: .infinity, alignment: .leading)
                    .background(RoundedRectangle(cornerRadius: 12).fill(Color.white.opacity(0.04)))
            }
            HStack(spacing: 8) {
                if n.suggest != nil { GoldButton(title: "ربط وتفعيل", small: true) { confirmAttach = n } }
                GoldButton(title: "تجاهل", primary: false, small: true) { Task { await store.ignore(n) } }
            }
        }
    }
}

struct RecentList: View {
    let orders: [Feed.Order]
    var body: some View {
        if !orders.isEmpty {
            Block(title: "آخر المدفوعات") {
                Card {
                    ForEach(Array(orders.enumerated()), id: \.element.id) { i, o in
                        if i > 0 { Rectangle().fill(Theme.line).frame(height: 1) }
                        HStack(spacing: 12) {
                            Avatar(name: o.user ?? "", color: Theme.green)
                            VStack(alignment: .leading, spacing: 4) {
                                Text(o.user ?? "").font(Theme.semi(14)).foregroundColor(Theme.text)
                                Text("\(o.course ?? "") · \(o.by == "auto" ? "تلقائي" : "يدوي")").font(Theme.body(12)).foregroundColor(Theme.text2)
                            }
                            Spacer()
                            VStack(alignment: .trailing, spacing: 4) {
                                Text("+" + money(o.amount)).font(Theme.head(15)).foregroundColor(Theme.green)
                                Text(o.paid ?? "").font(Theme.body(11)).foregroundColor(Theme.text2)
                            }
                        }
                    }
                }
            }
        }
    }
}

// MARK: - الطلبات

struct OrdersView: View {
    @EnvironmentObject var store: Store
    @State private var confirm: Feed.Order?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 12) {
                Header()
                let pending = store.feed?.pending ?? []
                Block(title: "بانتظار التحويل", count: pending.count) {
                    if pending.isEmpty { Card { Text("ماكو طلبات معلقة.").font(Theme.body(14)).foregroundColor(Theme.text2) } }
                    ForEach(pending) { o in
                        Card {
                            HStack(spacing: 12) {
                                Avatar(name: o.user ?? "")
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(o.user ?? "").font(Theme.semi(15)).foregroundColor(Theme.text)
                                    Text(o.phone ?? "").font(Theme.body(12.5)).foregroundColor(Theme.text2)
                                }
                                Spacer()
                                Text(o.created ?? "").font(Theme.body(11.5)).foregroundColor(Theme.text2)
                            }
                            HStack {
                                VStack(alignment: .leading, spacing: 5) {
                                    Text("\(o.course ?? "") · #\(o.id)").font(Theme.body(11.5)).foregroundColor(Theme.text2)
                                    Text(money(o.amount)).font(Theme.head(24)).foregroundColor(Theme.gold2)
                                }
                                Spacer()
                                GoldButton(title: "نسخ المبلغ", primary: false, small: true) { store.copy("المبلغ", "\(o.amount)") }
                            }
                            .padding(14)
                            .background(RoundedRectangle(cornerRadius: 16, style: .continuous).fill(Theme.card2))
                            GoldButton(title: "وصل التحويل؟ تأكيد يدوي", primary: false) { confirm = o }
                        }
                    }
                }
                RecentList(orders: store.feed?.recent ?? [])
            }
            .padding(18)
        }
        .refreshable { await store.load() }
        .confirmationDialog(confirm.map { "تأكد إن \(money($0.amount)) وصل لمحفظتك، وتفعيل طلب \($0.user ?? "")؟" } ?? "",
                            isPresented: Binding(get: { confirm != nil }, set: { if !$0 { confirm = nil } }), titleVisibility: .visible) {
            if let o = confirm { Button("تأكيد وتفعيل") { Task { await store.approve(o) } } }
            Button("إلغاء", role: .cancel) {}
        }
    }
}

// MARK: - الإعدادات

struct SettingsView: View {
    @EnvironmentObject var store: Store
    @State private var url = ""
    @State private var secret = ""
    @State private var busy = false

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 12) {
                Header()
                Block(title: "ربط المنصة") {
                    Card {
                        Text("من لوحة التحكم ← الدفع المباشر ← ربط تطبيق غني باي").font(Theme.body(12.5)).foregroundColor(Theme.text2)
                        field("رابط المنصة", $url)
                        field("المفتاح السري", $secret)
                        GoldButton(title: busy ? "جاري الاختبار…" : "حفظ واختبار") {
                            busy = true
                            store.save(url: url, secret: secret)
                            Task {
                                let ok = await store.load()
                                busy = false
                                store.show(ok ? "متصل بالمنصة" : "فشل الاتصال. شيك الرابط والمفتاح.")
                                UINotificationFeedbackGenerator().notificationOccurred(ok ? .success : .error)
                            }
                        }
                        .disabled(busy)
                        .padding(.top, 4)
                    }
                }
                if let ios = store.feed?.ios { IosHookCard(ios: ios) }
                let v = (Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "") + "." + (Bundle.main.infoDictionary?["CFBundleVersion"] as? String ?? "")
                Text("غني باي v\(v) · Ghani Code").font(Theme.body(11.5)).foregroundColor(Theme.text2.opacity(0.6))
                    .frame(maxWidth: .infinity).padding(.top, 10)
            }
            .padding(18)
        }
        .onAppear { url = store.url; secret = store.secret }
    }

    private func field(_ label: String, _ value: Binding<String>) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(label).font(Theme.semi(13)).foregroundColor(Theme.text2)
            TextField("", text: value)
                .font(Theme.body(15)).foregroundColor(Theme.text)
                .textInputAutocapitalization(.never).autocorrectionDisabled()
                .keyboardType(.URL)
                .multilineTextAlignment(.leading)
                .environment(\.layoutDirection, .leftToRight)
                .padding(.horizontal, 14).frame(minHeight: 50)
                .background(RoundedRectangle(cornerRadius: 14, style: .continuous).fill(Theme.bg))
                .overlay(RoundedRectangle(cornerRadius: 14, style: .continuous).stroke(Theme.line, lineWidth: 1))
        }
        .padding(.top, 6)
    }
}

// MARK: - الاستلام من رسائل الآيفون (أتمتة الاختصارات)

struct IosHookCard: View {
    @EnvironmentObject var store: Store
    let ios: Feed.Ios

    private let steps = [
        "افتح الاختصارات ← الأتمتة ← أتمتة جديدة ← رسالة.",
        "المرسل: اسم المحفظة (مثل SuperQi). الرسالة تحتوي: استلام. واختر «تشغيل فورا».",
        "أضف إجراء «الحصول على محتويات URL» والصق الرابط، والطريقة POST.",
        "الرؤوس: X-Ghani-Token والصق التوكن.",
        "نص الطلب JSON: الحقل text = محتوى الرسالة، والحقل sender = المرسل (من «مدخل الاختصار»).",
        "جرب: خلي أحد يحولك مبلغ صغير وشوف «آخر رسالة» تتحدث.",
    ]

    var body: some View {
        Block(title: "الاستلام من رسائل الآيفون") {
            Card {
                Text("الآيفون ما يسمح بقراءة إشعارات التطبيقات، بس الاختصارات تقرأ رسائل SMS وتدزها للمنصة تلقائيا. يشتغل إذا المحفظة تدزلك SMS بالاستلام.")
                    .font(Theme.body(13)).foregroundColor(Theme.text2).lineSpacing(5)
                copyRow("الرابط", ios.url)
                copyRow("التوكن", ios.token)
                HStack {
                    Text("آخر رسالة وصلت").font(Theme.body(12)).foregroundColor(Theme.text2)
                    Spacer()
                    Pill(text: ios.seen ?? "بعد ما وصلت", color: ios.seen == nil ? Theme.text2 : Theme.green)
                }
                .padding(.top, 4)
                Rectangle().fill(Theme.line).frame(height: 1).padding(.vertical, 4)
                ForEach(Array(steps.enumerated()), id: \.offset) { i, s in
                    HStack(alignment: .top, spacing: 10) {
                        Text("\(i + 1)").font(Theme.head(12)).foregroundColor(Theme.bg)
                            .frame(width: 22, height: 22).background(Circle().fill(Theme.gold))
                        Text(s).font(Theme.body(13)).foregroundColor(Theme.text).lineSpacing(4)
                    }
                }
                GoldButton(title: "فتح الاختصارات") {
                    if let u = URL(string: "shortcuts://") { UIApplication.shared.open(u) }
                }
                .padding(.top, 4)
            }
        }
    }

    private func copyRow(_ label: String, _ value: String) -> some View {
        HStack(spacing: 10) {
            VStack(alignment: .leading, spacing: 4) {
                Text(label).font(Theme.body(12)).foregroundColor(Theme.text2)
                Text(value).font(Theme.body(12.5)).foregroundColor(Theme.text).lineLimit(1).truncationMode(.middle)
                    .environment(\.layoutDirection, .leftToRight)
            }
            Spacer()
            GoldButton(title: "نسخ", small: true) { store.copy(label, value) }
        }
        .padding(12)
        .background(RoundedRectangle(cornerRadius: 14, style: .continuous).fill(Theme.card2))
    }
}
