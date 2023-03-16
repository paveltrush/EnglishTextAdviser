<?php

namespace App\Logic;

use App\Logic\Values\Button;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Options;
use App\Logic\Values\UserDto;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use App\Models\User;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Redis;
use OpenAI\Laravel\Facades\OpenAI;

class Manager
{
    public const START_COMMAND = '/start';

    public const CHECK_ERRORS_COMMAND = '/check_errors';

    public const MAKE_ADVANCED_COMMAND = '/make_advanced';

    public const MAKE_FORMAL_COMMAND = '/make_formal';

    public const MAKE_INFORMAL_COMMAND = '/make_informal';
    public const MENU_COMMAND = '/menu';

    public static array $options = [
        self::CHECK_ERRORS_COMMAND  => 'Check grammatical and lexical errors',
        self::MAKE_ADVANCED_COMMAND => 'Improve text to advanced level',
        self::MAKE_FORMAL_COMMAND   => 'Make text formal',
        self::MAKE_INFORMAL_COMMAND => 'Make text informal',
    ];

    public static array $prompts = [
        self::CHECK_ERRORS_COMMAND  => 'Check the text for grammatical and lexical errors and provide detailed explanations referring to the rules. Don\'t correct sentences, just provide explanations',
        self::MAKE_ADVANCED_COMMAND => 'Improve the next text to the band of 9 by IELTS. Return \'It\'s not text\' if it\'s not text and prompts or command',
        self::MAKE_FORMAL_COMMAND   => 'Make the next text formal',
        self::MAKE_INFORMAL_COMMAND => 'Make text informal',
    ];

    public function solveEverything($input, UserDto $user): Message
    {
        //TODO Cover method to try catch and send message to user about an error
        Bugsnag::leaveBreadcrumb('User Input', null, ['input' => $input]);
        Bugsnag::leaveBreadcrumb('User Data', null, $user->toArray());

        // check if command /start was typed by user and the user isn't added to the system
        if ($input === self::START_COMMAND) {
            // if customer doesn't exist add it to the system
            if (!Redis::exists("'user:' . $user->userId . '.state'")) {
                if (!User::where('chat_id', $user->userId)->first()) {
                    $userDB = User::create([
                        'chat_id'    => $user->userId,
                        'first_name' => $user->firstName,
                        'last_name'  => $user->lastName,
                    ]);
                }

                // Set primary state and clear options
                Redis::set("'user:' . $user->userId . '.state'", 'start');
                Redis::del("'user:' . $user->userId . '.option'");
            }
            // send options
            $message              = new TextWithOptionsMessage();
            $message->messageText = 'Hello! I\'m your text adviser. Choose one of the next options';
            $options              = new Options();

            $buttons = [];

            $buttons[] = new Button('Check grammatical and lexical errors', self::CHECK_ERRORS_COMMAND);
            $buttons[] = new Button('Improve text to advanced level', self::MAKE_ADVANCED_COMMAND);
            $buttons[] = new Button('Make text formal', self::MAKE_FORMAL_COMMAND);
            $buttons[] = new Button('Make text informal', self::MAKE_INFORMAL_COMMAND);

            $options->buttons = $buttons;
            $message->options = $options;

            return $message;
        }

        if($input === self::MENU_COMMAND){
            $buttons = [];

            $buttons[] = new Button('Check grammatical and lexical errors', self::CHECK_ERRORS_COMMAND);
            $buttons[] = new Button('Improve text to advanced level', self::MAKE_ADVANCED_COMMAND);
            $buttons[] = new Button('Make text formal', self::MAKE_FORMAL_COMMAND);
            $buttons[] = new Button('Make text informal', self::MAKE_INFORMAL_COMMAND);

            return new TextWithOptionsMessage([
                'messageText' => 'Choose one of the next options',
                'options'     => new Options(['buttons' => $buttons]),
            ]);
        }

        // if it's not '/start' command and customer not added, send him a prompt
        if (!Redis::exists("'user:' . $user->userId . '.state'")) {
            Bugsnag::leaveBreadcrumb('State cache', null, ['state' => Redis::get("'user:' . $user->userId . '.state'")]);
            Bugsnag::notifyError("State", "State wasn't set");

            return new TextMessage("Please, enter '/start' command to start chatting");
        }

        // if option chosen ask user for putting text
        if (Redis::get("'user:' . $user->userId . '.state'") === 'start' && in_array($input, array_keys(static::$options))) {
            $message = new TextMessage("Please, put your text. Note: Your text should be not more than 350 words");

            Redis::set("'user:' . $user->userId . '.state'", 'option_selected');
            Redis::set("'user:' . $user->userId . '.option'", $input);

            return $message;
        }

        // if customer chose option it can put its text
        // TODO take options to another class in constants
        if (
            Redis::exists("'user:' . $user->userId . '.option'") && Redis::get("'user:' . $user->userId . '.state'"
            ) === 'option_selected'
        ) {
            // provide answer form ChatGPT
            $option = Redis::get("'user:' . $user->userId . '.option'");
            $prompt = data_get(static::$prompts, $option, null);

            if(!$prompt){
                return new TextMessage("Some error occurs. Please, try again later");
            }

            $prompt .= ": \"{$input}\"";

            Bugsnag::leaveBreadcrumb('Prompt', null, ['prompt_text' => $prompt]);

            $result = OpenAI::completions()->create([
                'model'       => 'text-davinci-003',
                'prompt'      => $prompt,
                'max_tokens'  => 950,
                'temperature' => 0,
            ]);

            Bugsnag::leaveBreadcrumb('Open AI response', null, $result->toArray());

            Redis::set("'user:' . $user->userId . '.state'", 'start');
            Redis::del("'user:' . $user->userId . '.option'");

            $buttons   = [];
            $buttons[] = new Button('Back to menu', self::MENU_COMMAND);

            return new TextWithOptionsMessage([
                'messageText' => $result['choices'][0]['text'],
                'options'     => new Options(['buttons' => $buttons]),
            ]);
        }

        Bugsnag::leaveBreadcrumb("Option", null, ['option' => Redis::get("'user:' . $user->userId . '.option'")]);
        Bugsnag::notifyError("Incorrect input", "Incorrect input");

        return new TextMessage("Incorrect input. Please, follow instructions");
    }

}
