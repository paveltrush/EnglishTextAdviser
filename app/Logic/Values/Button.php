<?php

namespace App\Logic\Values;

use Illuminate\Support\Fluent;

/**
 * @property string $text
 * @property string $value
 */
class Button extends Fluent
{
    public function __construct(string $text, string $value)
    {
        $this->text  = $text;
        $this->value = $value;

        parent::__construct(['text' => $text, 'value' => $value]);
    }

}
