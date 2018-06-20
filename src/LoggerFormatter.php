<?php

namespace Mougrim\XdebugProxy;

use Monolog\Formatter\LineFormatter;
use function is_scalar;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class LoggerFormatter extends LineFormatter
{
    protected function convertToString($data): string
    {
        if (is_scalar($data)) {
            return (string) $data;
        }

        return var_export($data, true);
    }
}
