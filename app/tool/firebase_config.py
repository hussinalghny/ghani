"""يولد lib/firebase_config.dart من ملفات app/firebase (إذا موجودة)."""
import json, os, plistlib, sys
root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
fb = os.path.join(root, 'firebase')
android = ios = None
gs = os.path.join(fb, 'google-services.json')
if os.path.isfile(gs):
    d = json.load(open(gs))
    clients = d.get('client', [])
    c = next((x for x in clients if x['client_info']['android_client_info']['package_name'] == 'iq.ghaniacademy.ghani_academy'), clients[0] if clients else None)
    if c:
        android = dict(apiKey=c['api_key'][0]['current_key'], appId=c['client_info']['mobilesdk_app_id'],
                       messagingSenderId=d['project_info']['project_number'], projectId=d['project_info']['project_id'],
                       storageBucket=d['project_info'].get('storage_bucket', ''))
pl = os.path.join(fb, 'GoogleService-Info.plist')
if os.path.isfile(pl):
    p = plistlib.load(open(pl, 'rb'))
    ios = dict(apiKey=p['API_KEY'], appId=p['GOOGLE_APP_ID'], messagingSenderId=p['GCM_SENDER_ID'], projectId=p['PROJECT_ID'],
               storageBucket=p.get('STORAGE_BUCKET', ''), iosBundleId=p.get('BUNDLE_ID', ''))
def opt(o):
    if not o: return 'null'
    return 'FirebaseOptions(' + ', '.join(f"{k}: {json.dumps(v)}" for k, v in o.items()) + ')'
out = f"""// مولد تلقائيا من app/firebase — لا تعدله يدويا
import 'package:firebase_core/firebase_core.dart';

FirebaseOptions? firebaseOptionsFor(bool ios) => ios ? {opt(ios)} : {opt(android)};
"""
open(os.path.join(root, 'lib', 'firebase_config.dart'), 'w').write(out)
print('firebase android:', bool(android), 'ios:', bool(ios))
