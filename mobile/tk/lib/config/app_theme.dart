import 'package:flutter/material.dart';

class AppTheme {
  static const Color accent = Color(0xFF0A7C94);
  static const Color ink = Color(0xFF0F1419);
  static const Color muted = Color(0xFF5B6573);

  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(seedColor: accent, brightness: Brightness.light),
      scaffoldBackgroundColor: const Color(0xFFF3F5F7),
      appBarTheme: const AppBarTheme(centerTitle: false, elevation: 0),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        filled: true,
        fillColor: Colors.white,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
    );
  }
}
