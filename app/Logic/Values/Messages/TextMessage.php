<?php

namespace App\Logic\Values\Messages;

use App\Logic\Values\Message;

/**
 * @property $text
 */
class TextMessage extends Message
{
    public function __construct($text)
    {
        $this->text = $text;

        parent::__construct([$text]);
    }

}
