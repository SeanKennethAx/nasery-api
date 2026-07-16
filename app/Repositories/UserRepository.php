<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findByEmail(string $email)
    {
        $user = User::where('email', $email)->first();
        return $user;
    }
}
