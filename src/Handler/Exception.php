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
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        protected array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
