import 'package:shared_preferences/shared_preferences.dart';

class SettingsStore {
  static const _baseUrlKey = 'tilkit_api_base_url';
  static const _emailKey = 'tilkit_last_email';
  static const _sessionCookieKey = 'tilkit_session_cookie';
  static const _userNameKey = 'tilkit_user_name';

  static Future<String> baseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_baseUrlKey) ?? 'http://10.0.2.2:8000';
  }

  static Future<void> setBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_baseUrlKey, url.replaceAll(RegExp(r'/+$'), ''));
  }

  static Future<String?> lastEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_emailKey);
  }

  static Future<void> setLastEmail(String email) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_emailKey, email);
  }

  static Future<String?> sessionCookie() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_sessionCookieKey);
  }

  static Future<void> setSessionCookie(String? cookie) async {
    final prefs = await SharedPreferences.getInstance();
    if (cookie == null || cookie.isEmpty) {
      await prefs.remove(_sessionCookieKey);
    } else {
      await prefs.setString(_sessionCookieKey, cookie);
    }
  }

  static Future<String?> userName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_userNameKey);
  }

  static Future<void> setUserName(String? name) async {
    final prefs = await SharedPreferences.getInstance();
    if (name == null) {
      await prefs.remove(_userNameKey);
    } else {
      await prefs.setString(_userNameKey, name);
    }
  }

  static Future<void> clearSession() async {
    await setSessionCookie(null);
    await setUserName(null);
  }
}
