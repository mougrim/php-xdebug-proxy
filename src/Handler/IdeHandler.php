<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\Socket\ResourceSocket;
use Mougrim\XdebugProxy\Enum\RegistrationCommand;
use Mougrim\XdebugProxy\Xml\XmlDocument;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface IdeHandler extends Handler
{
    /** @var array<string, array{supportedArguments: array<string>, requiredArguments: array<string>}> */
    public const REGISTRATION_ARGUMENTS = [
        /** @uses RegistrationCommand::Init */
        'proxyinit' => [
            'supportedArguments' => ['-p', '-k'],
            'requiredArguments' => ['-p', '-k'],
        ],
        /** @uses RegistrationCommand::Stop */
        'proxystop' => [
            'supportedArguments' => ['-k'],
            'requiredArguments' => ['-k'],
        ],
    ];

    /**
     * @return array<string, string>
     */
    public function getIdeList(): array;

    /**
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     */
    public function processRequest(XmlDocument $xmlRequest, string $rawRequest, ResourceSocket $xdebugSocket): void;

    public function close(ResourceSocket $xdebugSocket): void;
}
