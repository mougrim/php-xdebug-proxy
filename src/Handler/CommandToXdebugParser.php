<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface CommandToXdebugParser
{
    /**
     * @return array{0: string, 1: array<string, string>} [$command, $arguments]
     */
    public function parseCommand(string $request): array;

    /**
     * @param array<string, string> $arguments
     */
    public function buildCommand(string $command, array $arguments): string;
}
