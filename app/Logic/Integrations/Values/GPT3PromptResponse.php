<?php

namespace App\Logic\Integrations\Values;

class GPT3PromptResponse implements PromptResponse
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getContent(): string
    {
        return data_get($this->data, 'choices.0.text');
    }
}
