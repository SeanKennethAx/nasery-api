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
            $event->organizer_id === $request->user()->id,
            403
        );

        return response()->json([
            'data' => $event
                ->preparationItems()
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        Event $event
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
            403
        );

        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $item = $event
            ->preparationItems()
            ->create([
                'label' =>
                $validated['label'],

                'is_completed' =>
                false,
            ]);

        return response()->json([
            'message' =>
            'Checklist item added successfully.',

            'data' =>
            $item,
        ], 201);
    }

    public function update(
        Request $request,
        Event $event,
        EventPreparationItem $item
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
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
        ]);

        $item->update($validated);

        return response()->json([
            'message' =>
            'Checklist item updated successfully.',

            'data' =>
            $item,
        ]);
    }

    public function destroy(
        Request $request,
        Event $event,
        EventPreparationItem $item
    ) {
        abort_unless(
            $event->organizer_id === $request->user()->id,
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
