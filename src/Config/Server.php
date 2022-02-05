<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Server
{
    /** @var array{listen: string|null} */
    protected array $config;
    /** @var array{listen: string} */
    protected array $defaultConfig;

    /**
     * @param array{listen: string|null} $config
     * @param array{listen: string} $defaultConfig
     */
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
