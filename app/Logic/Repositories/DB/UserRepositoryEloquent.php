<?php

namespace App\Logic\Repositories\DB;

use App\Logic\Values\UserDto;
use App\Models\User;

class UserRepositoryEloquent implements UserRepository
{

    public function exists(string $id): bool
    {
        return !User::where('chat_id', $id)->first();
    }

    public function create(UserDto $user)
    {
        User::create([
            'chat_id'    => $user->userId,
            'first_name' => $user->firstName,
            'last_name'  => $user->lastName,
        ]);
    }
}
