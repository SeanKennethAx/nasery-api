<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    public function profile(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can access this profile.',
            ], 403);
        }

        $organizer = $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $organizer->id,
                'user_id' => $organizer->user_id,

                'company_name' =>
                $organizer->company_name,

                'years_experience' =>
                $organizer->years_experience,

                'location' =>
                $organizer->location,

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
            ],
        ]);
    }


    public function updateProfile(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if ($user->role !== 'organizer') {
            return response()->json([
                'message' =>
                'Only organizers can update this profile.',
            ], 403);
        }

        $organizer = $user->organizer;

        if (!$organizer) {
            return response()->json([
                'message' =>
                'Organizer profile not found.',
            ], 404);
        }

        $validated = $request->validate([
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
                'max:255',
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

        return response()->json([
            'message' =>
            'Organizer profile updated successfully.',

            'data' =>
            $organizer->fresh(),
        ]);
    }
}
