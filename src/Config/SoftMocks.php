<?php

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocks
{
    protected $config;
    protected $defaultConfig;

    public function __construct(array $config, array $defaultConfig)
    {
        $this->config = $config;
        $this->defaultConfig = $defaultConfig;
    }

    public function getInitScript(): string
    {
        return $this->config['initScript'] ?? $this->defaultConfig['initScript'];
    }
}
