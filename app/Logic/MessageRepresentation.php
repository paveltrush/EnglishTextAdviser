<?php

namespace App\Logic;

use App\Logic\Values\Buttons\ShareButton;
use App\Logic\Values\Buttons\SimpleButton;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use App\Logic\Values\Options;

final class MessageRepresentation
{
    public const ASK_FOR_START_COMMAND = 'Please, enter '.Manager::START_COMMAND.' command to start chatting';
    public const INTRODUCTION_MESSAGE = 'Hello! I\'m your text adviser. Choose one of the next options';
    public const MENU_TEXT = 'Choose one of the next options';
    public const ASK_FOR_TEXT = 'Please, put your text. Note: Your text should be not more than '.Manager::MAX_WORDS.' words';
    public const INCORRECT_INPUT = 'Incorrect input. Please, follow instructions';
    public const ERROR_OCCURS = 'Some error occurs. Please, try again later';
    public const WORDS_AMOUNT_EXCEEDED = 'Your text consists more than '.Manager::MAX_WORDS.' words';

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
