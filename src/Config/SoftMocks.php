<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocks
{
    /** @var array{initScript: string|null} */
    protected array $config;
    /** @var array{initScript: string} */
    protected array $defaultConfig;

    /**
     * @param array{initScript: string|null} $config
     * @param array{initScript: string} $defaultConfig
     */
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
