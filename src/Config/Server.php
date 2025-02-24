<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-type ServerConfigArray array{listen?: string}
 * @phpstan-type ServerDefaultConfigArray array{listen: string}
 */
class Server
{
    /**
     * @param ServerConfigArray $config
     * @param ServerDefaultConfigArray $defaultConfig
     */
    public function __construct(
        protected readonly array $config,
        protected readonly array $defaultConfig,
    ) {
    }

    public function getListen(): string
    {
        return $this->config['listen'] ?? $this->defaultConfig['listen'];
    }
}
