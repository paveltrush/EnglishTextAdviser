<?php

namespace App\Logic;

use App\Logic\Exceptions\WordsAmountExceededException;
use App\Logic\Integrations\GeneretingModel;
use App\Logic\Repositories\Cache\CacheRepository;
use App\Logic\Repositories\DB\UserRepository;
use App\Logic\Values\Message;
use App\Logic\Values\UserDto;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Str;

/**
 * Manager is responsible for resolving input request from users and returning messages
 */
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

    public const START_STATE = 'start';
    public const OPTION_SELECTED_STATE = 'option_selected';

    public const MAX_WORDS = 500;
    protected GeneretingModel $model;
    protected UserRepository $userRepository;
    private CacheRepository $cacheRepository;

    public function __construct(GeneretingModel $model, UserRepository $userRepository, CacheRepository $cacheRepository)
    {
        $this->model = $model;

        $this->userRepository = $userRepository;

        $this->cacheRepository = $cacheRepository;
    }

    public function solveEverything(string $input, UserDto $user): Message
    {
        Bugsnag::leaveBreadcrumb('User Input', null, ['input' => $input]);
        Bugsnag::leaveBreadcrumb('User Data', null, $user->toArray());

        try {
            // check if command /start was typed by user and the user isn't added to the system
            if ($input === self::START_COMMAND) {
                // if customer doesn't exist add it to the system
                if (!$this->userRepository->exists($user->userId)) {
                    $this->userRepository->create($user);
                }

                if(!$this->cacheRepository->checkUserExists($user->userId)){
                    // Set primary state and clear options
                    $this->cacheRepository->setState($user->userId, self::START_STATE);
                    $this->cacheRepository->removeOptions($user->userId);
                }

                // send options
                return MessageRepresentation::createMenu(MessageRepresentation::INTRODUCTION_MESSAGE);
            }

            // if it's not '/start' command and customer not added, send him a prompt
            if (!$this->cacheRepository->checkUserExists($user->userId)) {
                Bugsnag::notifyError("State", "State wasn't set");

                return MessageRepresentation::createText(MessageRepresentation::ASK_FOR_START_COMMAND);
            }

            if ($input === self::MENU_COMMAND) {
                return MessageRepresentation::createMenu(MessageRepresentation::MENU_TEXT);
            }

            // if option chosen ask user for putting text
            if (in_array($input, array_keys(static::$options))) {
                $this->cacheRepository->setState($user->userId, self::OPTION_SELECTED_STATE);
                $this->cacheRepository->setOption($user->userId, $input);

                return MessageRepresentation::createText(MessageRepresentation::ASK_FOR_TEXT);
            }

            // if customer chose option it can put its text
            if (($option = $this->cacheRepository->getCurrentOption($user->userId)) &&
                $this->cacheRepository->getCurrentState($user->userId) === self::OPTION_SELECTED_STATE) {
                // getting answer from ChatGPT
                $prompt = data_get(static::$prompts, $option);

                if(Str::of($input)->wordCount() > self::MAX_WORDS){
                    throw new WordsAmountExceededException();
                }

                $prompt .= ": \"{$input}\"";

                $answer = $this->model->handlePrompt($prompt);

                $this->cacheRepository->setState($user->userId, self::START_STATE);
                $this->cacheRepository->removeOptions($user->userId);

                return MessageRepresentation::createAIAnswer($answer);
            }

            Bugsnag::notifyError("Incorrect input", "Incorrect input");

            return MessageRepresentation::createText(MessageRepresentation::INCORRECT_INPUT);
        }catch (WordsAmountExceededException $e){
            Bugsnag::notifyException($e);

            return MessageRepresentation::createText(MessageRepresentation::WORDS_AMOUNT_EXCEEDED);
        }catch (\Throwable $e) {
            Bugsnag::notifyException($e);

            return MessageRepresentation::createText(MessageRepresentation::ERROR_OCCURS);
        } finally {
            Bugsnag::leaveBreadcrumb("State", null, ['state' => $this->cacheRepository->getCurrentState($user->userId)]);
            Bugsnag::leaveBreadcrumb("Option", null, ['option' => $this->cacheRepository->getCurrentOption($user->userId)]);
        }
    }

}
