// حماية غني أكاديمي لنسخة ويندوز (يضيفها tool/prepare_windows.sh)
#include <tlhelp32.h>
#include <flutter/method_channel.h>
#include <flutter/standard_method_codec.h>
#include <chrono>
#include <memory>
#include <string>
#include <thread>

namespace ghani_guard {

static const wchar_t* kBad[] = {
    L"obs64.exe", L"obs32.exe", L"obs.exe", L"bdcam.exe", L"bandicam.exe",
    L"camtasia.exe", L"camtasiastudio.exe", L"camrecorder.exe", L"recorder.exe",
    L"fraps.exe", L"sharex.exe", L"screenrec.exe", L"xsplit.core.exe",
    L"streamlabs obs.exe", L"apowerrec.exe", L"apowersoft screen recorder.exe",
    L"ocam.exe", L"debut.exe", L"icecreamscreenrecorder.exe", L"flashback recorder.exe",
    L"fbrecorder.exe", L"screenpresso.exe", L"snagit32.exe", L"snagiteditor.exe",
    L"action.exe", L"mirillis.exe", L"gamebar.exe", L"anydesk.exe", L"teamviewer.exe"};
static const char* kBadA[] = {
    "obs64.exe", "obs32.exe", "obs.exe", "bdcam.exe", "bandicam.exe",
    "camtasia.exe", "camtasiastudio.exe", "camrecorder.exe", "recorder.exe",
    "fraps.exe", "sharex.exe", "screenrec.exe", "xsplit.core.exe",
    "streamlabs obs.exe", "apowerrec.exe", "apowersoft screen recorder.exe",
    "ocam.exe", "debut.exe", "icecreamscreenrecorder.exe", "flashback recorder.exe",
    "fbrecorder.exe", "screenpresso.exe", "snagit32.exe", "snagiteditor.exe",
    "action.exe", "mirillis.exe", "gamebar.exe", "anydesk.exe", "teamviewer.exe"};

static std::string FindRecorder() {
  std::string found;
  HANDLE snap = CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);
  if (snap == INVALID_HANDLE_VALUE) return found;
  PROCESSENTRY32W pe;
  pe.dwSize = sizeof(pe);
  if (Process32FirstW(snap, &pe)) {
    do {
      for (size_t i = 0; i < sizeof(kBad) / sizeof(kBad[0]); ++i) {
        if (_wcsicmp(pe.szExeFile, kBad[i]) == 0) {
          found = kBadA[i];
          break;
        }
      }
    } while (found.empty() && Process32NextW(snap, &pe));
  }
  CloseHandle(snap);
  return found;
}

// أدوات الهندسة العكسية واعتراض الشبكة: التطبيق يسكر إذا اشتغلت
static const wchar_t* kTools[] = {
    L"x64dbg.exe", L"x32dbg.exe", L"x96dbg.exe", L"ollydbg.exe", L"ida.exe", L"ida64.exe",
    L"idaq.exe", L"idaq64.exe", L"windbg.exe", L"ghidra.exe", L"ghidrarun.exe", L"dnspy.exe",
    L"cheatengine-x86_64.exe", L"cheatengine-x86_64-sse4-avx2.exe", L"cheatengine.exe",
    L"processhacker.exe", L"systeminformer.exe", L"httpdebuggerui.exe", L"httpdebuggersvc.exe",
    L"fiddler.exe", L"fiddler everywhere.exe", L"charles.exe", L"mitmproxy.exe", L"mitmdump.exe",
    L"burpsuite.exe", L"burp.exe", L"wireshark.exe", L"proxifier.exe", L"reqable.exe",
    L"scylla.exe", L"scylla_x64.exe", L"reshacker.exe", L"die.exe", L"frida.exe", L"frida-server.exe"};

static bool ToolRunning() {
  bool found = false;
  HANDLE snap = CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);
  if (snap == INVALID_HANDLE_VALUE) return false;
  PROCESSENTRY32W pe;
  pe.dwSize = sizeof(pe);
  if (Process32FirstW(snap, &pe)) {
    do {
      for (size_t i = 0; i < sizeof(kTools) / sizeof(kTools[0]); ++i) {
        if (_wcsicmp(pe.szExeFile, kTools[i]) == 0) {
          found = true;
          break;
        }
      }
    } while (!found && Process32NextW(snap, &pe));
  }
  CloseHandle(snap);
  return found;
}

static bool Debugged() {
  BOOL remote = FALSE;
  CheckRemoteDebuggerPresent(GetCurrentProcess(), &remote);
  return IsDebuggerPresent() || remote;
}

static void Bail() {
  MessageBoxW(nullptr, L"\u062A\u0645 \u0625\u064A\u0642\u0627\u0641 \u063A\u0646\u064A \u0623\u0643\u0627\u062F\u064A\u0645\u064A: \u0623\u063A\u0644\u0642 \u0623\u062F\u0648\u0627\u062A \u0627\u0644\u062A\u062D\u0644\u064A\u0644 \u0648\u0627\u0644\u062A\u0635\u062D\u064A\u062D \u0648\u0623\u0639\u062F \u0627\u0644\u0641\u062A\u062D.",
              L"Ghani Academy", MB_OK | MB_ICONSTOP | MB_TOPMOST);
  ExitProcess(0);
}

// فحص عند التشغيل وكل 3 ثواني
inline void StartAntiTamper() {
  if (Debugged() || ToolRunning()) Bail();
  std::thread([] {
    for (;;) {
      std::this_thread::sleep_for(std::chrono::seconds(3));
      if (Debugged() || ToolRunning()) Bail();
    }
  }).detach();
}

static std::unique_ptr<flutter::MethodChannel<flutter::EncodableValue>> g_channel;

// WDA_EXCLUDEFROMCAPTURE: النافذة تطلع سودة بأي سكرين شوت أو تسجيل (Windows 10 2004+)
inline void Protect(HWND hwnd, flutter::BinaryMessenger* messenger) {
  StartAntiTamper();
  if (!SetWindowDisplayAffinity(hwnd, 0x00000011)) {
    SetWindowDisplayAffinity(hwnd, 0x00000001);  // WDA_MONITOR للأنظمة الأقدم
  }
  g_channel = std::make_unique<flutter::MethodChannel<flutter::EncodableValue>>(
      messenger, "ghani/guard", &flutter::StandardMethodCodec::GetInstance());
  g_channel->SetMethodCallHandler(
      [](const flutter::MethodCall<flutter::EncodableValue>& call,
         std::unique_ptr<flutter::MethodResult<flutter::EncodableValue>> result) {
        if (call.method_name() == "recorder") {
          result->Success(flutter::EncodableValue(FindRecorder()));
        } else {
          result->NotImplemented();
        }
      });
}

}  // namespace ghani_guard
