<?php

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Config\SoftMocksConfig;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\RequestPreparer\SoftMocksRequestPreparer;
use Psr\Log\LoggerInterface;
use function array_merge;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocksFactory extends DefaultFactory
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     * {@inheritdoc}
     *
     * @param array $config
     *
     * @return SoftMocksConfig
     */
    public function createConfig(array $config): Config
    {
        return new SoftMocksConfig($config);
    }

    /**
     * {@inheritdoc}
     *
     * @param LoggerInterface $logger
     * @param SoftMocksConfig $config
     *
     * @throws RequestPreparerException
     * @throws RequestPreparerError
     *
     * @return RequestPreparer[]
     */
    public function createRequestPreparers(LoggerInterface $logger, Config $config): array
    {
        $requestPreparers = parent::createRequestPreparers($logger, $config);

        return array_merge($requestPreparers, [$this->createSoftMocksRequestPreparer($logger, $config)]);
    }

    /**
     * @param LoggerInterface $logger
     * @param SoftMocksConfig $config
     *
     * @throws RequestPreparerError
     *
     * @return SoftMocksRequestPreparer
     */
    public function createSoftMocksRequestPreparer(LoggerInterface $logger, SoftMocksConfig $config): SoftMocksRequestPreparer
    {
        return new SoftMocksRequestPreparer($logger, $config->getSoftMocks()->getInitScript());
    }
}
