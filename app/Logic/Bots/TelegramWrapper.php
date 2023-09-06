<?php

namespace App\Logic\Bots;

use App\Logic\Values\Buttons\ShareButton;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update as UpdateObject;

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
                $button = [['text' => $option->text, 'callback_data' => $option->value]];

                if($option instanceof ShareButton){
                    $button = [['text' => $option->text, 'switch_inline_query' => $option->value]];
                }

                $keyboard[] = $button;
            }

            Bugsnag::leaveBreadcrumb("Telegram Options", null, $keyboard);

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

    public function resolveInput(UpdateObject $update)
    {
        switch ($update->detectType()) {
            case 'message':
                return $update->message->text;
            case 'callback_query':
                $callbackQuery = $update->callbackQuery;
                if ($callbackQuery->has('data')) {
                    return $callbackQuery->data;
                }
                break;
        }

        throw new \LogicException($update->detectType()." isn't processed in code");
    }
}
