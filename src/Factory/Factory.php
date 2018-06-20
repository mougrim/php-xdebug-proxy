<?php

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Handler\IdeHandler;
use Mougrim\XdebugProxy\Handler\XdebugHandler;
use Mougrim\XdebugProxy\Proxy;
use Mougrim\XdebugProxy\RequestPreparer;
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
     * @return RequestPreparer[]
     */
    public function createRequestPreparers(): array;

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
