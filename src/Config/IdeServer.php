<?php

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class IdeServer
{
    protected $config;
    protected $defaultConfig;

    public function __construct(array $config, array $defaultConfig)
    {
        $this->config = $config;
        $this->defaultConfig = $defaultConfig;
    }

    public function getDefaultIde(): string
    {
        return $this->config['defaultIde'] ?? $this->defaultConfig['defaultIde'];
    }

    public function getPredefinedIdeList(): array
    {
        return $this->config['predefinedIdeList'] ?? $this->defaultConfig['predefinedIdeList'];
    }
}
