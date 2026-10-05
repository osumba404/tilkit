<?php

namespace Tests\Unit;

use App\Support\DateTimeFieldValidator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DateTimeFieldValidatorTest extends TestCase
{
    #[DataProvider('validDates')]
    public function test_normalizes_date(string $input, string $expected): void
    {
        $this->assertSame($expected, DateTimeFieldValidator::parseAndValidate('date', $input, 'd'));
    }

    public static function validDates(): array
    {
        return [
            ['2026-10-05', '2026-10-05'],
        ];
    }

    #[DataProvider('validTimes')]
    public function test_normalizes_time(string $input, string $expected): void
    {
        $this->assertSame($expected, DateTimeFieldValidator::parseAndValidate('time', $input, 't'));
    }

    public static function validTimes(): array
    {
        return [
            ['14:30', '14:30:00'],
            ['09:05:07', '09:05:07'],
        ];
    }

    #[DataProvider('validDatetimes')]
    public function test_normalizes_datetime(string $input, string $expected): void
    {
        $this->assertSame($expected, DateTimeFieldValidator::parseAndValidate('datetime', $input, 'dt'));
    }

    public static function validDatetimes(): array
    {
        return [
            ['2026-10-05T14:30', '2026-10-05T14:30:00'],
            ['2026-10-05T14:30:45', '2026-10-05T14:30:45'],
            ['2026-10-05 14:30:45', '2026-10-05T14:30:45'],
        ];
    }

    public function test_rejects_invalid_date(): void
    {
        $this->expectException(ValidationException::class);
        DateTimeFieldValidator::parseAndValidate('date', '05/10/2026', 'd');
    }
}
