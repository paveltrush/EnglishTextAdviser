<?php

namespace App\Logic\Bots;

use App\Logic\Values\Buttons\SimpleButton;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class BotmanWrapper implements BotInterface
{
    protected BotMan $botman;

    public function __construct(BotMan $botMan)
    {
        $this->botman = $botMan;
    }

    public function sendMessage(Message $message)
    {
        if($message instanceof TextWithOptionsMessage){
            $question = Question::create($message->messageText);
            $options = $message->options;

            foreach ($options->buttons as $button){
                if($button instanceof SimpleButton){
                    $button = Button::create($button->text)->value($button->value);
                    $question->addButton($button);
                }
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
