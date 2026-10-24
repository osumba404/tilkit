<?php

namespace App\Http\Controllers;

use App\Models\DcProject;
use App\Support\Tilkit\WeatherTool;
use App\Models\ToolInvitation;
use App\Enums\ToolMemberRole;
use App\Services\ToolAccessService;
use App\Services\ToolAuditService;
use App\Services\ToolInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ToolInvitationController extends Controller
{
    public function store(Request $request, DcProject $tool, ToolAccessService $access, ToolInvitationService $invites, ToolAuditService $audit): RedirectResponse
    {
        $access->assertCanManage($request->user(), $tool);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', 'in:viewer,collector,admin'],
            'can_export' => ['sometimes', 'boolean'],
            'can_publish_forms' => ['sometimes', 'boolean'],
        ]);

        $role = ToolMemberRole::from($validated['role']);

        $invites->invite(
            $tool,
            $request->user(),
            $validated['email'],
            $role,
            $request->boolean('can_export') ? true : null,
            $request->boolean('can_publish_forms') ? true : null,
        );

        $audit->log($tool, $request->user(), 'invitation.sent', null, null, [
            'email' => $validated['email'],
            'role' => $role->value,
        ]);

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

        $project = $member->project;
        $route = WeatherTool::isWeatherModule($project)
            ? route('tools.tilkit.overview', $project)
            : route('tools.show', $project);

        return redirect($route)
            ->with('status', 'You joined '.$project->name.'.');
    }
}
