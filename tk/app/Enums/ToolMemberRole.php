<?php

namespace App\Enums;

enum ToolMemberRole: string
{
    case Admin = 'admin';
    case Collector = 'collector';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canManageTool(): bool
    {
        return $this === self::Admin;
    }

    public function canFillForms(): bool
    {
        return in_array($this, [self::Admin, self::Collector, self::Member], true);
    }

    public function canViewAllSubmissions(): bool
    {
        return in_array($this, [self::Admin, self::Viewer], true);
    }

    public function canViewAnalytics(): bool
    {
        return $this->canViewAllSubmissions();
    }

    public function canExportData(): bool
    {
        return $this === self::Admin;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Collector => 'Field collector',
            self::Member => 'Field collector',
            self::Viewer => 'Viewer',
        };
    }
}
