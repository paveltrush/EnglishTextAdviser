<?php

namespace App\Http\Controllers;

use BotMan\BotMan\Messages\Incoming\Answer;

class BotmanController extends Controller
{
    /**
     * Place your BotMan converison.
     */
    public function enterRequest()
    {
        $botman = app('botman');

        $botman->hears('{message}', function($botman, $message) {
            if ($message == 'Hi! i need your help') {
                $this->askReply($botman);
            } else {
                $botman->reply("Hello! how can i Help you...?");
            }
        });

        $botman->listen();
    }

    /**
     * Place your BotMan converison.
     */
    public function askReply($botman)
    {
        $botman->ask('Hello! What is your Name?', function(Answer $answer) {
            $name = $answer->getText();

            $this->say('Nice to meet you '.$name);
        });
    }
}
