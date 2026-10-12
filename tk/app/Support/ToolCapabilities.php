<?php

namespace App\Support;

use App\Enums\ToolMemberRole;
use App\Models\ToolMember;

class ToolCapabilities
{
    public function __construct(
        public ?ToolMemberRole $role,
        public ?ToolMember $membership = null,
    ) {}

    public function canManageTool(): bool
    {
        return $this->role?->canManageTool() ?? false;
    }

    public function canFillForms(): bool
    {
        return $this->role?->canFillForms() ?? false;
    }

    public function canViewAllSubmissions(): bool
    {
        return $this->role?->canViewAllSubmissions() ?? false;
    }

    public function canViewAnalytics(): bool
    {
        return $this->canViewAllSubmissions();
    }

    public function canExportData(): bool
    {
        if ($this->membership?->can_export !== null) {
            return (bool) $this->membership->can_export;
        }

        return $this->role?->canExportData() ?? false;
    }

    public function canPublishForms(): bool
    {
        if ($this->membership?->can_publish_forms !== null) {
            return (bool) $this->membership->can_publish_forms;
        }

        return $this->role?->canManageTool() ?? false;
    }

    public function canReviewSubmissions(): bool
    {
        return $this->canViewAllSubmissions();
    }

    public function label(): string
    {
        return $this->role?->label() ?? '—';
    }
}
