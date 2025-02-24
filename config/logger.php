<?php

declare(strict_types=1);
/**
 * @author Mougrim <rinat@mougrim.ru>
 */

namespace Mougrim\XdebugProxy\config;

use Amp\Log\StreamHandler;
use Monolog\Logger;
use Mougrim\XdebugProxy\LoggerFormatter;
use Mougrim\XdebugProxy\RunError;
use Psr\Log\LogLevel;

use function Amp\ByteStream\getStdout;
use function class_exists;

if (!class_exists(StreamHandler::class)) {
    throw new RunError(
        'You should install "amphp/log" by default or provide your custom config/logger.php via config for use php-xdebug-proxy'
    );
}

return (new Logger('xdebug-proxy'))
    ->pushHandler(
        (new StreamHandler(getStdout(), LogLevel::NOTICE))
            ->setFormatter(new LoggerFormatter())
    );
