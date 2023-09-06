<?php

namespace Tests\Unit\Logic;

use Tests\TestCase;

class ManagerTest extends TestCase
{
    public function testSolveEverything()
    {
        // if user not registered, and it typed not a "start" command ask them to do it
        // if command /start was typed by user and the user isn't added to the system
        // -> show menu to the user if it's registered in the system
        // Show menu if /menu command was typed
        // If some menu item was chosen, send request back to put a text
        // -> When text is inserted send request to ChatGPT
        // If some option pointed wrong, send error message
        // If some exception occurs, it should be sent to bugsnag
    }
}
