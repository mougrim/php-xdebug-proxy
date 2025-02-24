<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Enum;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
enum RegistrationError: int
{
    case UnknownCommand = 1;
    case ArgumentFormat = 2;
    case MissingRequiredArguments = 3;
}
