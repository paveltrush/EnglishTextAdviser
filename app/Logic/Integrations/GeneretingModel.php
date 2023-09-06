<?php

namespace App\Logic\Integrations;

use App\Logic\Integrations\Values\PromptResponse;

interface GeneretingModel
{
    public function handlePrompt(string $prompt);
}
