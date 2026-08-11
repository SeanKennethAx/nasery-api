<?php

namespace App\Services;

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

    /**
     * Register a new user using either email or phone.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = $this->userRepository->createUser([
                'firstname' => $data['firstname'],
                'middlename' => $data['middlename'] ?? null,
                'lastname' => $data['lastname'],

                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,

                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            if ($data['role'] === 'organizer') {
                $this->userRepository->createOrganizer(
                    $user->id
                );
            }

            if ($data['role'] === 'client') {
                $this->userRepository->createClient(
                    $user->id
                );
            }

            return $user;
        });
    }

    public function login(array $data): array
    {
        $credentialField = null;
        $user = null;

        if (! empty($data['email'])) {
            $credentialField = 'email';

            $user = $this->userRepository->findByEmail(
                $data['email']
            );
        }

        if (! empty($data['phone'])) {
            $credentialField = 'phone';

            $user = $this->userRepository->findByPhone(
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

        $token = $user
            ->createToken('auth-token')
            ->plainTextToken;

        return [
            'message' => 'Login successful.',
            'token' => $token,

            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ];
    }
}
