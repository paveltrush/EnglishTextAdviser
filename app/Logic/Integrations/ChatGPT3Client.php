<?php

namespace App\Logic\Integrations;

use App\Logic\Integrations\Values\GPT3PromptResponse;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use OpenAI;

class ChatGPT3Client implements GeneretingModel
{
    public const MODEL = 'text-davinci-003';
    public const MAX_TOKENS = 950;
    public const TEMPERATURE = 0;

    public function handlePrompt(string $prompt): string
    {
        Bugsnag::leaveBreadcrumb('Prompt', null, ['prompt_text' => $prompt]);

        $result = OpenAI::completions()->create([
            'model'       => self::MODEL,
            'prompt'      => $prompt,
            'max_tokens'  => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
        ]);

        Bugsnag::leaveBreadcrumb('Open AI response', null, $result);

        return (new GPT3PromptResponse($result))->getContent();
    }
}
