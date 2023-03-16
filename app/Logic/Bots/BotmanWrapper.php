<?php

namespace App\Logic\Bots;

use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use App\Logic\Values\Messages\TextMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class BotmanWrapper implements BotInterface
{
    protected \BotMan\BotMan\BotMan $botman;

    public function __construct(\BotMan\BotMan\BotMan $botMan)
    {
        $this->botman = $botMan;
    }

    public function sendMessage(Message $message)
    {
        if($message instanceof TextWithOptionsMessage){
            $question = Question::create($message->messageText);
            $options = $message->options;

            foreach ($options->buttons as $button){
                $button = Button::create($button->text)->value($button->value);
                $question->addButton($button);
            }

            $this->botman->reply($question);

            return;
        }

        if ($message instanceof TextMessage){
            $this->botman->reply($message->text);

            return;
        }

        throw new \LogicException("Incorrect message type");
    }

}
