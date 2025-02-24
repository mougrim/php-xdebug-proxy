<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type ServerConfigArray from Server
 * @phpstan-import-type ServerDefaultConfigArray from Server
 * @phpstan-import-type IdeServerConfigArray from IdeServer
 * @phpstan-import-type IdeServerDefaultConfigArray from IdeServer
 *
 * @phpstan-type XdebugProxyConfigArray array{xdebugServer?: ServerConfigArray, ideRegistrationServer?: ServerConfigArray, ideServer?: IdeServerConfigArray}
 */
class Config
{
    /** @var ServerDefaultConfigArray */
    public const DEFAULT_XDEBUG_SERVER_CONFIG = [
        'listen' => '127.0.0.1:9002',
    ];

    /** @var ServerDefaultConfigArray */
    public const DEFAULT_IDE_REGISTRATION_SERVER_CONFIG = [
        'listen' => '127.0.0.1:9001',
    ];

    /** @var IdeServerDefaultConfigArray */
    public const DEFAULT_IDE_SERVER_CONFIG = [
        'defaultIde' => '127.0.0.1:9000',
        'predefinedIdeList' => [
            'idekey' => '127.0.0.1:9000',
        ],
    ];

    protected readonly Server $xdebugServer;
    protected readonly Server $ideRegistrationServer;
    protected readonly IdeServer $ideServer;

    /**
     * @param XdebugProxyConfigArray $config
     */
    public function __construct(array $config)
    {
        $this->xdebugServer = new Server(
            $config['xdebugServer'] ?? [],
            static::DEFAULT_XDEBUG_SERVER_CONFIG
        );
        $this->ideRegistrationServer = new Server(
            $config['ideRegistrationServer'] ?? [],
            static::DEFAULT_IDE_REGISTRATION_SERVER_CONFIG
        );
        $this->ideServer = new IdeServer(
            $config['ideServer'] ?? [],
            static::DEFAULT_IDE_SERVER_CONFIG
        );
    }

    public function getXdebugServer(): Server
    {
        return $this->xdebugServer;
    }

    public function getIdeRegistrationServer(): Server
    {
        return $this->ideRegistrationServer;
    }

    public function getIdeServer(): IdeServer
    {
        return $this->ideServer;
    }
}
