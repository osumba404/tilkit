<?php

namespace App\Services;

use App\Enums\ToolMemberRole;
use App\Mail\ToolCollectorInvitationMail;
use App\Models\DcProject;
use App\Models\ToolInvitation;
use App\Models\ToolMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ToolInvitationService
{
    public function invite(
        DcProject $tool,
        User $inviter,
        string $email,
        ToolMemberRole $role = ToolMemberRole::Collector,
        ?bool $canExport = null,
        ?bool $canPublishForms = null,
    ): ToolInvitation|ToolMember
    {
        $email = Str::lower(trim($email));

        if ($inviter->email === $email) {
            throw ValidationException::withMessages(['email' => 'You are already the tool owner.']);
        }

        $existingUser = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($existingUser) {
            $membership = ToolMember::query()
                ->where('project_id', $tool->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($membership) {
                throw ValidationException::withMessages(['email' => 'This person already has access to the tool.']);
            }

            return DB::transaction(function () use ($tool, $existingUser, $role, $inviter, $canExport, $canPublishForms): ToolMember {
                $member = ToolMember::create([
                    'project_id' => $tool->id,
                    'user_id' => $existingUser->id,
                    'role' => $role,
                    'can_export' => $canExport,
                    'can_publish_forms' => $canPublishForms,
                ]);

                ToolInvitation::query()
                    ->where('project_id', $tool->id)
                    ->where('email', Str::lower($existingUser->email))
                    ->whereNull('accepted_at')
                    ->update(['accepted_at' => now()]);

                $placeholder = new ToolInvitation([
                    'token' => (string) Str::uuid(),
                    'email' => $existingUser->email,
                ]);
                Mail::to($existingUser->email)->send(
                    new ToolCollectorInvitationMail(
                        $placeholder,
                        $tool,
                        $inviter,
                        route('tools.show', $tool),
                    )
                );

                return $member;
            });
        }

        $invitation = ToolInvitation::query()->updateOrCreate(
            ['project_id' => $tool->id, 'email' => $email],
            [
                'role' => $role,
                'can_export' => $canExport,
                'can_publish_forms' => $canPublishForms,
                'invited_by' => $inviter->id,
                'token' => (string) Str::uuid(),
                'accepted_at' => null,
                'expires_at' => now()->addDays(14),
                'created_at' => now(),
            ],
        );

        Mail::to($email)->send(new ToolCollectorInvitationMail($invitation, $tool, $inviter));

        return $invitation;
    }

    public function accept(string $token, User $user): ToolMember
    {
        $invitation = ToolInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was sent to '.$invitation->email.'. Sign in with that account or register using that address.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): ToolMember {
            $member = ToolMember::firstOrCreate(
                ['project_id' => $invitation->project_id, 'user_id' => $user->id],
                [
                    'role' => $invitation->role,
                    'can_export' => $invitation->can_export,
                    'can_publish_forms' => $invitation->can_publish_forms,
                ],
            );

            if ($member->wasRecentlyCreated === false) {
                $member->update([
                    'role' => $invitation->role,
                    'can_export' => $invitation->can_export,
                    'can_publish_forms' => $invitation->can_publish_forms,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);

            return $member;
        });
    }
}
