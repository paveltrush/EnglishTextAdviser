<?php

namespace App\Http\Controllers;


use App\Logic\Bots\TelegramWrapper;
use App\Logic\Manager;
use App\Logic\Values\UserDto;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update as UpdateObject;

class TelegramController extends Controller
{
    public function setWebhook()
    {
        return Telegram::setWebhook(['url' => config('telegram.bots.mybot.webhook_url')]);
    }
    public function handle()
    {
        try {
            $manager = new Manager();
            $update = Telegram::getWebhookUpdate();
            $message = $update->getMessage();
            $chat = $message->chat;
            $telegramWrapper = new TelegramWrapper($chat->id);

            $input = $this->resolveInput($update);

            $user = new UserDto([
                'userId' => $chat->id,
                'firstName' => $chat->firstName,
                'lastName' => $chat->lastName,
                'username' => $chat->username
            ]);

            Telegram::sendChatAction(['chat_id' => $chat->id, 'action' => 'typing']);

            $telegramWrapper->sendMessage(
                $manager->solveEverything($input, $user)
            );
        }catch (\Throwable $e){
            Bugsnag::notifyException($e);
        }
    }

    protected function resolveInput(UpdateObject $update)
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
