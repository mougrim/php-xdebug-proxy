<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Enum;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
enum RegistrationCommand: string
{
    case Init = 'proxyinit';
    case Stop = 'proxystop';
}
