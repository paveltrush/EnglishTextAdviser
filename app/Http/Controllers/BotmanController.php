<?php

namespace App\Http\Controllers;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Redis;
use OpenAI\Laravel\Facades\OpenAI;

class BotmanController extends Controller
{
    /**
     * Place your BotMan converison.
     */
    public function enterRequest()
    {
        $botman = app('botman');
        $cacheChoice = '';
        $options = [
            '/check_errors' => 'Check all grammatical and lexical errors and provide explanations referring to the rules. Don\'t correct sentences, just provide explanations',
            '/make_advanced' => 'Improve this text to the band of 9 by IELTS',
            '/make_formal' => 'Make text formal',
            '/make_informal' => 'Make text informal'
        ];

        $botman->hears('{message}', function($botman, $message) use ($options, $cacheChoice) {
            if ($message == '/start') {
                // Save user data
                // init cache
                $botman->reply(Question::create('Hello! I\'m your text adviser. Choose one of the next option')
                    ->addButton(Button::create('Check grammatical and lexical errors')->value('/check_errors'))
                    ->addButton(Button::create('Improve text to advanced level')->value('/make_advanced'))
                    ->addButton(Button::create('Make text formal')->value('/make_formal'))
                    ->addButton(Button::create('Make text informal')->value('/make_informal'))
                );
            } elseif ($message == '/check_errors' || $message == '/make_advanced' || $message == '/make_formal' || $message == '/make_informal') {
                // save user's choice in cache
                Redis::set('option', $message);
                $botman->reply("Please, put your text. Note: Your text should be not more than 350 words");
            } elseif (Redis::exists('option') && $message) {
                $prompt = data_get($options, Redis::get('option'));
                $replyText = 'Incorrect option';

                if ($prompt){
                    $prompt.=':/n/n '.$message;
                    $result = OpenAI::completions()->create([
                        'model' => 'text-davinci-003',
                        'prompt' => $prompt,
                        'max_tokens' => 600
                    ]);

                    $replyText = $result['choices'][0]['text'];
                }

                $botman->reply($replyText);
            }
            else {
                $botman->reply("Some error occurs");
            }
        });

        $botman->listen();
    }

    /**
     * Place your BotMan converison.
     */
    public function askReply($botman)
    {
        $botman->ask('Hello! What is your Name?', function(Answer $answer) {
            $name = $answer->getText();

            $this->say('Nice to meet you '.$name);
        });
    }
}
