<?php

namespace App\Logic;

use App\Logic\Exceptions\NotFoundOptionException;
use App\Logic\Integrations\GeneretingModel;
use App\Logic\Repositories\Cache\CacheRepository;
use App\Logic\Repositories\DB\UserRepository;
use App\Logic\Values\Message;
use App\Logic\Values\UserDto;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

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
                if (!$this->cacheRepository->checkUserExists($user->userId)) {
                    if ($this->userRepository->exists($user->userId)) {
                        $this->userRepository->create($user);
                    }

                    // Set primary state and clear options
                    $this->cacheRepository->setState($user->userId, 'start');
                    $this->cacheRepository->removeOptions($user->userId);
                }
                // send options
                return MessageFacade::createMenu('Hello! I\'m your text adviser. Choose one of the next options');
            }

            if ($input === self::MENU_COMMAND) {
                return MessageFacade::createMenu('Choose one of the next options');
            }

            // if it's not '/start' command and customer not added, send him a prompt
            if (!$this->cacheRepository->checkUserExists($user->userId)) {
                Bugsnag::leaveBreadcrumb('State cache', null, ['state' => $this->cacheRepository->getCurrentState($user->userId)]);
                Bugsnag::notifyError("State", "State wasn't set");

                return MessageFacade::createText('Please, enter '.self::START_COMMAND.' command to start chatting');
            }

            // if option chosen ask user for putting text
            if ($this->cacheRepository->getCurrentState($user->userId) === 'start' && in_array($input, array_keys(static::$options))) {
                $this->cacheRepository->setState($user->userId, 'option_selected');
                $this->cacheRepository->setOption($user->userId, $input);

                return MessageFacade::createText('Please, put your text. Note: Your text should be not more than 500 words');
            }

            // if customer chose option it can put its text
            if ($this->cacheRepository->getCurrentOption($user->userId) &&
                $this->cacheRepository->getCurrentState($user->userId) === 'option_selected') {
                // getting answer from ChatGPT
                $option = $this->cacheRepository->getCurrentOption($user->userId);
                $prompt = data_get(static::$prompts, $option);

                if(!$prompt){
                    throw new NotFoundOptionException();
                }

                $prompt .= ": \"{$input}\"";

                $answer = $this->model->handlePrompt($prompt);

                $this->cacheRepository->setState($user->userId, 'start');
                $this->cacheRepository->removeOptions($user->userId);

                return MessageFacade::createAIAnswer($answer);
            }

            Bugsnag::leaveBreadcrumb("Option", null, ['option' => $this->cacheRepository->getCurrentOption($user->userId)]);
            Bugsnag::notifyError("Incorrect input", "Incorrect input");

            return MessageFacade::createText('Incorrect input. Please, follow instructions');
        } catch (\Throwable $e) {
            Bugsnag::notifyException($e);

            return MessageFacade::createText('Some error occurs. Please, try again later');
        }
    }

}
