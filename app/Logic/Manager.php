<?php

namespace App\Logic;

use App\Logic\Bots\BotInterface;
use App\Logic\Values\Button;
use App\Logic\Values\Message;
use App\Logic\Values\Messages\TextMessage;
use App\Logic\Values\Options;
use App\Logic\Values\UserDto;
use App\Logic\Values\Messages\TextWithOptionsMessage;
use App\Models\User;
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
        self::MAKE_ADVANCED_COMMAND => 'Improve this text to the band of 9 by IELTS',
        self::MAKE_FORMAL_COMMAND   => 'Make text formal',
        self::MAKE_INFORMAL_COMMAND => 'Make text informal',
    ];

    public function solveEverything($input, UserDto $user): Message
    {
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

                Redis::set("'user:' . $user->userId . '.state'", 'start');
            }
            // send options
            $message              = new TextWithOptionsMessage();
            $message->messageText = 'Hello! I\'m your text adviser. Choose one of the next options';
            $options              = new Options();

            $buttons = [];

            $buttons[] = new Button('Check grammatical and lexical errors', '/check_errors');
            $buttons[] = new Button('Improve text to advanced level', '/make_advanced');
            $buttons[] = new Button('Make text formal', '/make_formal');
            $buttons[] = new Button('Make text informal', '/make_informal');

            $options->buttons = $buttons;
            $message->options = $options;

            return $message;
        }

        if($input === self::MENU_COMMAND){
            $buttons = [];

            $buttons[] = new Button('Check grammatical and lexical errors', '/check_errors');
            $buttons[] = new Button('Improve text to advanced level', '/make_advanced');
            $buttons[] = new Button('Make text formal', '/make_formal');
            $buttons[] = new Button('Make text informal', '/make_informal');

            return new TextWithOptionsMessage([
                'messageText' => 'Choose one of the next options',
                'options'     => new Options(['buttons' => $buttons]),
            ]);
        }

        // if it's not '/start' command and customer not added, send him a prompt
        if (!Redis::exists("'user:' . $user->userId . '.state'")) {
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
                report(new \Exception("Incorrect option: $option"));
                
                return new TextMessage("Some error occurs. Please, try again later");
            }

            try {
                $prompt .= ':/n/n '.$input;
                $result = OpenAI::completions()->create([
                    'model'       => 'text-davinci-003',
                    'prompt'      => $prompt,
                    'max_tokens'  => 600,
                    'temperature' => 0,
                ]);
            }catch(\Throwable $e){
                report($e);

                return new TextMessage("Some error occurs. Please, try again later");
            }

            Redis::del("'user:' . $user->userId . '.option'");
            Redis::set("'user:' . $user->userId . '.state'", 'start');

            $buttons   = [];
            $buttons[] = new Button('Back to menu', self::MENU_COMMAND);

            return new TextWithOptionsMessage([
                'messageText' => $result->choices[0]->text,
                'options'     => new Options(['buttons' => $buttons]),
            ]);
        }

        return new TextMessage("Incorrect input. Please, follow instructions");
    }

}
