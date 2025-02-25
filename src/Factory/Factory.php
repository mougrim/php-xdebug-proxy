<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Config\IdeServer as IdeServerConfig;
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
 *
 * @phpstan-import-type XdebugProxyConfigArray from Config
 */
interface Factory
{
    /**
     * @param array<string, array<string, mixed>> $config
     * @phpstan-param XdebugProxyConfigArray $config
     */
    public function createConfig(array $config): Config;

    public function createXmlConverter(LoggerInterface $logger): XmlConverter;

    /**
     * @param RequestPreparer[] $requestPreparers
     */
    public function createIdeHandler(
        LoggerInterface $logger,
        IdeServerConfig $config,
        XmlConverter $xmlConverter,
        array $requestPreparers,
    ): IdeHandler;

    /**
     * Request preparers will be called:
     * - on request to ide from first to last;
     * - on request to xdebug from last to first.
     *
     * @return RequestPreparer[]
     *
     * @throws RequestPreparerException
     * @throws RequestPreparerError
     */
    public function createRequestPreparers(LoggerInterface $logger, Config $config): array;

    public function createXdebugHandler(
        LoggerInterface $logger,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
    ): XdebugHandler;

    public function createProxy(
        LoggerInterface $logger,
        Config $config,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
        XdebugHandler $xdebugHandler,
    ): Proxy;
}
