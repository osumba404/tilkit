<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Models\ToolInvitation;
use App\Services\ToolAccessService;
use App\Services\ToolInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ToolInvitationController extends Controller
{
    public function store(Request $request, DcProject $tool, ToolAccessService $access, ToolInvitationService $invites): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $invites->invite($tool, $request->user(), $validated['email']);

        return back()->with('status', 'Invitation sent to '.$validated['email'].'.');
    }

    public function acceptForm(string $token): View|RedirectResponse
    {
        $invitation = ToolInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('project')
            ->firstOrFail();

        if (Auth::check()) {
            return redirect()->route('invitations.complete', $token);
        }

        session(['invitation_token' => $token]);

        return view('invitations.accept', compact('invitation'));
    }

    public function complete(Request $request, string $token, ToolInvitationService $invites): RedirectResponse
    {
        try {
            $member = $invites->accept($token, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors($e->errors());
        }

        session()->forget('invitation_token');

        $member->load('project');

        return redirect()
            ->route('tools.show', $member->project)
            ->with('status', 'You joined '.$member->project->name.' as a field collector.');
    }
}
