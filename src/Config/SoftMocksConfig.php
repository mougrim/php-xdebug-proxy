<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocksConfig extends Config
{
    public const DEFAULT_SOFT_MOCKS_CONFIG = [
        'initScript' => '',
    ];

    protected SoftMocks $softMocks;

    /**
     * @param array<array-key, array> $config
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->softMocks = new SoftMocks(
            $this->config['softMocks'] ?? [],
            static::DEFAULT_SOFT_MOCKS_CONFIG
        );
    }

    public function getSoftMocks(): SoftMocks
    {
        return $this->softMocks;
    }
}
