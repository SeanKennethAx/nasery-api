<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicEventMarketplaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $events = Event::query()
            ->where(function (Builder $query) {
                $query
                    ->whereIn('status', ['published', 'ongoing', 'completed'])
                    ->orWhereHas(
                        'registrationSettings',
                        fn (Builder $settings) => $settings->where('public_registration', true)
                    );
            })
            ->with([
                'organizer.user:id,firstname,middlename,lastname',
                'eventLocation:id,event_id,venue_name,venue_address',
                'registrationSettings:id,event_id,public_registration,public_registration_token',
                'ticketTypes:id,event_id,name,price,capacity',
            ])
            ->when(filled($validated['search'] ?? null), function (Builder $query) use ($validated) {
                $search = '%' . $validated['search'] . '%';

                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('event_type', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('organizer', fn (Builder $organizer) => $organizer
                            ->where('company_name', 'like', $search)
                            ->orWhereHas('user', fn (Builder $user) => $user
                                ->where('firstname', 'like', $search)
                                ->orWhere('lastname', 'like', $search)))
                        ->orWhereHas('eventLocation', fn (Builder $location) => $location
                            ->where('venue_name', 'like', $search)
                            ->orWhere('venue_address', 'like', $search));
                });
            })
            ->when(
                filled($validated['type'] ?? null),
                fn (Builder $query) => $query->where('event_type', $validated['type'])
            )
            ->when(
                filled($validated['location'] ?? null),
                fn (Builder $query) => $query->whereHas('eventLocation', fn (Builder $location) => $location
                    ->where('venue_name', 'like', '%' . $validated['location'] . '%')
                    ->orWhere('venue_address', 'like', '%' . $validated['location'] . '%'))
            )
            ->orderByRaw('CASE WHEN event_date >= ? THEN 0 ELSE 1 END', [now()->toDateString()])
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Event $event) => $this->summary($event));

        return response()->json(['data' => $events]);
    }

    private function summary(Event $event): array
    {
        $organizer = $event->organizer;
        $personName = collect([
            $organizer?->user?->firstname,
            $organizer?->user?->middlename,
            $organizer?->user?->lastname,
        ])->filter()->implode(' ');
        $organizerName = trim((string) $organizer?->company_name) ?: $personName ?: 'Organizer';
        $settings = $event->registrationSettings;

        return [
            'id' => $event->id,
            'name' => $event->name,
            'event_type' => $event->event_type,
            'description' => $event->description,
            'event_date' => $event->event_date?->toDateString(),
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'status' => $event->status,
            'location' => $event->eventLocation?->venue_name
                ?: $event->eventLocation?->venue_address,
            'organizer' => [
                'id' => $organizer?->id,
                'slug' => Str::slug($organizerName) . '-' . $organizer?->id,
                'name' => $organizerName,
                'banner_color' => $this->bannerColor($organizer?->banner_color),
            ],
            'ticket_types' => $event->ticketTypes->map(fn ($ticket) => [
                'id' => $ticket->id,
                'name' => $ticket->name,
                'price' => (float) $ticket->price,
                'capacity' => $ticket->capacity,
            ])->values(),
            'registration_open' => (bool) $settings?->public_registration,
            'registration_path' => $settings?->public_registration && $settings?->public_registration_token
                ? '/event-registration/' . $settings->public_registration_token
                : null,
        ];
    }

    private function bannerColor(?string $color): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            ? $color
            : '#285f6b';
    }
}
