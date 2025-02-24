<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy;

use Monolog\Formatter\LineFormatter;

use function is_scalar;
use function var_export;

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
