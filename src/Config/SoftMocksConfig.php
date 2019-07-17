<?php

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocksConfig extends Config
{
    public const DEFAULT_SOFT_MOCKS_CONFIG = [
        'initScript' => '',
    ];

    protected $softMocks;

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
