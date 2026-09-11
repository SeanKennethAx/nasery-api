<?php

namespace App\Http\Controllers;

use App\Models\EventPreparationItem;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeamMemberController extends Controller
{
    public function organizerIndex(Request $request): JsonResponse
    {
        $organizer = $request->user()->organizer;
        abort_unless($organizer, 404, 'Organizer profile not found.');

        return response()->json(['data' => $organizer->teamMembers()->with('user:id,firstname,middlename,lastname,email,phone')->orderBy('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $organizer = $request->user()->organizer;
        abort_unless($organizer, 404, 'Organizer profile not found.');

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        $member = DB::transaction(function () use ($data, $organizer) {
            $user = User::create([
                'firstname' => $data['firstname'], 'lastname' => $data['lastname'],
                'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']), 'role' => 'team_member',
            ]);
            return TeamMember::create([
                'organizer_id' => $organizer->id, 'user_id' => $user->id,
                'position' => $data['position'] ?? null,
            ])->load('user');
        });

        return response()->json(['message' => 'Team member account created.', 'data' => $member], 201);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $member = $request->user()->teamMember;
        abort_unless($member?->is_active, 403, 'Your team member account is inactive.');

        $tasks = EventPreparationItem::query()
            ->where('assigned_team_member_id', $member->id)
            ->with(['event:id,name,event_type,event_date,status', 'completedBy:id,firstname,lastname'])
            ->orderBy('is_completed')->orderBy('id')->get();

        return response()->json(['data' => ['member' => $member->load('user'), 'tasks' => $tasks]]);
    }

    public function complete(Request $request, EventPreparationItem $item): JsonResponse
    {
        $member = $request->user()->teamMember;
        abort_unless($member && $item->assigned_team_member_id === $member->id, 403);
        $data = $request->validate(['completion_note' => ['nullable', 'string', 'max:1000']]);

        $item->update([
            'is_completed' => true, 'completion_note' => $data['completion_note'] ?? null,
            'completed_at' => now(), 'completed_by_user_id' => $request->user()->id,
            'review_status' => 'pending', 'reviewed_at' => null,
        ]);

        return response()->json(['message' => 'Task marked complete and sent for organizer review.', 'data' => $item->load(['event', 'completedBy'])]);
    }
}
