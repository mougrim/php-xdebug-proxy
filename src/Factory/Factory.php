<?php

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Handler\IdeHandler;
use Mougrim\XdebugProxy\Handler\XdebugHandler;
use Mougrim\XdebugProxy\Proxy;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Psr\Log\LoggerInterface;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface Factory
{
    public function createConfig(array $config): Config;

    public function createXmlConverter(LoggerInterface $logger): XmlConverter;

    /**
     * @param LoggerInterface $logger
     * @param XmlConverter $xmlConverter
     * @param RequestPreparer[] $requestPreparers
     *
     * @return IdeHandler
     */
    public function createIdeHandler(
        LoggerInterface $logger,
        XmlConverter $xmlConverter,
        array $requestPreparers
    ): IdeHandler;

    /**
     * @param LoggerInterface $logger
     *
     * @throws RequestPreparerException
     * @throws RequestPreparerError
     *
     * @return RequestPreparer[]
     */
    public function createRequestPreparers(LoggerInterface $logger): array;

    public function createXdebugHandler(
        LoggerInterface $logger,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler
    ): XdebugHandler;

    public function createProxy(
        LoggerInterface $logger,
        Config $config,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
        XdebugHandler $xdebugHandler
    ): Proxy;
}
