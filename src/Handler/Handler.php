<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\Socket\ResourceSocket;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface Handler
{
    public function handle(ResourceSocket $socket): void;
}
