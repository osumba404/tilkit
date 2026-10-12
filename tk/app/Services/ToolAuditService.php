<?php

namespace App\Services;

use App\Models\DcProject;
use App\Models\ToolAuditLog;
use App\Models\User;

class ToolAuditService
{
    public function log(
        DcProject $project,
        ?User $user,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $meta = null,
    ): ToolAuditLog {
        return ToolAuditLog::create([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
