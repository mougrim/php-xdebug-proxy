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
    public function getIdeList(): array;

    /**
     * @param XmlDocument $xmlRequest
     * @param string $rawRequest
     * @param ServerSocket $xdebugSocket
     *
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     *
     * @return Generator
     */
    public function processRequest(XmlDocument $xmlRequest, string $rawRequest, ServerSocket $xdebugSocket): Generator;

    public function close(ServerSocket $xdebugSocket): Generator;
}
