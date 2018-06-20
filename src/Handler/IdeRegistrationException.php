<?php

namespace Mougrim\XdebugProxy\Handler;

use Exception;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class IdeRegistrationException extends Exception
{
    protected $command;

    public function __construct(int $error_id, string $message, string $command = 'proxyerror')
    {
        parent::__construct($message, $error_id);
        $this->command = $command;
    }

    public function getErrorId(): int
    {
        return $this->code;
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
