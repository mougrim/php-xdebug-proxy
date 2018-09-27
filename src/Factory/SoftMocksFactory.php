<?php

namespace Mougrim\XdebugProxy\Factory;

use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\SoftMocksRequestPreparer;
use Psr\Log\LoggerInterface;
use function array_merge;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocksFactory extends DefaultFactory
{
    /**
     * {@inheritdoc}
     */
    public function createRequestPreparers(LoggerInterface $logger): array
    {
        $requestPreparers = parent::createRequestPreparers($logger);

        return array_merge($requestPreparers, [$this->createSoftMocksRequestPreparer($logger)]);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @throws RequestPreparerError
     *
     * @return SoftMocksRequestPreparer
     */
    public function createSoftMocksRequestPreparer(LoggerInterface $logger): SoftMocksRequestPreparer
    {
        return new SoftMocksRequestPreparer($logger);
    }
}
