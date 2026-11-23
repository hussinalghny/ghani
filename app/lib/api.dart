import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:android_id/android_id.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import 'ui.dart' show ResCache;

/// دومين المنصة. يتغير وقت البناء: --dart-define=API_BASE=https://...
const apiBase = String.fromEnvironment('API_BASE', defaultValue: 'https://ghaniacademy.online');

class ApiException implements Exception {
  final String message;
  final String? code;
  final int status;
  ApiException(this.message, {this.code, this.status = 0});
  @override
  String toString() => message;
}

class Api {
  Api._();
  static final Api i = Api._();

  final _store = const FlutterSecureStorage(aOptions: AndroidOptions(encryptedSharedPreferences: true));
  String? token;
  Map<String, dynamic>? user;

  /// يتغير لما المستخدم يسجل دخول أو يطلع
  final loggedIn = ValueNotifier<bool>(false);

  Future<void> init() async {
    token = await _store.read(key: 'token');
    loggedIn.value = token != null;
  }

  Future<Map<String, String>> _device() async {
    String? id;
    var name = 'Phone';
    try {
      if (Platform.isAndroid) {
        id = await const AndroidId().getId();
        final a = await DeviceInfoPlugin().androidInfo;
        name = '${a.manufacturer} ${a.model}';
      } else if (Platform.isWindows) {
        final w = await DeviceInfoPlugin().windowsInfo;
        id = 'win-${w.deviceId}';
        name = w.computerName;
      } else if (Platform.isIOS) {
        final s = await DeviceInfoPlugin().iosInfo;
        id = s.identifierForVendor;
        name = s.name;
      }
    } catch (_) {}
    id ??= await _store.read(key: 'device_id');
    id ??= 'gen-${DateTime.now().microsecondsSinceEpoch}';
    await _store.write(key: 'device_id', value: id);
    return {'device_id': id, 'device_name': name, 'platform': Platform.isIOS ? 'ios' : (Platform.isWindows ? 'windows' : 'android')};
  }

  Future<dynamic> req(String method, String path, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('$apiBase/api/v1$path');
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (Platform.isWindows || Platform.isMacOS) 'X-Client': 'desktop',
    };
    http.Response r;
    try {
      final Future<http.Response> f;
      if (method == 'DELETE') {
        f = http.delete(uri, headers: headers, body: jsonEncode(body ?? {}));
      } else if (method == 'POST') {
        f = http.post(uri, headers: headers, body: jsonEncode(body ?? {}));
      } else if (method == 'PUT') {
        f = http.put(uri, headers: headers, body: jsonEncode(body ?? {}));
      } else {
        f = http.get(uri, headers: headers);
      }
      r = await f.timeout(const Duration(seconds: 25));
    } on TimeoutException {
      throw ApiException('الاتصال بطيء. أعد المحاولة.');
    } catch (_) {
      throw ApiException('تحقق من اتصال الإنترنت.');
    }

    dynamic data;
    try {
      data = r.body.isEmpty ? null : jsonDecode(utf8.decode(r.bodyBytes));
    } catch (_) {
      data = null;
    }

    if (r.statusCode >= 400) {
      final msg = data is Map && data['message'] is String ? data['message'] as String : 'حدث خطأ. أعد المحاولة.';
      final code = data is Map ? data['code']?.toString() : null;
      if (r.statusCode == 401 && token != null) await _clear();
      throw ApiException(msg, code: code, status: r.statusCode);
    }
    return data;
  }

  Future<void> login(String phone, String password) async {
    final d = await req('POST', '/auth/login', body: {'phone': phone, 'password': password, ...await _device()});
    await _save(d);
  }

  Future<void> register(String name, String phone, String city, String password) async {
    final d = await req('POST', '/auth/register',
        body: {'name': name, 'phone': phone, 'city': city, 'password': password, ...await _device()});
    await _save(d);
  }

  Future<void> _save(dynamic d) async {
    token = d['token'] as String;
    user = Map<String, dynamic>.from(d['user'] as Map);
    await _store.write(key: 'token', value: token);
    loggedIn.value = true;
  }

  Future<Map<String, dynamic>> me() async {
    final d = await req('GET', '/me');
    user = Map<String, dynamic>.from((d['data'] ?? d) as Map);
    return user!;
  }

  /// نسخة المتاجر (Apple/Google): بدون شراء أو محفظة داخل التطبيق
  static const storeBuild = bool.fromEnvironment('STORE_BUILD');

  String? pushToken;

  Future<void> logout() async {
    try {
      if (pushToken != null) await req('DELETE', '/me/push-token', body: {'token': pushToken});
    } catch (_) {}
    try {
      await req('POST', '/auth/logout');
    } catch (_) {}
    await _clear();
  }

  /// حذف الحساب نهائيا
  Future<void> deleteAccount(String password) async {
    await req('DELETE', '/me', body: {'password': password});
    await _clear();
  }

  Map<String, String> get authHeaders => {if (token != null) 'Authorization': 'Bearer $token'};

  Future<void> _clear() async {
    ResCache.clear();
    token = null;
    user = null;
    await _store.delete(key: 'token');
    loggedIn.value = false;
  }
}
