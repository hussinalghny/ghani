; مثبت غني أكاديمي لويندوز (Inno Setup) — بدون صلاحيات مدير
#define AppVer GetEnv("APP_VER")
[Setup]
AppId={{6C1E7B52-4A0F-4D3B-9A57-3E1D2C9B8A71}
AppName=Ghani Academy
AppVersion={#AppVer}
AppPublisher=Ghani Code
AppPublisherURL=https://ghaniacademy.online
DefaultDirName={localappdata}\Programs\Ghani Academy
DefaultGroupName=Ghani Academy
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
OutputDir=..\..\dist
OutputBaseFilename=GhaniAcademy-Setup
SetupIconFile=..\..\windows\runner\resources\app_icon.ico
UninstallDisplayIcon={app}\ghani_academy.exe
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0.19041

[Tasks]
Name: "desktopicon"; Description: "Desktop shortcut"; GroupDescription: "Shortcuts:"

[Files]
Source: "..\..\build\windows\x64\runner\Release\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\Ghani Academy"; Filename: "{app}\ghani_academy.exe"
Name: "{userdesktop}\Ghani Academy"; Filename: "{app}\ghani_academy.exe"; Tasks: desktopicon

[Run]
Filename: "{app}\ghani_academy.exe"; Description: "Open Ghani Academy"; Flags: nowait postinstall skipifsilent
