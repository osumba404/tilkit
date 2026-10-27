import 'package:flutter/material.dart';
import '../config/app_theme.dart';
import '../services/local_db.dart';
import '../services/settings_store.dart';
import '../services/sync_service.dart';
import '../services/tilkit_api.dart';
import 'login_screen.dart';
import 'sighting_capture_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.api});

  final TilkitApi api;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? _catalog;
  String? _userName;
  int _pending = 0;
  bool _loading = true;
  String? _error;
  String _filter = '';

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh({bool pullBootstrap = true}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _userName = await SettingsStore.userName();
      if (pullBootstrap) {
        try {
          final fresh = await widget.api.bootstrap();
          await LocalDb.instance.saveCatalog(fresh);
          _catalog = fresh;
        } on TilkitApiException {
          _catalog = await LocalDb.instance.loadCatalog();
          if (_catalog == null) {
            rethrow;
          }
        }
      } else {
        _catalog = await LocalDb.instance.loadCatalog();
      }
      _pending = await LocalDb.instance.pendingCount();
    } on TilkitApiException catch (e) {
      _error = e.message;
      _catalog ??= await LocalDb.instance.loadCatalog();
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _sync() async {
    final sync = SyncService(api: widget.api);
    try {
      final n = await sync.syncPendingSightings();
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(n == 0 ? 'Nothing to sync.' : 'Synced $n sighting(s).')),
      );
      await _refresh(pullBootstrap: false);
    } on TilkitApiException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      await _refresh(pullBootstrap: false);
    }
  }

  Future<void> _signOut() async {
    await widget.api.clearSession();
    if (!mounted) {
      return;
    }
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  List<Map<String, dynamic>> get _records {
    final raw = _catalog?['records'];
    if (raw is! List) {
      return [];
    }
    final list = raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    if (_filter.trim().isEmpty) {
      return list;
    }
    final q = _filter.toLowerCase();
    return list.where((r) {
      final name = '${r['indicator_name']} ${r['indicator_uid']} ${r['site_name']}'.toLowerCase();
      return name.contains(q);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final toolName = _catalog?['tool']?['name'] ?? 'TILKIT';
    final fetched = _catalog != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(toolName),
        actions: [
          IconButton(
            tooltip: 'Sync queued sightings',
            onPressed: _loading ? null : _sync,
            icon: Badge(
              isLabelVisible: _pending > 0,
              label: Text('$_pending'),
              child: const Icon(Icons.cloud_upload_outlined),
            ),
          ),
          IconButton(tooltip: 'Sign out', onPressed: _signOut, icon: const Icon(Icons.logout)),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _refresh(pullBootstrap: true),
        child: _loading && !fetched
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_userName != null)
                    Text('Signed in as $_userName', style: const TextStyle(color: AppTheme.muted)),
                  if (_error != null) ...[
                    const SizedBox(height: 8),
                    Material(
                      color: Theme.of(context).colorScheme.errorContainer,
                      borderRadius: BorderRadius.circular(12),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(_error!),
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextField(
                    decoration: const InputDecoration(
                      prefixIcon: Icon(Icons.search),
                      hintText: 'Search indicator or site',
                    ),
                    onChanged: (v) => setState(() => _filter = v),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Select a record, then log a sighting (date, place, GPS, photo). '
                    'Sightings queue on this device until you sync.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.muted),
                  ),
                  const SizedBox(height: 16),
                  ..._records.map(_recordTile),
                  if (_records.isEmpty)
                    const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(child: Text('No indicator records in cache. Pull to refresh when online.')),
                    ),
                ],
              ),
      ),
    );
  }

  Widget _recordTile(Map<String, dynamic> record) {
    final sighted = record['sighted'] == true;
    final year = record['collection_year'];
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        minVerticalPadding: 14,
        title: Text(record['indicator_name']?.toString() ?? record['indicator_uid']?.toString() ?? 'Record'),
        subtitle: Text(
          [
            record['site_name'],
            record['season_label'],
            if (year != null) year.toString(),
          ].whereType<String>().where((s) => s.isNotEmpty).join(' · '),
        ),
        trailing: sighted
            ? const Icon(Icons.check_circle, color: AppTheme.accent)
            : const Icon(Icons.radio_button_unchecked),
        onTap: () async {
          final saved = await Navigator.of(context).push<bool>(
            MaterialPageRoute(builder: (_) => SightingCaptureScreen(record: record)),
          );
          if (saved == true) {
            await _refresh(pullBootstrap: false);
          }
        },
      ),
    );
  }
}
