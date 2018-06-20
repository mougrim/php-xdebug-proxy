<?php

namespace Mougrim\XdebugProxy;

use Mougrim\XdebugProxy\Handler\CommandToXdebugParser;
use Mougrim\XdebugProxy\Xml\XmlDocument;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface RequestPreparer
{
    /**
     * @param XmlDocument $xmlRequest
     * @param string $rawRequest
     *
     * @return void
     */
    public function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest);

    /**
     * @param string $request
     * @param CommandToXdebugParser $commandToXdebugParser
     *
     * @return string
     */
    public function prepareRequestToXdebug(string $request, CommandToXdebugParser $commandToXdebugParser): string;
}
