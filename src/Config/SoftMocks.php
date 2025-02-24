<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-type SoftMocksConfigArray array{initScript?: string}
 * @phpstan-type SoftMocksDefaultConfigArray array{initScript: string}
 */
class SoftMocks
{
    /**
     * @param SoftMocksConfigArray $config
     * @param SoftMocksDefaultConfigArray $defaultConfig
     */
    public function __construct(
        protected readonly array $config,
        protected readonly array $defaultConfig,
    ) {
    }

    public function getInitScript(): string
    {
        return $this->config['initScript'] ?? $this->defaultConfig['initScript'];
    }
}
