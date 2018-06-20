<?php

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Server
{
    protected $config;
    protected $defaultConfig;

    public function __construct(array $config, array $defaultConfig)
    {
        $this->config = $config;
        $this->defaultConfig = $defaultConfig;
    }

    public function getListen(): string
    {
        return $this->config['listen'] ?? $this->defaultConfig['listen'];
    }
}
