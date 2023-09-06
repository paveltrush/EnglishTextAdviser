<?php

namespace App\Logic\Repositories\DB;

use App\Logic\Values\UserDto;

interface UserRepository
{
    public function exists(string $id): bool;

    public function create(UserDto $user);
}
