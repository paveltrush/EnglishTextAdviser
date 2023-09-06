<?php

namespace App\Http\Controllers;


use App\Logic\Bots\TelegramWrapper;
use App\Logic\Manager;
use App\Logic\Values\UserDto;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update as UpdateObject;

class TelegramController extends Controller implements BotControllerInterface
{
    public function setWebhook()
    {
        return Telegram::setWebhook(['url' => config('telegram.bots.mybot.webhook_url')]);
    }
    public function handle(Manager $manager)
    {
        try {
            $update = Telegram::getWebhookUpdate();
            $message = $update->getMessage();
            $chat = $message->chat;
            $telegramWrapper = new TelegramWrapper($chat->id);

            $input = $telegramWrapper->resolveInput($update);

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
}
