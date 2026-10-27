import 'package:flutter/material.dart';

import 'config/app_theme.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'services/settings_store.dart';
import 'services/tilkit_api.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const TilkitFieldApp());
}

class TilkitFieldApp extends StatelessWidget {
  const TilkitFieldApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'TILKIT Field',
      theme: AppTheme.light(),
      home: const _BootstrapGate(),
    );
  }
}

class _BootstrapGate extends StatefulWidget {
  const _BootstrapGate();

  @override
  State<_BootstrapGate> createState() => _BootstrapGateState();
}

class _BootstrapGateState extends State<_BootstrapGate> {
  late final TilkitApi _api;
  bool _ready = false;
  bool _hasSession = false;

  @override
  void initState() {
    super.initState();
    _api = TilkitApi();
    _init();
  }

  Future<void> _init() async {
    await _api.restoreSession();
    final cookie = await SettingsStore.sessionCookie();
    setState(() {
      _hasSession = cookie != null && cookie.isNotEmpty;
      _ready = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!_ready) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_hasSession) {
      return HomeScreen(api: _api);
    }
    return const LoginScreen();
  }
}
