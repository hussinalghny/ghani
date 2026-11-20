// حماية غني أكاديمي لنسخة ويندوز (يضيفها tool/prepare_windows.sh)
#include <tlhelp32.h>
#include <flutter/method_channel.h>
#include <flutter/standard_method_codec.h>
#include <memory>
#include <string>

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

static std::unique_ptr<flutter::MethodChannel<flutter::EncodableValue>> g_channel;

// WDA_EXCLUDEFROMCAPTURE: النافذة تطلع سودة بأي سكرين شوت أو تسجيل (Windows 10 2004+)
inline void Protect(HWND hwnd, flutter::BinaryMessenger* messenger) {
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
