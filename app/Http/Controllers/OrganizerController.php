<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganizerController extends Controller
{
    public function profile(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can access this profile.',
            ], 403);
        }

        $organizer = Organizer::query()
            ->where(
                'user_id',
                $user->id
            )
            ->withAvg([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ], 'rating')
            ->withCount([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ])
            ->first();

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' =>
                $organizer->id,

                'user_id' =>
                $organizer->user_id,

                'company_name' =>
                $organizer->company_name,

                'years_experience' =>
                $organizer->years_experience,

                'location' =>
                $organizer->location,

                'google_place_id' =>
                $organizer->google_place_id,

                'latitude' =>
                $organizer->latitude,

                'longitude' =>
                $organizer->longitude,

                'service_radius_km' =>
                $organizer->service_radius_km,

                'bio' =>
                $organizer->bio,

                'tags' =>
                $organizer->tags ?? [],

                'specialties' =>
                $organizer->specialties ?? [],

                'website' =>
                $organizer->website,

                'facebook' =>
                $organizer->facebook,

                'instagram' =>
                $organizer->instagram,

                'banner_color' =>
                $organizer->banner_color,

                'average_rating' =>
                round(
                    (float) (
                        $organizer
                        ->reviews_avg_rating
                        ?? 0
                    ),
                    1
                ),

                'reviews_count' =>
                (int) (
                    $organizer
                    ->reviews_count
                    ?? 0
                ),
            ],
        ]);
    }

    public function updateProfile(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can update this profile.',
            ], 403);
        }

        $organizer =
            $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        $validated =
            $request->validate([
                'company_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'years_experience' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'google_place_id' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                    'required_with:longitude',
                ],

                'longitude' => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                    'required_with:latitude',
                ],

                'service_radius_km' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:200',
                ],

                'bio' => [
                    'nullable',
                    'string',
                ],

                'tags' => [
                    'nullable',
                    'array',
                ],

                'tags.*' => [
                    'string',
                    'in:Wedding,Corporate,Birthday,Debut,Concert,Conference,Reunion,Seminar',
                ],

                'specialties' => [
                    'nullable',
                    'array',
                ],

                'specialties.*' => [
                    'string',
                    'max:255',
                ],

                'website' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'facebook' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'instagram' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'banner_color' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
            ]);

        $organizer->update(
            $validated
        );

        $organizer =
            Organizer::query()
            ->whereKey(
                $organizer->id
            )
            ->withAvg([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ], 'rating')
            ->withCount([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ])
            ->first();

        return response()->json([
            'message' =>
            'Organizer profile updated successfully.',

            'data' =>
            $organizer,
        ]);
    }

    public function nearby(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'client') {
            return response()->json([
                'message' =>
                'Only clients can search for nearby organizers.',
            ], 403);
        }

        $validated =
            $request->validate([
                'lat' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'lng' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],

                'radius' => [
                    'nullable',
                    'numeric',
                    'min:1',
                    'max:200',
                ],
            ]);

        $latitude =
            (float) $validated['lat'];

        $longitude =
            (float) $validated['lng'];

        $radius =
            (float) (
                $validated['radius']
                ?? 25
            );

        $distanceSql = '
            (
                6371 * ACOS(
                    LEAST(
                        1,
                        GREATEST(
                            -1,
                            COS(RADIANS(?))
                            * COS(RADIANS(latitude))
                            * COS(
                                RADIANS(longitude)
                                - RADIANS(?)
                            )
                            + SIN(RADIANS(?))
                            * SIN(RADIANS(latitude))
                        )
                    )
                )
            )
        ';

        $organizers =
            Organizer::query()
            ->select(
                'organizers.*'
            )

            ->selectRaw(
                $distanceSql
                    . ' AS distance_km',
                [
                    $latitude,
                    $longitude,
                    $latitude,
                ]
            )

            ->with([
                'user:id,firstname,middlename,lastname,avatar_path',
            ])

            ->withAvg([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ], 'rating')

            ->withCount([
                'reviews' => function ($query) {
                    $query->where(
                        'is_visible',
                        true
                    );
                },
            ])

            ->whereNotNull(
                'latitude'
            )

            ->whereNotNull(
                'longitude'
            )

            ->whereRaw(
                $distanceSql
                    . ' <= ?',
                [
                    $latitude,
                    $longitude,
                    $latitude,
                    $radius,
                ]
            )

            ->whereRaw(
                $distanceSql
                    . ' <= COALESCE(service_radius_km, ?)',
                [
                    $latitude,
                    $longitude,
                    $latitude,
                    $radius,
                ]
            )

            ->orderBy(
                'distance_km',
                'asc'
            )

            ->orderByRaw(
                'COALESCE(reviews_avg_rating, 0) DESC'
            )

            ->get();

        $organizers->transform(
            function ($organizer) {
                $fullName =
                    trim(
                        collect([
                            $organizer
                                ->user
                                ?->firstname,

                            $organizer
                                ->user
                                ?->middlename,

                            $organizer
                                ->user
                                ?->lastname,
                        ])
                            ->filter()
                            ->implode(' ')
                    );

                $companyName =
                    trim(
                        (string) (
                            $organizer
                            ->company_name
                            ?? ''
                        )
                    );

                $organizer->full_name =
                    $fullName;

                $organizer->display_name =
                    $companyName !== ''
                    ? $companyName
                    : (
                        $fullName !== ''
                        ? $fullName
                        : 'Organizer'
                    );

                $organizer->slug =
                    Str::slug(
                        $organizer->display_name
                    ) . '-' . $organizer->id;

                $organizer->avatar_url =
                    $organizer->user?->avatar_path
                    ? url(Storage::url(
                        $organizer->user->avatar_path
                    ))
                    : null;

                $organizer->distance_km =
                    round(
                        (float) (
                            $organizer
                            ->distance_km
                            ?? 0
                        ),
                        2
                    );

                $organizer
                    ->reviews_avg_rating =
                    round(
                        (float) (
                            $organizer
                            ->reviews_avg_rating
                            ?? 0
                        ),
                        1
                    );

                $organizer
                    ->reviews_count =
                    (int) (
                        $organizer
                        ->reviews_count
                        ?? 0
                    );

                $organizer->latitude =
                    (float) $organizer
                        ->latitude;

                $organizer->longitude =
                    (float) $organizer
                        ->longitude;

                $organizer
                    ->service_radius_km =
                    (int) (
                        $organizer
                        ->service_radius_km
                        ?? 25
                    );

                $organizer->unsetRelation(
                    'user'
                );

                return $organizer;
            }
        );

        return response()->json([
            'message' =>
            'Nearby organizers retrieved successfully.',

            'data' => [
                'center' => [
                    'latitude' =>
                    $latitude,

                    'longitude' =>
                    $longitude,
                ],

                'radius_km' =>
                $radius,

                'organizers' =>
                $organizers,
            ],
        ]);
    }
}
