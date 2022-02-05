<?php

namespace Mougrim\XdebugProxy\Handler;

use Amp\Socket\ServerSocket;
use Generator;
use Mougrim\XdebugProxy\Xml\XmlDocument;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface IdeHandler extends Handler
{
    public const REGISTRATION_COMMAND_INIT = 'proxyinit';
    public const REGISTRATION_COMMAND_STOP = 'proxystop';

    public const REGISTRATION_ARGUMENTS = [
        self::REGISTRATION_COMMAND_INIT => [
            'supportedArguments' => ['-p', '-k'],
            'requiredArguments' => ['-p', '-k'],
        ],
        self::REGISTRATION_COMMAND_STOP => [
            'supportedArguments' => ['-k'],
            'requiredArguments' => ['-k'],
        ],
    ];

    public const REGISTRATION_ERROR_UNKNOWN_COMMAND = 1;
    public const REGISTRATION_ERROR_ARGUMENT_FORMAT = 2;
    public const REGISTRATION_ERROR_MISSING_REQUIRED_ARGUMENTS = 3;

    public function getIdeList(): array;

    /**
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     */
    public function processRequest(XmlDocument $xmlRequest, string $rawRequest, ServerSocket $xdebugSocket): Generator;

    public function close(ServerSocket $xdebugSocket): Generator;
}
