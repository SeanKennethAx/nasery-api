<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicOrganizerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $organizers = $this->publicQuery()
            ->when(
                filled($validated['search'] ?? null),
                function (Builder $query) use ($validated) {
                    $search = '%' . $validated['search'] . '%';

                    $query->where(function (Builder $query) use ($search) {
                        $query
                            ->where('company_name', 'like', $search)
                            ->orWhere('location', 'like', $search)
                            ->orWhere('bio', 'like', $search)
                            ->orWhere('specialties', 'like', $search)
                            ->orWhere('tags', 'like', $search)
                            ->orWhereHas('user', function (Builder $query) use ($search) {
                                $query
                                    ->where('firstname', 'like', $search)
                                    ->orWhere('middlename', 'like', $search)
                                    ->orWhere('lastname', 'like', $search);
                            });
                    });
                }
            )
            ->when(
                filled($validated['specialty'] ?? null),
                fn (Builder $query) => $query->where(
                    'specialties',
                    'like',
                    '%' . $validated['specialty'] . '%'
                )
            )
            ->when(
                filled($validated['location'] ?? null),
                fn (Builder $query) => $query->where(
                    'location',
                    'like',
                    '%' . $validated['location'] . '%'
                )
            )
            ->orderByRaw("CASE WHEN company_name IS NULL OR company_name = '' THEN 1 ELSE 0 END")
            ->orderBy('company_name')
            ->orderBy('id')
            ->get()
            ->map(fn (Organizer $organizer) => $this->summary($organizer));

        return response()->json([
            'data' => $organizers,
        ]);
    }

    public function show(string $organizer): JsonResponse
    {
        $id = $this->idFromPublicIdentifier($organizer);

        if ($id === null) {
            abort(404, 'Organizer not found.');
        }

        $profile = $this->publicQuery()
            ->with([
                'events' => function ($query) {
                    $query
                        ->where('status', 'completed')
                        ->with('eventLocation:id,event_id,venue_name,venue_address')
                        ->latest('event_date')
                        ->latest('id');
                },
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                ...$this->summary($profile),
                'bio' => $profile->bio,
                'tags' => array_values($profile->tags ?? []),
                'years_experience' => $profile->years_experience,
                'website' => $this->publicUrl($profile->website),
                'facebook' => $this->publicUrl($profile->facebook),
                'instagram' => $this->publicUrl($profile->instagram),
                'events' => $profile->events->map(fn ($event) => [
                    'id' => $event->id,
                    'title' => $event->name,
                    'category' => $event->event_type,
                    'date' => $event->event_date?->toDateString(),
                    'year' => $event->event_date?->year,
                    'venue' => $event->eventLocation?->venue_name
                        ?: $event->eventLocation?->venue_address,
                    'description' => $event->description,
                ])->values(),
            ],
        ]);
    }

    private function publicQuery(): Builder
    {
        return Organizer::query()
            ->with('user:id,firstname,middlename,lastname')
            ->withCount([
                'events as portfolio_events_count' => fn ($query) => $query
                    ->where('status', 'completed'),
                'reviews as reviews_count' => fn ($query) => $query
                    ->where('is_visible', true),
            ])
            ->withAvg([
                'reviews as average_rating' => fn ($query) => $query
                    ->where('is_visible', true),
            ], 'rating');
    }

    private function summary(Organizer $organizer): array
    {
        $fullName = collect([
            $organizer->user?->firstname,
            $organizer->user?->middlename,
            $organizer->user?->lastname,
        ])->filter()->implode(' ');

        $name = trim((string) $organizer->company_name) ?: $fullName ?: 'Organizer';

        return [
            'id' => $organizer->id,
            'slug' => Str::slug($name) . '-' . $organizer->id,
            'name' => $name,
            'location' => $organizer->location,
            'specialties' => array_values($organizer->specialties ?? []),
            'bio' => $organizer->bio,
            'years_experience' => $organizer->years_experience,
            'banner_color' => $this->bannerColor($organizer->banner_color),
            'portfolio_events_count' => (int) $organizer->portfolio_events_count,
            'average_rating' => round((float) ($organizer->average_rating ?? 0), 1),
            'reviews_count' => (int) $organizer->reviews_count,
        ];
    }

    private function idFromPublicIdentifier(string $identifier): ?int
    {
        if (ctype_digit($identifier)) {
            return (int) $identifier;
        }

        return preg_match('/-(\d+)$/', $identifier, $matches)
            ? (int) $matches[1]
            : null;
    }

    private function bannerColor(?string $color): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            ? $color
            : '#285f6b';
    }

    private function publicUrl(?string $url): ?string
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $url
            : null;
    }
}
