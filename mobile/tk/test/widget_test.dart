import 'package:flutter_test/flutter_test.dart';
import 'package:tk/main.dart';

void main() {
  testWidgets('Login screen shows TILKIT Field title', (WidgetTester tester) async {
    await tester.pumpWidget(const TilkitFieldApp());
    await tester.pumpAndSettle();
    expect(find.text('TILKIT Field'), findsOneWidget);
  });
}
