<?php

namespace App\Enums;

enum PlatformRole: string
{
    case Member = 'member';
    case SuperAdmin = 'super_admin';
}
