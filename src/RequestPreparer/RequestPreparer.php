<?php

namespace Mougrim\XdebugProxy\RequestPreparer;

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
     * @throws Exception
     * @throws Error
     *
     * @return void
     */
    public function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest);

    /**
     * @param string $request
     * @param CommandToXdebugParser $commandToXdebugParser
     *
     * @throws Exception
     * @throws Error
     *
     * @return string
     */
    public function prepareRequestToXdebug(string $request, CommandToXdebugParser $commandToXdebugParser): string;
}
