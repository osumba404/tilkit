import 'dart:convert';

import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

class LocalDb {
  LocalDb._();
  static final LocalDb instance = LocalDb._();
  Database? _db;

  Future<Database> get database async {
    if (_db != null) {
      return _db!;
    }
    final dir = await getDatabasesPath();
    _db = await openDatabase(
      join(dir, 'tilkit_field.db'),
      version: 1,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE catalog_cache (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            fetched_at TEXT NOT NULL,
            payload TEXT NOT NULL
          )
        ''');
        await db.execute('''
          CREATE TABLE sighting_drafts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sighting_uuid TEXT NOT NULL UNIQUE,
            record_uuid TEXT NOT NULL,
            sighted_at TEXT NOT NULL,
            place_text TEXT,
            landmark TEXT,
            latitude REAL,
            longitude REAL,
            observed_by TEXT,
            photo_paths TEXT,
            sync_status TEXT NOT NULL DEFAULT 'pending',
            last_error TEXT,
            created_at TEXT NOT NULL
          )
        ''');
      },
    );
    return _db!;
  }

  Future<void> saveCatalog(Map<String, dynamic> payload) async {
    final db = await database;
    await db.insert(
      'catalog_cache',
      {
        'id': 1,
        'fetched_at': DateTime.now().toUtc().toIso8601String(),
        'payload': jsonEncode(payload),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<Map<String, dynamic>?> loadCatalog() async {
    final db = await database;
    final rows = await db.query('catalog_cache', where: 'id = ?', whereArgs: [1], limit: 1);
    if (rows.isEmpty) {
      return null;
    }
    return jsonDecode(rows.first['payload'] as String) as Map<String, dynamic>;
  }

  Future<int> insertSightingDraft(Map<String, dynamic> row) async {
    final db = await database;
    return db.insert('sighting_drafts', row, conflictAlgorithm: ConflictAlgorithm.abort);
  }

  Future<List<Map<String, dynamic>>> pendingSightings() async {
    final db = await database;
    return db.query(
      'sighting_drafts',
      where: 'sync_status = ?',
      whereArgs: ['pending'],
      orderBy: 'created_at ASC',
    );
  }

  Future<void> markSightingSynced(String sightingUuid) async {
    final db = await database;
    await db.update(
      'sighting_drafts',
      {'sync_status': 'synced', 'last_error': null},
      where: 'sighting_uuid = ?',
      whereArgs: [sightingUuid],
    );
  }

  Future<void> markSightingError(String sightingUuid, String message) async {
    final db = await database;
    await db.update(
      'sighting_drafts',
      {'last_error': message},
      where: 'sighting_uuid = ?',
      whereArgs: [sightingUuid],
    );
  }

  Future<int> pendingCount() async {
    final db = await database;
    final r = Sqflite.firstIntValue(
      await db.rawQuery("SELECT COUNT(*) FROM sighting_drafts WHERE sync_status = 'pending'"),
    );
    return r ?? 0;
  }
}
