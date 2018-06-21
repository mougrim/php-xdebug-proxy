<?php
/**
 * @author Mougrim <rinat@mougrim.ru>
 */

namespace Mougrim\XdebugProxy;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use const STDOUT;
use function class_exists;

if (!class_exists(StreamHandler::class)) {
    throw new RunError('You should install "amphp/log" by default or provide your custom config/logger.php via config for use php-xdebug-proxy');
}

return (new Logger('xdebug-proxy'))
    ->pushHandler(
        (new StreamHandler(new ResourceOutputStream(STDOUT)))
            ->setFormatter(new LoggerFormatter())
    );
