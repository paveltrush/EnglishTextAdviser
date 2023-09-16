<?php

namespace App\Http\Controllers;

use App\Logic\Bots\BotmanWrapper;
use App\Logic\Manager;
use App\Logic\Values\UserDto;
use BotMan\BotMan\BotMan;

class BotmanController extends Controller implements BotControllerInterface
{
    /**
     * Endpoint for botman implementation
     */
    public function handle(Manager $manager)
    {
        $botman = app('botman');
        $botmanWrapper = new BotmanWrapper($botman);

        $botman->hears('{message}', function(BotMan $botman, $input) use ($manager, $botmanWrapper){
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
