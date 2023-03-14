<?php

namespace App\Logic\Bots;

use App\Logic\Values\Message;

interface BotInterface
{
    public function sendMessage(Message $message);
}
