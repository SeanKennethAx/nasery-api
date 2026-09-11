<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPreparationItem;
use Illuminate\Http\Request;

class EventPreparationController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        return response()->json([
            'data' => $event
                ->preparationItems()
                ->with(['assignedTeamMember.user:id,firstname,lastname,email', 'completedBy:id,firstname,lastname'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:255',
            ],
            'assigned_team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
        ]);

        if (!empty($validated['assigned_team_member_id'])) {
            $belongsToOrganizer = $request->user()->organizer
                ?->teamMembers()->whereKey($validated['assigned_team_member_id'])->exists();
            abort_unless($belongsToOrganizer, 422, 'Select a member of your organizer team.');
        }

        $item = $event
            ->preparationItems()
            ->create([
                'label' =>
                $validated['label'],

                'is_completed' =>
                false,
                'assigned_team_member_id' => $validated['assigned_team_member_id'] ?? null,
            ]);

        return response()->json([
            'message' =>
            'Checklist item added successfully.',

            'data' =>
            $item->load('assignedTeamMember.user'),
        ], 201);
    }

    public function update(
        Request $request,
        Event $event,
        EventPreparationItem $item
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        abort_unless(
            $item->event_id === $event->id,
            404
        );

        $validated = $request->validate([
            'label' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'is_completed' => [
                'sometimes',
                'boolean',
            ],
            'assigned_team_member_id' => ['sometimes', 'nullable', 'integer', 'exists:team_members,id'],
            'review_status' => ['sometimes', 'in:pending,verified,changes_requested'],
        ]);

        if (array_key_exists('assigned_team_member_id', $validated) && $validated['assigned_team_member_id']) {
            abort_unless(
                $request->user()->organizer?->teamMembers()->whereKey($validated['assigned_team_member_id'])->exists(),
                422,
                'Select a member of your organizer team.'
            );
        }

        if (array_key_exists('review_status', $validated)) {
            abort_unless($item->is_completed, 422, 'Only completed work can be reviewed.');
            $validated['reviewed_at'] = now();
            $validated['is_completed'] = $validated['review_status'] === 'verified';
        }

        if (
            array_key_exists('is_completed', $validated) &&
            !$validated['is_completed'] &&
            !$request->has('review_status')
        ) {
            $validated['completed_at'] = null;
            $validated['completed_by_user_id'] = null;
            $validated['completion_note'] = null;
            $validated['review_status'] = 'pending';
            $validated['reviewed_at'] = null;
        }

        $item->update($validated);

        return response()->json([
            'message' =>
            'Checklist item updated successfully.',

            'data' =>
            $item->load(['assignedTeamMember.user', 'completedBy']),
        ]);
    }

    public function destroy(
        Request $request,
        Event $event,
        EventPreparationItem $item
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        abort_unless(
            $item->event_id === $event->id,
            404
        );

        $item->delete();

        return response()->json([
            'message' =>
            'Checklist item deleted successfully.',
        ]);
    }
}
