<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\Socket\ServerSocket;
use Generator;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface Handler
{
    public function handle(ServerSocket $socket): Generator;
}
