<?php

namespace App\Enums;

enum ToolMemberRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canManageTool(): bool
    {
        return $this === self::Admin;
    }

    public function canFillForms(): bool
    {
        return $this !== self::Viewer;
    }

    public function canViewAnalytics(): bool
    {
        return true;
    }
}
