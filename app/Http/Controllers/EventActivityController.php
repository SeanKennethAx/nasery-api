<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventActivity;
use Illuminate\Http\Request;

class EventActivityController extends Controller
{
    public function index(Request $request)
    {
        $activities = EventActivity::query()
            ->whereHas(
                'event',
                fn ($query) =>
                    $query->managedBy($request->user())
            )
            ->with([
                'event:id,name,event_type,event_date,location,status',
            ])
            ->orderByDesc('activity_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $activities,
        ]);
    }
    public function eventActivities(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        $activities = $event
            ->activities()
            ->orderByDesc('activity_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $activities,
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

        $validated =
            $request->validate([
                'title' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],

                'category' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'tag' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'person' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'status' => [
                    'required',
                    'in:pending,in_progress,completed',
                ],

                'activity_at' => [
                    'nullable',
                    'date',
                ],
            ]);

        $activity =
            $event
            ->activities()
            ->create($validated);

        $activity->load('event');

        return response()->json([
            'message' =>
            'Activity created successfully.',

            'data' =>
            $activity,
        ], 201);
    }

    public function update(
        Request $request,
        Event $event,
        EventActivity $activity
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        abort_unless(
            $activity->event_id ===
                $event->id,
            404
        );

        $validated =
            $request->validate([
                'title' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],

                'category' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'tag' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'person' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'status' => [
                    'sometimes',
                    'required',
                    'in:pending,in_progress,completed',
                ],

                'activity_at' => [
                    'nullable',
                    'date',
                ],
            ]);

        $activity->update(
            $validated
        );

        $activity->load(
            'event'
        );

        return response()->json([
            'message' =>
            'Activity updated successfully.',

            'data' =>
            $activity,
        ]);
    }
    public function destroy(
        Request $request,
        Event $event,
        EventActivity $activity
    ) {
        abort_unless(
            $event->isManagedBy($request->user()),
            403
        );

        abort_unless(
            $activity->event_id ===
                $event->id,
            404
        );

        $activity->delete();

        return response()->json([
            'message' =>
            'Activity deleted successfully.',
        ]);
    }
}
