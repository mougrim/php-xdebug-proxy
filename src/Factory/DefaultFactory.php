<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Config\IdeServer as IdeServerConfig;
use Mougrim\XdebugProxy\Handler\DefaultIdeHandler;
use Mougrim\XdebugProxy\Handler\DefaultXdebugHandler;
use Mougrim\XdebugProxy\Handler\IdeHandler;
use Mougrim\XdebugProxy\Handler\XdebugHandler;
use Mougrim\XdebugProxy\Proxy;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\Xml\DomXmlConverter;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Psr\Log\LoggerInterface;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type XdebugProxyConfigArray from Config
 */
class DefaultFactory implements Factory
{
    /**
     * @param array<string, array<string, mixed>> $config
     * @phpstan-param XdebugProxyConfigArray $config
     */
    public function createConfig(array $config): Config
    {
        return new Config($config);
    }

    public function createXmlConverter(LoggerInterface $logger): XmlConverter
    {
        return new DomXmlConverter($logger);
    }

    /**
     * @param RequestPreparer[] $requestPreparers
     */
    public function createIdeHandler(
        LoggerInterface $logger,
        IdeServerConfig $config,
        XmlConverter $xmlConverter,
        array $requestPreparers,
    ): IdeHandler {
        return new DefaultIdeHandler($logger, $config, $xmlConverter, $requestPreparers);
    }

    public function createRequestPreparers(LoggerInterface $logger, Config $config): array
    {
        return [];
    }

    public function createXdebugHandler(
        LoggerInterface $logger,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
    ): XdebugHandler {
        return new DefaultXdebugHandler($logger, $xmlConverter, $ideHandler);
    }

    public function createProxy(
        LoggerInterface $logger,
        Config $config,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
        XdebugHandler $xdebugHandler,
    ): Proxy {
        return new Proxy($logger, $config, $xmlConverter, $ideHandler, $xdebugHandler);
    }
}
