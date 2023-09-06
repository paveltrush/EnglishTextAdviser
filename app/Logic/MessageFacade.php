<?php

namespace App\Logic;

use App\Logic\Values\Buttons\ShareButton;
use App\Logic\Values\Buttons\SimpleButton;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use App\Logic\Values\Options;

final class MessageFacade
{
    public static function createMenu(string $text): Message
    {
        $buttons = [];

        $buttons[] = new SimpleButton('Check grammatical and lexical errors', Manager::CHECK_ERRORS_COMMAND);
        $buttons[] = new SimpleButton('Improve text to advanced level', Manager::MAKE_ADVANCED_COMMAND);
        $buttons[] = new SimpleButton('Make text formal', Manager::MAKE_FORMAL_COMMAND);
        $buttons[] = new SimpleButton('Make text informal', Manager::MAKE_INFORMAL_COMMAND);

        return new TextWithOptionsMessage([
            'messageText' => $text,
            'options' => new Options(['buttons' => $buttons]),
        ]);
    }

    public static function createAIAnswer(string $text): Message
    {
        $buttons = [];
        $buttons[] = new SimpleButton('Back to menu', Manager::MENU_COMMAND);
        $buttons[] = new ShareButton('Share', $text);

        return new TextWithOptionsMessage([
            'messageText' => $text,
            'options'     => new Options(['buttons' => $buttons]),
        ]);
    }

    public static function createText(string $text): Message
    {
        return new TextMessage($text);
    }
}
