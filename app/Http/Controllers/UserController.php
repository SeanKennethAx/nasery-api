<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}


    public function login(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'nullable',
                'email',
                'required_without:phone',
            ],

            'phone' => [
                'nullable',
                'string',
                'required_without:email',
                'regex:/^\+639\d{9}$/',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $result = $this->userService->login(
            $validated
        );

        return response()->json(
            $result
        );
    }


    public function register(
        Request $request
    ): JsonResponse {
        $validated = $request->validate(
            [
                'firstname' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'middlename' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'lastname' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],

                'phone' => [
                    'required',
                    'string',
                    'regex:/^\+639\d{9}$/',
                    'unique:users,phone',
                ],

                'address' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                ],

                'role' => [
                    'required',
                    'in:organizer,client',
                ],

                'location' => [
                    'required_if:role,organizer',
                    'nullable',
                    'string',
                    'max:500',
                ],

                'google_place_id' => [
                    'required_if:role,organizer',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'latitude' => [
                    'required_if:role,organizer',
                    'nullable',
                    'numeric',
                    'between:-90,90',
                ],

                'longitude' => [
                    'required_if:role,organizer',
                    'nullable',
                    'numeric',
                    'between:-180,180',
                ],

                'service_radius_km' => [
                    'required_if:role,organizer',
                    'nullable',
                    'integer',
                    'min:1',
                    'max:200',
                ],
            ],
            [
                'email.required' =>
                'The email address field is required.',

                'email.email' =>
                'Please enter a valid email address.',

                'email.unique' =>
                'This email address is already registered.',

                'phone.required' =>
                'The phone number field is required.',

                'phone.regex' =>
                'Please enter a valid Philippine mobile number.',

                'phone.unique' =>
                'This phone number is already registered.',

                'address.required' =>
                'The address field is required.',

                'location.required_if' =>
                'Please select your organizer service location.',

                'google_place_id.required_if' =>
                'Please select a valid location from the suggestions.',

                'latitude.required_if' =>
                'The selected organizer location is missing latitude.',

                'longitude.required_if' =>
                'The selected organizer location is missing longitude.',

                'service_radius_km.required_if' =>
                'Please select your organizer service radius.',

                'latitude.between' =>
                'The selected latitude is invalid.',

                'longitude.between' =>
                'The selected longitude is invalid.',

                'service_radius_km.min' =>
                'The service radius must be at least 1 kilometer.',

                'service_radius_km.max' =>
                'The service radius cannot exceed 200 kilometers.',
            ]
        );

        $user = $this->userService->register(
            $validated
        );

        $user->load([
            'client',
            'organizer',
            'teamMember',
        ]);

        return response()->json([
            'message' =>
            'Account created successfully.',

            'data' => [
                'id' =>
                $user->id,

                'firstname' =>
                $user->firstname,

                'middlename' =>
                $user->middlename,

                'lastname' =>
                $user->lastname,

                'email' =>
                $user->email,

                'phone' =>
                $user->phone,

                'address' =>
                $user->address,

                'avatar_url' =>
                $this->mediaUrl($user->avatar_path),

                'cover_url' =>
                $this->mediaUrl($user->cover_path),

                'role' =>
                $user->role,

                'client_id' =>
                $user->client?->id,

                'organizer_id' =>
                $user->organizer?->id,

                'team_member_id' =>
                $user->teamMember?->id,

                'organizer' =>
                $user->organizer
                    ? [
                        'id' =>
                        $user->organizer->id,

                        'location' =>
                        $user->organizer->location,

                        'google_place_id' =>
                        $user->organizer->google_place_id,

                        'latitude' =>
                        $user->organizer->latitude,

                        'longitude' =>
                        $user->organizer->longitude,

                        'service_radius_km' =>
                        $user->organizer->service_radius_km,
                    ]
                    : null,
            ],
        ], 201);
    }


    public function me(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $user->load([
            'client',
            'organizer',
            'teamMember',
        ]);

        return response()->json([
            'user' => [
                'id' =>
                $user->id,

                'firstname' =>
                $user->firstname,

                'middlename' =>
                $user->middlename,

                'lastname' =>
                $user->lastname,

                'email' =>
                $user->email,

                'phone' =>
                $user->phone,

                'address' =>
                $user->address,

                'avatar_url' =>
                $this->mediaUrl($user->avatar_path),

                'cover_url' =>
                $this->mediaUrl($user->cover_path),

                'role' =>
                $user->role,

                'client_id' =>
                $user->client?->id,

                'organizer_id' =>
                $user->organizer?->id,

                'team_member_id' =>
                $user->teamMember?->id,

                'organizer' =>
                $user->organizer
                    ? [
                        'id' =>
                        $user->organizer->id,

                        'company_name' =>
                        $user->organizer->company_name,

                        'location' =>
                        $user->organizer->location,

                        'google_place_id' =>
                        $user->organizer->google_place_id,

                        'latitude' =>
                        $user->organizer->latitude,

                        'longitude' =>
                        $user->organizer->longitude,

                        'service_radius_km' =>
                        $user->organizer->service_radius_km,
                    ]
                    : null,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+639\d{9}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);
        $user->load(['client', 'organizer', 'teamMember']);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'role' => $user->role,
                'client_id' => $user->client?->id,
                'organizer_id' => $user->organizer?->id,
                'team_member_id' => $user->teamMember?->id,
                'avatar_url' => $this->mediaUrl($user->avatar_path),
                'cover_url' => $this->mediaUrl($user->cover_path),
            ],
        ]);
    }

    public function updateProfileMedia(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:cover'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'required_without:avatar'],
        ]);
        $user = $request->user();

        foreach (['avatar', 'cover'] as $kind) {
            if (! $request->hasFile($kind)) {
                continue;
            }

            $pathField = $kind . '_path';
            if ($user->{$pathField}) {
                Storage::disk('public')->delete($user->{$pathField});
            }
            $user->{$pathField} = $request->file($kind)->store('profile-media/' . $user->id, 'public');
        }

        $user->save();

        return response()->json([
            'message' => 'Profile images updated successfully.',
            'avatar_url' => $this->mediaUrl($user->avatar_path),
            'cover_url' => $this->mediaUrl($user->cover_path),
        ]);
    }

    private function mediaUrl(?string $path): ?string
    {
        return $path ? url(Storage::url($path)) : null;
    }
}
