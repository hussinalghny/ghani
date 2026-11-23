import Flutter
import UIKit

/// شريط التبويبات الأصلي مال النظام (UITabBar) داخل فلاتر.
/// بـ iOS 26 ياخذ زجاج آبل (Liquid Glass) تلقائيا، وبالنسخ الأقدم شكل النظام العادي.
final class GhaniTabBar: NSObject, FlutterPlatformViewFactory {
  private let messenger: FlutterBinaryMessenger

  init(messenger: FlutterBinaryMessenger) {
    self.messenger = messenger
    super.init()
  }

  static func register(with registry: FlutterPluginRegistry) {
    guard let r = registry.registrar(forPlugin: "GhaniTabBar") else { return }
    r.register(GhaniTabBar(messenger: r.messenger()), withId: "ghani/tabbar")
  }

  func create(withFrame frame: CGRect, viewIdentifier viewId: Int64, arguments args: Any?) -> FlutterPlatformView {
    GhaniTabBarView(frame: frame, id: viewId, args: args as? [String: Any] ?? [:], messenger: messenger)
  }

  func createArgsCodec() -> FlutterMessageCodec & NSObjectProtocol {
    FlutterStandardMessageCodec.sharedInstance()
  }
}

final class GhaniTabBarView: NSObject, FlutterPlatformView, UITabBarDelegate {
  private let container: UIView
  private let bar = UITabBar()
  private let channel: FlutterMethodChannel

  init(frame: CGRect, id: Int64, args: [String: Any], messenger: FlutterBinaryMessenger) {
    container = UIView(frame: frame)
    channel = FlutterMethodChannel(name: "ghani/tabbar_\(id)", binaryMessenger: messenger)
    super.init()

    container.backgroundColor = .clear
    bar.translatesAutoresizingMaskIntoConstraints = false
    bar.delegate = self
    bar.semanticContentAttribute = .forceRightToLeft
    bar.tintColor = UIColor(red: 0x5A / 255, green: 0x35 / 255, blue: 0xD6 / 255, alpha: 1)
    container.addSubview(bar)
    NSLayoutConstraint.activate([
      bar.leadingAnchor.constraint(equalTo: container.leadingAnchor),
      bar.trailingAnchor.constraint(equalTo: container.trailingAnchor),
      bar.bottomAnchor.constraint(equalTo: container.bottomAnchor),
      bar.topAnchor.constraint(equalTo: container.topAnchor),
    ])

    let items = (args["items"] as? [[String: Any]]) ?? []
    bar.items = items.enumerated().map { i, it in
      let item = UITabBarItem(
        title: it["title"] as? String,
        image: UIImage(systemName: it["icon"] as? String ?? "circle"),
        selectedImage: UIImage(systemName: it["selected"] as? String ?? it["icon"] as? String ?? "circle"))
      item.tag = i
      return item
    }
    select(args["index"] as? Int ?? 0)
    setDark(args["dark"] as? Bool ?? false)

    channel.setMethodCallHandler { [weak self] call, result in
      guard let self = self else { return }
      switch call.method {
      case "select": self.select(call.arguments as? Int ?? 0)
      case "dark": self.setDark(call.arguments as? Bool ?? false)
      default: break
      }
      result(nil)
    }
  }

  func view() -> UIView { container }

  private func select(_ i: Int) {
    guard let items = bar.items, i >= 0, i < items.count else { return }
    bar.selectedItem = items[i]
  }

  private func setDark(_ dark: Bool) {
    bar.overrideUserInterfaceStyle = dark ? .dark : .light
    bar.tintColor = dark
      ? UIColor(red: 0x9D / 255, green: 0x85 / 255, blue: 0xF7 / 255, alpha: 1)
      : UIColor(red: 0x5A / 255, green: 0x35 / 255, blue: 0xD6 / 255, alpha: 1)
  }

  func tabBar(_ tabBar: UITabBar, didSelect item: UITabBarItem) {
    channel.invokeMethod("tap", arguments: item.tag)
  }
}
