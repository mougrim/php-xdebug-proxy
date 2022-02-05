<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Exception as BaseException;
use Throwable;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Exception extends BaseException
{
    protected array $context;

    public function __construct(string $message, array $context = [], Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
