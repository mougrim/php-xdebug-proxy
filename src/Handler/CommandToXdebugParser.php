<?php

namespace Mougrim\XdebugProxy\Handler;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface CommandToXdebugParser
{
    /**
     * @param string $request
     *
     * @return array [$command, $arguments]
     */
    public function parseCommand(string $request): array;

    /**
     * @param string $command
     * @param array $arguments
     *
     * @return string
     */
    public function buildCommand(string $command, array $arguments): string;
}
