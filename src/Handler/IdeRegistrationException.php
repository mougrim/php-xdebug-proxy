<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Exception;
use Mougrim\XdebugProxy\Enum\RegistrationError;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class IdeRegistrationException extends Exception
{
    public function __construct(
        protected RegistrationError $error,
        string $message,
        protected string $command = 'proxyerror',
    ) {
        parent::__construct($message, $error->value);
    }

    public function getError(): RegistrationError
    {
        return $this->error;
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
