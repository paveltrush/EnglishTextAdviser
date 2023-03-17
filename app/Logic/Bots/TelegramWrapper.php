<?php

namespace App\Logic\Bots;

use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWrapper implements BotInterface
{
    protected int $chatId;

    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    public function sendMessage(Message $message)
    {
        Bugsnag::leaveBreadcrumb("Telegram Message", null, $message->toArray());

        if($message instanceof TextMessage){
            Telegram::sendMessage([
                'chat_id' => $this->chatId,
                'text' => $message->text
            ]);

            return;
        }

        if($message instanceof TextWithOptionsMessage){
            $keyboard = [];

            foreach ($message->options->buttons as $option){
                $keyboard[] = [['text' => $option->text, 'callback_data' => $option->value]];
            }

            Bugsnag::leaveBreadcrumb("Options", null, $keyboard);

            $replyMarkup = Keyboard::make([
                'inline_keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]);

            Telegram::sendMessage([
                'chat_id'      => $this->chatId,
                'text'         => $message->messageText,
                'reply_markup' => $replyMarkup,
            ]);

            return;
        }

        throw new \LogicException("Incorrect message type");
    }

}
