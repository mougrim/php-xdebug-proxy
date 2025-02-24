<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type ServerConfigArray from Server
 * @phpstan-import-type IdeServerConfigArray from IdeServer
 * @phpstan-import-type SoftMocksConfigArray from SoftMocks
 * @phpstan-import-type SoftMocksDefaultConfigArray from SoftMocks
 *
 * @phpstan-type XdebugProxySoftMocksConfigArray array{xdebugServer?: ServerConfigArray, ideRegistrationServer?: ServerConfigArray, ideServer?: IdeServerConfigArray, softMocks?: SoftMocksConfigArray}
 */
class SoftMocksConfig extends Config
{
    /** @var SoftMocksDefaultConfigArray */
    public const DEFAULT_SOFT_MOCKS_CONFIG = [
        'initScript' => '',
    ];

    protected readonly SoftMocks $softMocks;

    /**
     * @param XdebugProxySoftMocksConfigArray $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->softMocks = new SoftMocks(
            $config['softMocks'] ?? [],
            static::DEFAULT_SOFT_MOCKS_CONFIG
        );
    }

    public function getSoftMocks(): SoftMocks
    {
        return $this->softMocks;
    }
}
