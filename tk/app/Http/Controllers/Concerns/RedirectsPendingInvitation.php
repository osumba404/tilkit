<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsPendingInvitation
{
    protected function redirectAfterAuth(): RedirectResponse
    {
        if ($token = session('invitation_token')) {
            return redirect()->route('invitations.complete', $token);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
