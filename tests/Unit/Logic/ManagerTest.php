<?php

namespace Tests\Unit\Logic;

use App\Logic\Exceptions\WordsAmountExceededException;
use App\Logic\Integrations\GeneretingModel;
use App\Logic\Manager;
use App\Logic\MessageRepresentation;
use App\Logic\Repositories\Cache\CacheRepository;
use App\Logic\Repositories\DB\UserRepository;
use App\Logic\Values\UserDto;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Str;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;
use Faker;

class ManagerTest extends TestCase
{
    /**
     * @var GeneretingModel|LegacyMockInterface|MockInterface
     */
    private GeneretingModel|LegacyMockInterface|MockInterface $clientMock;
    /**
     * @var UserRepository|LegacyMockInterface|MockInterface
     */
    private UserRepository|LegacyMockInterface|MockInterface $userRepositoryMock;
    /**
     * @var CacheRepository|LegacyMockInterface|MockInterface
     */
    private CacheRepository|LegacyMockInterface|MockInterface $cacheSpy;
    /**
     * @var Manager
     */
    private Manager $managerMock;
    /**
     * @var Faker\Generator
     */
    private Faker\Generator $faker;
    private UserDto $user;
    private Mockery\MockInterface|Bugsnag $bugsnagSpy;

    /**
     * If command /start was typed by user and the user isn't added to the system
     *
     * @return void
     */
    public function testUserInputStart()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->once()->andReturn(false);

        $this->userRepositoryMock->shouldReceive('exists')->once()->andReturn(false);

        // -> check the user added
        $this->userRepositoryMock->shouldReceive('create')->once();

        $message = $this->managerMock->solveEverything(Manager::START_COMMAND, $this->user);

        // -> show menu to the user if it's registered in the system
        $this->assertEquals(MessageRepresentation::createMenu(MessageRepresentation::INTRODUCTION_MESSAGE), $message);

        // -> check "start" state set in cache
        $this->cacheSpy->shouldHaveReceived('setState', function(string $userId, string $state){
            return $state === Manager::START_STATE;
        });
    }

    /**
     * If user persists in db but for some reason absents in cache repo, don't add them to db
     *
     * @return void
     */
    public function testUserPersistsInDBButNotInCache()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->once()->andReturn(false);

        $this->userRepositoryMock->shouldReceive('exists')->once()->andReturn(true);

        $this->userRepositoryMock->shouldReceive('create')->never();

        $message = $this->managerMock->solveEverything(Manager::START_COMMAND, $this->user);

        // -> show menu to the user if it's registered in the system
        $this->assertEquals(MessageRepresentation::createMenu(MessageRepresentation::INTRODUCTION_MESSAGE), $message);

        // -> check "start" state set in cache
        $this->cacheSpy->shouldHaveReceived('setState', function(string $userId, string $state){
            return $state === Manager::START_STATE;
        });
    }

    /**
     * If a user not registered, and it typed not a "start" command ask them to do it
     *
     * @return void
     */
    public function testUnregisteredUserPutSomething()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->once()->andReturn(false);

        $message = $this->managerMock->solveEverything(Manager::MENU_COMMAND, $this->user);

        $this->assertEquals(MessageRepresentation::createText(MessageRepresentation::ASK_FOR_START_COMMAND), $message);
    }

    /**
     * @return void
     */
    public function testTypedMenuCommand()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->once()->andReturn(true);

        $message = $this->managerMock->solveEverything(Manager::MENU_COMMAND, $this->user);

        $this->assertEquals(MessageRepresentation::createMenu(MessageRepresentation::MENU_TEXT), $message);
    }

    /**
     * If some menu item was chosen, send request back to put a text
     * success result: an option form the list and last status is start
     *
     * @return void
     */
    public function testMenuItemChosen()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->andReturn(true);

        $chosenOption = $this->getOption();

        $message = $this->managerMock->solveEverything($chosenOption, $this->user);

        $this->assertEquals(MessageRepresentation::createText(MessageRepresentation::ASK_FOR_TEXT), $message);

        $this->cacheSpy->shouldHaveReceived('setState', function(string $userId, string $state){
            return $state === Manager::OPTION_SELECTED_STATE;
        });

        $this->cacheSpy->shouldHaveReceived('setOption', function($userId, string $option) use ($chosenOption){
            return $chosenOption === $option;
        });
    }

    /**
     * @return void
     */
    public function testUserTypedIncorrectOption()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->andReturn(true);
        $chosenOption = 'random_option';
        $message = $this->managerMock->solveEverything($chosenOption, $this->user);

        $this->bugsnagSpy->shouldHaveReceived('notifyError', function (string $name, string $message){
            return $name === 'Incorrect input' && $message === 'Incorrect input';
        });

        $this->assertEquals(MessageRepresentation::createText(MessageRepresentation::INCORRECT_INPUT), $message);
    }

    /**
     * When text is inserted send request to ChatGPT
     *
     * @return void
     */
    public function testUserSentTextSuccess()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->andReturn(true);

        $this->cacheSpy->shouldReceive('getCurrentOption')->andReturn($this->getOption());
        $this->cacheSpy->shouldReceive('getCurrentState')->andReturn(Manager::OPTION_SELECTED_STATE);

        $input = $this->faker->words(Manager::MAX_WORDS, true);
        $aiResponse = $this->faker->text;

        $this->clientMock->shouldReceive('handlePrompt')->once()->andReturn($aiResponse);
        $this->cacheSpy->shouldReceive('removeOptions')->once();

        $message = $this->managerMock->solveEverything($input, $this->user);

        $this->assertEquals(MessageRepresentation::createAIAnswer($aiResponse), $message);

        $this->cacheSpy->shouldHaveReceived('setState', function (string $userId, string $state){
            return $userId === $this->user->userId && $state === Manager::START_STATE;
        });
    }

    /**
     * If words count exceeded, fire exception
     *
     * @return void
     */
    public function testUserSentTextWithExceededWordsAmount()
    {
        $this->init();

        $this->cacheSpy->shouldReceive('checkUserExists')->andReturn(true);

        $input = $this->faker->words(Manager::MAX_WORDS + 1, true);

        $chosenOption = $this->getOption();

        $this->cacheSpy->shouldReceive('getCurrentOption')->andReturn($chosenOption);
        $this->cacheSpy->shouldReceive('getCurrentState')->andReturn(Manager::OPTION_SELECTED_STATE);

        $message = $this->managerMock->solveEverything($input, $this->user);

        $this->bugsnagSpy->shouldHaveReceived('notifyException', function ($exception){
            return get_class($exception) === WordsAmountExceededException::class;
        });

        $this->assertEquals(MessageRepresentation::createText(MessageRepresentation::WORDS_AMOUNT_EXCEEDED), $message);
    }

    /**
     * If request failed, send error message and notification to bugsnag
     *
     * @return void
     */
    public function testUserSentTextAndRequestFailed()
    {
        $this->init();

        $input = $this->faker->text(Manager::MAX_WORDS);

        $chosenOption = $this->getOption();

        $this->cacheSpy->shouldReceive('checkUserExists')->andReturn(true);

        $this->cacheSpy->shouldReceive('getCurrentOption')->andReturn($chosenOption);
        $this->cacheSpy->shouldReceive('getCurrentState')->andReturn(Manager::OPTION_SELECTED_STATE);

        $this->clientMock->shouldReceive('handlePrompt')->andThrow(new \Exception("Incorrect request"));

        $message = $this->managerMock->solveEverything($input, $this->user);

        $this->assertEquals(MessageRepresentation::createText(MessageRepresentation::ERROR_OCCURS), $message);

        $this->bugsnagSpy->shouldHaveReceived('leaveBreadcrumb', function () {
            $args = func_get_args();

            return data_get($args, 0) === 'State' && data_get($args, 2) === ['state' => Manager::OPTION_SELECTED_STATE];
        });

        $this->bugsnagSpy->shouldHaveReceived('leaveBreadcrumb', function () use ($chosenOption){
            $args = func_get_args();

            return data_get($args, 0) === 'Option' && data_get($args, 2) === ['option' => $chosenOption];
        });
    }

    protected function init(): void
    {
        $this->clientMock = Mockery::mock(GeneretingModel::class);

        $this->userRepositoryMock = Mockery::mock(UserRepository::class);

        $this->cacheSpy = Mockery::spy(CacheRepository::class);

        $this->managerMock = new Manager($this->clientMock, $this->userRepositoryMock, $this->cacheSpy);

        $this->faker = Faker\Factory::create();

        $this->user = new UserDto([
            'userId' => Str::random(10),
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'username' => $this->faker->userName
        ]);

        $this->bugsnagSpy = Bugsnag::spy();
    }

    protected function getOption(): string
    {
        return array_rand(Manager::$options);
    }
}
