import 'dart:convert';

import 'package:http/http.dart' as http;

import 'settings_store.dart';

class TilkitApi {
  TilkitApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  String? _sessionCookie;

  Future<void> restoreSession() async {
    _sessionCookie = await SettingsStore.sessionCookie();
  }

  Future<void> clearSession() async {
    _sessionCookie = null;
    await SettingsStore.clearSession();
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    final base = await SettingsStore.baseUrl();
    final uri = Uri.parse('$base/api/tilkit/mobile/login');
    final response = await _client.post(
      uri,
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    await _captureSession(response);
    if (response.statusCode != 200) {
      throw TilkitApiException(_messageFromBody(response.body) ?? 'Login failed (${response.statusCode})');
    }
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final user = body['user'];
    if (user is Map && user['name'] is String) {
      await SettingsStore.setUserName(user['name'] as String);
    }
    return body;
  }

  Future<Map<String, dynamic>> bootstrap({int? programId}) async {
    final base = await SettingsStore.baseUrl();
    final query = programId != null ? '?program_id=$programId' : '';
    final uri = Uri.parse('$base/api/tilkit/mobile/bootstrap$query');
    final response = await _client.get(uri, headers: _headers());
    if (response.statusCode == 401) {
      throw TilkitApiException('Session expired. Sign in again.');
    }
    if (response.statusCode != 200) {
      throw TilkitApiException('Bootstrap failed (${response.statusCode})');
    }
    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> postSighting(Map<String, dynamic> body) async {
    final base = await SettingsStore.baseUrl();
    final uri = Uri.parse('$base/api/tilkit/sightings');
    final response = await _client.post(
      uri,
      headers: _headers(),
      body: jsonEncode(body),
    );
    if (response.statusCode == 401) {
      throw TilkitApiException('Session expired. Sign in again.');
    }
    if (response.statusCode != 200) {
      throw TilkitApiException(_messageFromBody(response.body) ?? 'Sync failed (${response.statusCode})');
    }
  }

  Map<String, String> _headers() {
    final h = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (_sessionCookie != null) {
      h['Cookie'] = _sessionCookie!;
    }
    return h;
  }

  Future<void> _captureSession(http.Response response) async {
    final parts = <String>[];
    response.headers.forEach((key, value) {
      if (key.toLowerCase() != 'set-cookie') {
        return;
      }
      for (final raw in _splitSetCookieHeader(value)) {
        final nameValue = raw.split(';').first.trim();
        if (nameValue.contains('=')) {
          parts.add(nameValue);
        }
      }
    });
    if (parts.isEmpty) {
      return;
    }
    _sessionCookie = parts.join('; ');
    await SettingsStore.setSessionCookie(_sessionCookie);
  }

  /// Splits combined Set-Cookie header values (comma between cookies, not Expires).
  static List<String> _splitSetCookieHeader(String header) {
    return header.split(RegExp(r', (?=[^;,]+=)'));
  }

  String? _messageFromBody(String body) {
    try {
      final map = jsonDecode(body) as Map<String, dynamic>;
      if (map['message'] is String) {
        return map['message'] as String;
      }
      final errors = map['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          return first.first.toString();
        }
      }
    } catch (_) {}
    return null;
  }
}

class TilkitApiException implements Exception {
  TilkitApiException(this.message);
  final String message;
  @override
  String toString() => message;
}
