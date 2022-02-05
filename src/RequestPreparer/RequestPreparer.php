<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\RequestPreparer;

use Mougrim\XdebugProxy\Handler\CommandToXdebugParser;
use Mougrim\XdebugProxy\Xml\XmlDocument;

/**
 * @author Mougrim <rinat@mougrim.ru>
 * You can use request preparer for example for changing path to files (in break points and execution files).
 */
interface RequestPreparer
{
    /**
     * In this method you can change $xmlRequest, which will be sent to ide.
     *
     * @param XmlDocument $xmlRequest request from xdebug to ide
     * @param string $rawRequest just for logging purposes
     *
     * @throws Exception
     * @throws Error
     */
    public function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest): void;

    /**
     * This method should return request based on $request, which will be sent to xdebug.
     * Use $commandToXdebugParser to parse the command in request and to rebuild the command.
     *
     * @param string $request command from ide to xdebug
     *
     * @throws Exception
     * @throws Error
     */
    public function prepareRequestToXdebug(string $request, CommandToXdebugParser $commandToXdebugParser): string;
}
