<?php

namespace App\Services;

use App\Http\Controllers\AccountEmailVerificationController;
use App\Models\EmailVerification;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}


    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $verification = EmailVerification::query()
                ->where('email', strtolower(trim($data['email'])))
                ->where('event_token', AccountEmailVerificationController::SCOPE)
                ->where('verification_token', $data['email_verification_token'])
                ->whereNotNull('verified_at')
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (! $verification || $verification->verified_at->lt(now()->subMinutes(30))) {
                throw ValidationException::withMessages([
                    'email' => ['Verify your email address before creating the account.'],
                ]);
            }

            $user = $this->userRepository->createUser([
                'firstname' =>
                $data['firstname'],

                'middlename' =>
                $data['middlename'] ?? null,

                'lastname' =>
                $data['lastname'],

                'email' =>
                $data['email'],

                'phone' =>
                $data['phone'],

                'address' =>
                $data['address'],

                'password' =>
                $data['password'],

                'role' =>
                $data['role'],
            ]);


            if ($data['role'] === 'organizer') {
                $this->userRepository->createOrganizer(
                    $user->id,
                    [
                        'location' =>
                        $data['location']
                            ?? $data['address'],

                        'google_place_id' =>
                        $data['google_place_id']
                            ?? null,

                        'latitude' =>
                        isset($data['latitude'])
                            ? (float) $data['latitude']
                            : null,

                        'longitude' =>
                        isset($data['longitude'])
                            ? (float) $data['longitude']
                            : null,

                        'service_radius_km' =>
                        isset($data['service_radius_km'])
                            ? (int) $data['service_radius_km']
                            : 25,
                    ]
                );
            }


            if ($data['role'] === 'client') {
                $this->userRepository->createClient(
                    $user->id
                );
            }

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            $verification->markAsUsed();


            $user->load([
                'organizer',
                'client',
            ]);

            return $user;
        });
    }


    public function login(array $data): array
    {
        $credentialField = null;
        $user = null;


        if (! empty($data['email'])) {
            $credentialField = 'email';

            $user =
                $this->userRepository
                ->findByEmail(
                    $data['email']
                );
        }


        if (! empty($data['phone'])) {
            $credentialField = 'phone';

            $user =
                $this->userRepository
                ->findByPhone(
                    $data['phone']
                );
        }


        if (
            ! $user ||
            ! Hash::check(
                $data['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                $credentialField ?? 'login' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }


        $user->load([
            'client',
            'organizer',
            'teamMember',
        ]);


        $token = $user
            ->createToken('auth-token')
            ->plainTextToken;


        return [
            'message' =>
            'Login successful.',

            'token' =>
            $token,

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
                $user->avatar_path ? url(\Illuminate\Support\Facades\Storage::url($user->avatar_path)) : null,

                'cover_url' =>
                $user->cover_path ? url(\Illuminate\Support\Facades\Storage::url($user->cover_path)) : null,

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
                        $user->organizer->latitude !== null
                            ? (float) $user->organizer->latitude
                            : null,

                        'longitude' =>
                        $user->organizer->longitude !== null
                            ? (float) $user->organizer->longitude
                            : null,

                        'service_radius_km' =>
                        $user->organizer->service_radius_km !== null
                            ? (int) $user->organizer->service_radius_km
                            : 25,
                    ]
                    : null,
            ],
        ];
    }
}
