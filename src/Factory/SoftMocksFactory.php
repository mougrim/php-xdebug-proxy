<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Config\SoftMocksConfig;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\RequestPreparer\SoftMocksRequestPreparer;
use Psr\Log\LoggerInterface;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type XdebugProxySoftMocksConfigArray from SoftMocksConfig
 */
class SoftMocksFactory extends DefaultFactory
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @param XdebugProxySoftMocksConfigArray $config
     * @return SoftMocksConfig
     */
    public function createConfig(array $config): Config
    {
        return new SoftMocksConfig($config);
    }

    /**
     * @param SoftMocksConfig $config
     * @return RequestPreparer[]
     *
     * @throws RequestPreparerException
     * @throws RequestPreparerError
     */
    public function createRequestPreparers(LoggerInterface $logger, Config $config): array
    {
        $requestPreparers = parent::createRequestPreparers($logger, $config);
        $requestPreparers[] = $this->createSoftMocksRequestPreparer($logger, $config);

        return $requestPreparers;
    }

    /**
     * @throws RequestPreparerError
     */
    public function createSoftMocksRequestPreparer(
        LoggerInterface $logger,
        SoftMocksConfig $config,
    ): SoftMocksRequestPreparer {
        return new SoftMocksRequestPreparer($logger, $config->getSoftMocks()->getInitScript());
    }
}
