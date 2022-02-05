<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class IdeServer
{
    /** @var array{defaultIde: string|null, predefinedIdeList: array<string, string>|null} */
    protected array $config;
    /** @var array{defaultIde: string, predefinedIdeList: array<string, string>} */
    protected array $defaultConfig;

    /**
     * @param array{defaultIde: string|null, predefinedIdeList: array<string, string>|null} $config
     * @param array{defaultIde: string, predefinedIdeList: array<string, string>} $defaultConfig
     */
    public function __construct(array $config, array $defaultConfig)
    {
        $this->config = $config;
        $this->defaultConfig = $defaultConfig;
    }

    public function getDefaultIde(): string
    {
        return $this->config['defaultIde'] ?? $this->defaultConfig['defaultIde'];
    }

    /**
     * @return array<string, string>
     */
    public function getPredefinedIdeList(): array
    {
        return $this->config['predefinedIdeList'] ?? $this->defaultConfig['predefinedIdeList'];
    }
}
