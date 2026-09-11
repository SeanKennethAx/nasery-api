<?php

namespace App\Repositories;

use App\Models\ClientProfile;
use App\Models\Organizer;
use App\Models\User;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }

    public function createOrganizer(
        int $userId,
        array $data = []
    ): Organizer {
        return Organizer::create([
            'user_id' =>
            $userId,

            'location' =>
            $data['location'] ?? null,

            'google_place_id' =>
            $data['google_place_id'] ?? null,

            'latitude' =>
            $data['latitude'] ?? null,

            'longitude' =>
            $data['longitude'] ?? null,

            'service_radius_km' =>
            $data['service_radius_km'] ?? 25,
        ]);
    }

    public function createClient(
        int $userId
    ): ClientProfile {
        return ClientProfile::create([
            'user_id' => $userId,
        ]);
    }
}
