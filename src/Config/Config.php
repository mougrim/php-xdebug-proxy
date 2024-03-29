<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Config;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Config
{
    public const DEFAULT_XDEBUG_SERVER_CONFIG = [
        'listen' => '127.0.0.1:9002',
    ];

    public const DEFAULT_IDE_REGISTRATION_SERVER_CONFIG = [
        'listen' => '127.0.0.1:9001',
    ];

    public const DEFAULT_IDE_SERVER_CONFIG = [
        'defaultIde' => '127.0.0.1:9000',
        'predefinedIdeList' => [
            'idekey' => '127.0.0.1:9000',
        ],
    ];

    /** @var array<array-key, array> */
    protected array $config;
    protected Server $xdebugServer;
    protected Server $ideRegistrationServer;
    protected IdeServer $ideServer;

    /**
     * @param array<array-key, array> $config
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->xdebugServer = new Server(
            $this->config['xdebugServer'] ?? [],
            static::DEFAULT_XDEBUG_SERVER_CONFIG
        );
        $this->ideRegistrationServer = new Server(
            $this->config['ideRegistrationServer'] ?? [],
            static::DEFAULT_IDE_REGISTRATION_SERVER_CONFIG
        );
        $this->ideServer = new IdeServer(
            $this->config['ideServer'] ?? [],
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
