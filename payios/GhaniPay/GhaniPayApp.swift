import SwiftUI

// MARK: - الهوية: أسود وذهبي

enum Theme {
    static let bg = Color(red: 11 / 255, green: 10 / 255, blue: 16 / 255)
    static let card = Color(red: 21 / 255, green: 19 / 255, blue: 29 / 255)
    static let card2 = Color(red: 28 / 255, green: 25 / 255, blue: 38 / 255)
    static let line = Color.white.opacity(0.10)
    static let gold = Color(red: 212 / 255, green: 175 / 255, blue: 106 / 255)
    static let gold2 = Color(red: 240 / 255, green: 207 / 255, blue: 140 / 255)
    static let text = Color(red: 245 / 255, green: 241 / 255, blue: 232 / 255)
    static let text2 = Color(red: 160 / 255, green: 153 / 255, blue: 175 / 255)
    static let green = Color(red: 52 / 255, green: 199 / 255, blue: 123 / 255)
    static let red = Color(red: 240 / 255, green: 110 / 255, blue: 120 / 255)
    static let amber = Color(red: 245 / 255, green: 166 / 255, blue: 35 / 255)

    static func head(_ s: CGFloat) -> Font { .custom("Alexandria-Bold", size: s) }
    static func body(_ s: CGFloat) -> Font { .custom("ReadexPro-Regular", size: s) }
    static func semi(_ s: CGFloat) -> Font { .custom("ReadexPro-SemiBold", size: s) }
}

@main
struct GhaniPayApp: App {
    @StateObject private var store = Store()

    init() {
        let tab = UITabBarAppearance()
        tab.configureWithOpaqueBackground()
        tab.backgroundColor = UIColor(Theme.card)
        tab.shadowColor = UIColor(Theme.line)
        for item in [tab.stackedLayoutAppearance, tab.inlineLayoutAppearance, tab.compactInlineLayoutAppearance] {
            item.normal.titleTextAttributes = [.font: UIFont(name: "ReadexPro-Regular", size: 11) ?? .systemFont(ofSize: 11), .foregroundColor: UIColor(Theme.text2)]
            item.selected.titleTextAttributes = [.font: UIFont(name: "ReadexPro-SemiBold", size: 11) ?? .boldSystemFont(ofSize: 11), .foregroundColor: UIColor(Theme.gold)]
            item.normal.iconColor = UIColor(Theme.text2)
            item.selected.iconColor = UIColor(Theme.gold)
        }
        UITabBar.appearance().standardAppearance = tab
        UITabBar.appearance().scrollEdgeAppearance = tab
    }

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environment(\.layoutDirection, .rightToLeft)
                .environment(\.locale, Locale(identifier: "ar"))
                .preferredColorScheme(.dark)
                .tint(Theme.gold)
        }
    }
}

struct RootView: View {
    @EnvironmentObject var store: Store
    @Environment(\.scenePhase) private var phase
    @State private var tab = 0

    var body: some View {
        ZStack(alignment: .top) {
            Theme.bg.ignoresSafeArea()
            if store.configured {
                TabView(selection: $tab) {
                    HomeView().tabItem { Label("الرئيسية", systemImage: "square.grid.2x2.fill") }.tag(0)
                        .badge(store.feed?.review.count ?? 0)
                    OrdersView().tabItem { Label("الطلبات", systemImage: "list.bullet.rectangle.portrait.fill") }.tag(1)
                        .badge(store.feed?.pending.count ?? 0)
                    SettingsView().tabItem { Label("الإعدادات", systemImage: "gearshape.fill") }.tag(2)
                }
            } else {
                SettingsView()
            }
            if let t = store.toast {
                Text(t).font(Theme.semi(14)).foregroundColor(Theme.bg)
                    .padding(.horizontal, 18).padding(.vertical, 11)
                    .background(Capsule().fill(Theme.gold2))
                    .padding(.top, 8)
                    .transition(.move(edge: .top).combined(with: .opacity))
            }
        }
        .animation(.spring(response: 0.35), value: store.toast)
        .task(id: phase) {
            // تحديث كل 6 ثواني والتطبيق مفتوح
            while phase == .active && !Task.isCancelled {
                await store.load()
                try? await Task.sleep(nanoseconds: 6_000_000_000)
            }
        }
    }
}
