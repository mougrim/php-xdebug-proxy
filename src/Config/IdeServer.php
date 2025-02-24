<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-type IdeServerConfigArray array{defaultIde?: string, predefinedIdeList?: array<string, string>}
 * @phpstan-type IdeServerDefaultConfigArray array{defaultIde: string, predefinedIdeList: array<string, string>}
 */
class IdeServer
{
    /**
     * @param IdeServerConfigArray $config
     * @param IdeServerDefaultConfigArray $defaultConfig
     */
    public function __construct(
        protected readonly array $config,
        protected readonly array $defaultConfig,
    ) {
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
