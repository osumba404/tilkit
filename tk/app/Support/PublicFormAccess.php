<?php

namespace App\Support;

use App\Models\DcForm;
use App\Models\User;

class PublicFormAccess
{
    public static function canFill(DcForm $form, ?User $user): bool
    {
        if ($user !== null) {
            return true;
        }

        if ($form->allow_anonymous) {
            return true;
        }

        $form->loadMissing('project');

        return (bool) ($form->project?->public_collection_enabled ?? false);
    }
}
