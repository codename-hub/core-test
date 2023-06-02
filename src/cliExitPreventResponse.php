<?php

namespace codename\core\test;

use codename\core\response\cli;

/**
 * Response that prevents die/flush/exit via pushOutput
 */
class cliExitPreventResponse extends cli
{
    /**
     * @inheritDoc
     */
    public function pushOutput(): void
    {
    }
}
