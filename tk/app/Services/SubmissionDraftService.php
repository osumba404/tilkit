<?php

namespace App\Services;

use App\Models\DcProject;
use App\Models\SubmissionDraft;
use App\Models\User;
use Illuminate\Support\Str;

class SubmissionDraftService
{
    public function resolveDeviceToken(?string $token): string
    {
        if ($token !== null && strlen($token) >= 32) {
            return $token;
        }

        return (string) Str::uuid();
    }

    public function save(
        DcProject $project,
        array $payload,
        ?User $user,
        ?string $deviceToken,
        ?int $formId = null,
    ): SubmissionDraft {
        $deviceToken = $user ? null : $this->resolveDeviceToken($deviceToken);

        if ($user) {
            return SubmissionDraft::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id],
                ['form_id' => $formId, 'device_token' => null, 'payload' => $payload],
            );
        }

        return SubmissionDraft::updateOrCreate(
            ['project_id' => $project->id, 'device_token' => $deviceToken],
            ['form_id' => $formId, 'user_id' => null, 'payload' => $payload],
        );
    }

    public function load(DcProject $project, ?User $user, ?string $deviceToken): ?SubmissionDraft
    {
        if ($user) {
            return SubmissionDraft::query()
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->first();
        }

        if ($deviceToken) {
            return SubmissionDraft::query()
                ->where('project_id', $project->id)
                ->where('device_token', $deviceToken)
                ->first();
        }

        return null;
    }

    public function clear(DcProject $project, ?User $user, ?string $deviceToken): void
    {
        if ($user) {
            SubmissionDraft::query()
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->delete();

            return;
        }

        if ($deviceToken) {
            SubmissionDraft::query()
                ->where('project_id', $project->id)
                ->where('device_token', $deviceToken)
                ->delete();
        }
    }
}
