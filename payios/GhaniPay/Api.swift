import CryptoKit
import Foundation
import Security
import SwiftUI

// MARK: - البيانات (نفس رد /api/v1/payments/console/feed)

struct Feed: Decodable {
    struct Stats: Decodable { var today: Int = 0; var month: Int = 0; var pending: Int = 0; var review: Int = 0 }
    struct Wallet: Decodable, Identifiable { var key: String; var label: String; var number: String; var id: String { key } }
    struct Order: Decodable, Identifiable {
        var id: Int; var amount: Int
        var user: String?; var phone: String?; var course: String?
        var created: String?; var paid: String?; var by: String?
    }
    struct Suggest: Decodable { var id: Int; var user: String?; var amount: Int }
    struct Review: Decodable, Identifiable { var id: Int; var amount: Int?; var text: String; var at: String?; var suggest: Suggest? }
    struct Device: Decodable {
        var online: Bool?; var seen: String?; var battery: Int?; var charging: Bool?; var queue: Int?; var version: String?
    }

    var stats: Stats
    var wallets: [Wallet]
    var pending: [Order]
    var review: [Review]
    var recent: [Order]
    var device: Device?
}

// MARK: - تخزين المفتاح بالـ Keychain

enum Keychain {
    static let account = "pay_hook_secret"

    static func get() -> String {
        let q: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrAccount as String: account,
                                kSecReturnData as String: true, kSecMatchLimit as String: kSecMatchLimitOne]
        var out: AnyObject?
        guard SecItemCopyMatching(q as CFDictionary, &out) == errSecSuccess, let d = out as? Data else { return "" }
        return String(data: d, encoding: .utf8) ?? ""
    }

    static func set(_ v: String) {
        let base: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrAccount as String: account]
        SecItemDelete(base as CFDictionary)
        var add = base
        add[kSecValueData as String] = Data(v.utf8)
        add[kSecAttrAccessible as String] = kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        SecItemAdd(add as CFDictionary, nil)
    }
}

// MARK: - الاتصال بالمنصة (موقع HMAC مثل تطبيق الأندرويد)

@MainActor
final class Store: ObservableObject {
    @Published var url: String = UserDefaults.standard.string(forKey: "url") ?? "https://ghaniacademy.online"
    @Published var secret: String = Keychain.get()
    @Published var feed: Feed?
    @Published var online: Bool?
    @Published var toast: String?

    var configured: Bool { !url.trimmingCharacters(in: .whitespaces).isEmpty && !secret.isEmpty }

    func save(url: String, secret: String) {
        self.url = url.trimmingCharacters(in: .whitespacesAndNewlines)
        self.secret = secret.trimmingCharacters(in: .whitespacesAndNewlines)
        UserDefaults.standard.set(self.url, forKey: "url")
        Keychain.set(self.secret)
    }

    private func request(_ method: String, _ path: String, body: Data = Data()) async -> (Int, Data)? {
        let base = url.trimmingCharacters(in: CharacterSet(charactersIn: "/ "))
        guard configured, let u = URL(string: base + path) else { return nil }
        let ts = String(Int(Date().timeIntervalSince1970))
        let key = SymmetricKey(data: Data(secret.utf8))
        var payload = Data((ts + ".").utf8); payload.append(body)
        let sig = HMAC<SHA256>.authenticationCode(for: payload, using: key).map { String(format: "%02x", $0) }.joined()
        var r = URLRequest(url: u, timeoutInterval: 12)
        r.httpMethod = method
        r.setValue("application/json", forHTTPHeaderField: "Accept")
        r.setValue(ts, forHTTPHeaderField: "X-Ghani-Time")
        r.setValue(sig, forHTTPHeaderField: "X-Ghani-Sign")
        if method == "POST" { r.setValue("application/json", forHTTPHeaderField: "Content-Type"); r.httpBody = body }
        guard let (data, res) = try? await URLSession.shared.data(for: r), let http = res as? HTTPURLResponse else { return nil }
        return (http.statusCode, data)
    }

    @discardableResult
    func load() async -> Bool {
        guard let (code, data) = await request("GET", "/api/v1/payments/console/feed") else { online = false; return false }
        online = code == 200
        if code == 200, let f = try? JSONDecoder().decode(Feed.self, from: data) { feed = f }
        return code == 200
    }

    func act(_ path: String, order: Int? = nil) async {
        let body = order.map { Data("{\"order\":\($0)}".utf8) } ?? Data("{}".utf8)
        let r = await request("POST", path, body: body)
        if r?.0 == 200 {
            show("تم")
            UINotificationFeedbackGenerator().notificationOccurred(.success)
        } else {
            show("ما تم: " + (r.map { String(data: $0.1, encoding: .utf8)?.prefix(80).description ?? "" } ?? "ماكو اتصال"))
        }
        await load()
    }

    func approve(_ o: Feed.Order) async { await act("/api/v1/payments/console/orders/\(o.id)/approve") }
    func attach(_ n: Feed.Review, to order: Int) async { await act("/api/v1/payments/console/notifications/\(n.id)/attach", order: order) }
    func ignore(_ n: Feed.Review) async { await act("/api/v1/payments/console/notifications/\(n.id)/ignore") }

    func copy(_ label: String, _ value: String) {
        UIPasteboard.general.string = value
        UIImpactFeedbackGenerator(style: .light).impactOccurred()
        show("تم نسخ \(label)")
    }

    func show(_ t: String) {
        toast = t
        Task { try? await Task.sleep(nanoseconds: 1_800_000_000); if toast == t { toast = nil } }
    }
}

func money(_ v: Int) -> String {
    let f = NumberFormatter(); f.numberStyle = .decimal; f.locale = Locale(identifier: "en_US")
    return f.string(from: NSNumber(value: v)) ?? "\(v)"
}
