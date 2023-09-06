<?php

namespace App\Http\Controllers;

use App\Logic\Manager;

interface BotControllerInterface
{
    public function handle(Manager $manager);
}
