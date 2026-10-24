<?php

namespace App\Support\Http;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FriendlyHttpErrors
{
    public static function fromQueryException(QueryException $exception): string
    {
        $code = (int) ($exception->errorInfo[1] ?? 0);
        $detail = strtolower($exception->getMessage());

        if ($code === 1062 || str_contains($detail, 'duplicate entry')) {
            if (str_contains($detail, 'tilkit_programs')) {
                return 'A program with this name already exists in this workspace. Use a different name or edit the existing program.';
            }
            if (str_contains($detail, 'tilkit_sites')) {
                return 'A site with this name already exists in this program. Use a different site name.';
            }
            if (str_contains($detail, 'tilkit_seasons')) {
                return 'This season overlaps one that is already saved for this program.';
            }

            return 'That value is already in use. Please change it and try again.';
        }

        if ($code === 1451 || str_contains($detail, 'foreign key constraint')) {
            return 'This item is linked to other records and cannot be saved that way. Check your selections and try again.';
        }

        if ($code === 1452) {
            return 'Something you selected is no longer available. Refresh the page and try again.';
        }

        return 'We could not save your changes. Please check the form and try again.';
    }

    /** @param  array<string, mixed>  $payload */
    public static function fromJsonPayload(array $payload): string
    {
        $errors = $payload['errors'] ?? null;
        if (is_array($errors)) {
            foreach ($errors as $messages) {
                if (is_array($messages) && isset($messages[0]) && is_string($messages[0])) {
                    return $messages[0];
                }
                if (is_string($messages)) {
                    return $messages;
                }
            }
        }

        if (! empty($payload['message']) && is_string($payload['message']) && ! self::looksLikeSql($payload['message'])) {
            return $payload['message'];
        }

        return 'Something went wrong. Please check the form and try again.';
    }

    public static function looksLikeSql(string $message): bool
    {
        return str_contains($message, 'SQLSTATE[')
            || str_contains($message, 'SQL:')
            || str_contains($message, 'Connection:');
    }

    public static function sanitize(string $message): string
    {
        if (self::looksLikeSql($message)) {
            return 'Something went wrong while saving. Please try again or contact support if it keeps happening.';
        }

        return $message;
    }

    public static function renderValidation(ValidationException $exception, Request $request): ?\Symfony\Component\HttpFoundation\Response
    {
        if (! $request->expectsJson()) {
            return null;
        }

        $payload = $exception->errors();
        $first = '';
        foreach ($payload as $messages) {
            $first = is_array($messages) ? ($messages[0] ?? '') : (string) $messages;
            if ($first !== '') {
                break;
            }
        }

        return response()->json([
            'message' => $first !== '' ? $first : 'Please fix the highlighted fields and try again.',
            'errors' => $payload,
        ], $exception->status);
    }

    public static function renderQueryException(QueryException $exception, Request $request): ?\Symfony\Component\HttpFoundation\Response
    {
        $message = self::fromQueryException($exception);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['form' => $message]);
        }

        return null;
    }
}
