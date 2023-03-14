<?php

namespace App\Http\Controllers;

use App\Logic\Bots\Botman;
use App\Logic\Manager;
use App\Logic\Values\UserDto;

class BotmanController extends Controller
{
    /**
     * Place your BotMan converison.
     */
    public function enterRequest()
    {
        $botman = app('botman');
        $botmanWrapper = new Botman($botman);

        $manager = new Manager();

        $botman->hears('{message}', function(\BotMan\BotMan\BotMan $botman, $input) use ($manager, $botmanWrapper){
            $botman->typesAndWaits(2);

            $userBotman = $botman->getUser();

            $user = new UserDto([
                'userId' => $userBotman->getId(),
                'firstName' => $userBotman->getFirstName(),
                'lastName' => $userBotman->getLastName(),
                'username' => $userBotman->getUsername()
            ]);

            $botmanWrapper->sendMessage(
                $manager->solveEverything($input, $user)
            );
        });

        $botman->listen();
    }
}
