<?php

namespace App\Modules\Identity;

use App\Models\User;

class IdentityService
{
    /**
     * Register a new member account.
     */
    public function register(string $name, string $email, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'member',
        ]);
    }

    /**
     * Return the display names of the given users, keyed by user id.
     *
     * @param  array<int, int|string>  $userIds
     * @return array<int, string>
     */
    public function namesFor(array $userIds): array
    {
        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }
}
