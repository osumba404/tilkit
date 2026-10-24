<?php

namespace Tests\Unit;

use App\Support\Http\FriendlyHttpErrors;
use PHPUnit\Framework\TestCase;

class FriendlyHttpErrorsTest extends TestCase
{
    public function test_sanitize_hides_sql_state(): void
    {
        $raw = "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '3-iwiwi' for key 'tilkit_programs_project_id_name_unique'";

        $this->assertStringNotContainsString('SQLSTATE', FriendlyHttpErrors::sanitize($raw));
    }

    public function test_from_json_payload_prefers_validation_errors(): void
    {
        $message = FriendlyHttpErrors::fromJsonPayload([
            'message' => 'The given data was invalid.',
            'errors' => ['name' => ['A program with this name already exists.']],
        ]);

        $this->assertSame('A program with this name already exists.', $message);
    }
}
