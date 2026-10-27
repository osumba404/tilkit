import 'package:connectivity_plus/connectivity_plus.dart';

import 'local_db.dart';
import 'tilkit_api.dart';

class SyncService {
  SyncService({TilkitApi? api}) : _api = api ?? TilkitApi();

  final TilkitApi _api;

  Future<int> syncPendingSightings() async {
    final connectivity = await Connectivity().checkConnectivity();
    if (connectivity.contains(ConnectivityResult.none)) {
      throw TilkitApiException('No network. Sightings stay queued until you are online.');
    }

    final pending = await LocalDb.instance.pendingSightings();
    var synced = 0;
    for (final row in pending) {
      try {
        await _api.postSighting({
          'record_uuid': row['record_uuid'],
          'sighting_uuid': row['sighting_uuid'],
          'sighted_at': row['sighted_at'],
          'place_text': row['place_text'],
          'landmark': row['landmark'],
          'latitude': row['latitude'],
          'longitude': row['longitude'],
          'observed_by': row['observed_by'],
        });
        await LocalDb.instance.markSightingSynced(row['sighting_uuid'] as String);
        synced++;
      } catch (e) {
        await LocalDb.instance.markSightingError(row['sighting_uuid'] as String, e.toString());
        rethrow;
      }
    }
    return synced;
  }
}
