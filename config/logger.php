<?php
/**
 * @author Mougrim <rinat@mougrim.ru>
 */

namespace Mougrim\XdebugProxy;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use const STDOUT;

return (new Logger('xdebug-proxy'))
    ->pushHandler(
        (new StreamHandler(new ResourceOutputStream(STDOUT)))
            ->setFormatter(new LoggerFormatter())
    );
