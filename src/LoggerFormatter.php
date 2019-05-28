<?php

namespace Mougrim\XdebugProxy;

use Monolog\Formatter\LineFormatter;
use function is_scalar;
use function /** @noinspection ForgottenDebugOutputInspection */ var_export;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class LoggerFormatter extends LineFormatter
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function convertToString($data): string
    {
        if (is_scalar($data)) {
            return (string) $data;
        }

        return var_export($data, true);
    }
}
