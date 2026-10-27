import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';

import '../services/local_db.dart';

class SightingCaptureScreen extends StatefulWidget {
  const SightingCaptureScreen({super.key, required this.record});

  final Map<String, dynamic> record;

  @override
  State<SightingCaptureScreen> createState() => _SightingCaptureScreenState();
}

class _SightingCaptureScreenState extends State<SightingCaptureScreen> {
  final _placeController = TextEditingController();
  final _landmarkController = TextEditingController();
  final _observerController = TextEditingController();
  DateTime _sightedAt = DateTime.now();
  double? _latitude;
  double? _longitude;
  final List<String> _photoPaths = [];
  bool _saving = false;
  String? _gpsStatus;

  @override
  void dispose() {
    _placeController.dispose();
    _landmarkController.dispose();
    _observerController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _sightedAt,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );
    if (picked != null) {
      setState(() => _sightedAt = DateTime(picked.year, picked.month, picked.day, _sightedAt.hour, _sightedAt.minute));
    }
  }

  Future<void> _captureGps() async {
    setState(() => _gpsStatus = 'Getting location…');
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      setState(() => _gpsStatus = 'Turn on location services.');
      return;
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      setState(() => _gpsStatus = 'Location permission denied.');
      return;
    }
    try {
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.high));
      setState(() {
        _latitude = pos.latitude;
        _longitude = pos.longitude;
        _gpsStatus = '${pos.latitude.toStringAsFixed(5)}, ${pos.longitude.toStringAsFixed(5)}';
      });
    } catch (e) {
      setState(() => _gpsStatus = 'Could not read GPS.');
    }
  }

  Future<void> _addPhoto() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, maxWidth: 1600, imageQuality: 75);
    if (file != null) {
      setState(() => _photoPaths.add(file.path));
    }
  }

  Future<void> _saveDraft() async {
    setState(() => _saving = true);
    try {
      final uuid = const Uuid().v4();
      final iso = _sightedAt.toUtc().toIso8601String();
      await LocalDb.instance.insertSightingDraft({
        'sighting_uuid': uuid,
        'record_uuid': widget.record['uuid'],
        'sighted_at': iso,
        'place_text': _placeController.text.trim().isEmpty ? null : _placeController.text.trim(),
        'landmark': _landmarkController.text.trim().isEmpty ? null : _landmarkController.text.trim(),
        'latitude': _latitude,
        'longitude': _longitude,
        'observed_by': _observerController.text.trim().isEmpty ? null : _observerController.text.trim(),
        'photo_paths': _photoPaths.isEmpty ? null : jsonEncode(_photoPaths),
        'sync_status': 'pending',
        'last_error': null,
        'created_at': DateTime.now().toUtc().toIso8601String(),
      });
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sighting saved on device. Sync when online.')),
      );
      Navigator.of(context).pop(true);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.record['indicator_name'] ?? widget.record['indicator_uid'] ?? 'Indicator';
    final site = widget.record['site_name'] ?? '';
    final season = widget.record['season_label'] ?? '';

    return Scaffold(
      appBar: AppBar(title: const Text('B. With every sighting')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          if (site.isNotEmpty || season.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text('$site · $season', style: Theme.of(context).textTheme.bodyMedium),
            ),
          const SizedBox(height: 24),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Date observed'),
            subtitle: Text(DateFormat.yMMMd().format(_sightedAt)),
            trailing: const Icon(Icons.calendar_today),
            onTap: _pickDate,
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _placeController,
            decoration: const InputDecoration(labelText: 'Place / description'),
            minLines: 1,
            maxLines: 3,
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _landmarkController,
            decoration: const InputDecoration(labelText: 'Landmark (optional)'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _observerController,
            decoration: const InputDecoration(labelText: 'Observed by (optional)'),
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: _captureGps,
            icon: const Icon(Icons.my_location),
            label: const Text('Capture GPS'),
          ),
          if (_gpsStatus != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(_gpsStatus!),
            ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _addPhoto,
            icon: const Icon(Icons.photo_camera),
            label: Text(_photoPaths.isEmpty ? 'Add photo (local only)' : '${_photoPaths.length} photo(s)'),
          ),
          const SizedBox(height: 32),
          FilledButton(
            onPressed: _saving ? null : _saveDraft,
            child: _saving
                ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Save sighting on device'),
          ),
        ],
      ),
    );
  }
}
