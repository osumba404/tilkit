<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsPendingInvitation;
use App\Http\Controllers\Controller;
use App\Models\ToolInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use RedirectsPendingInvitation;
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $inviteEmail = null;
        if ($token = session('invitation_token')) {
            $inviteEmail = ToolInvitation::query()
                ->where('token', $token)
                ->whereNull('accepted_at')
                ->value('email');
        }

        return view('auth.register', ['inviteEmail' => $inviteEmail]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($token = session('invitation_token')) {
            $invitedEmail = ToolInvitation::query()
                ->where('token', $token)
                ->whereNull('accepted_at')
                ->value('email');
            if ($invitedEmail !== null) {
                $rules['email'][] = 'in:'.Str::lower($invitedEmail);
            }
        }

        $request->validate($rules);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $this->redirectAfterAuth();
    }
}
