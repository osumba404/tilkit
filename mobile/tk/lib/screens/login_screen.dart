import 'package:flutter/material.dart';

import '../config/app_theme.dart';
import '../services/settings_store.dart';
import '../services/tilkit_api.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _baseUrlController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _api = TilkitApi();
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDefaults();
  }

  Future<void> _loadDefaults() async {
    _baseUrlController.text = await SettingsStore.baseUrl();
    final email = await SettingsStore.lastEmail();
    if (email != null) {
      _emailController.text = email;
    }
    setState(() {});
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await SettingsStore.setBaseUrl(_baseUrlController.text.trim());
      await SettingsStore.setLastEmail(_emailController.text.trim());
      await _api.login(_emailController.text.trim(), _passwordController.text);
      if (!mounted) {
        return;
      }
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => HomeScreen(api: _api)),
      );
    } on TilkitApiException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  void dispose() {
    _baseUrlController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 24),
            Text(
              'TILKIT Field',
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: AppTheme.ink,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              'Community Data List - sighting capture (offline-first).',
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: AppTheme.muted),
            ),
            const SizedBox(height: 32),
            Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextFormField(
                    controller: _baseUrlController,
                    decoration: const InputDecoration(
                      labelText: 'Server URL',
                      hintText: 'http://10.0.2.2:8000',
                    ),
                    keyboardType: TextInputType.url,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _emailController,
                    decoration: const InputDecoration(labelText: 'Email'),
                    keyboardType: TextInputType.emailAddress,
                    autofillHints: const [AutofillHints.email],
                    validator: (v) => (v == null || !v.contains('@')) ? 'Enter a valid email' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _passwordController,
                    decoration: const InputDecoration(labelText: 'Password'),
                    obscureText: true,
                    autofillHints: const [AutofillHints.password],
                    validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _busy ? null : _submit,
                    child: _busy
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Sign in'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
