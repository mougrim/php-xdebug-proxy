<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy;

use Amp\Socket\ResourceServerSocket;
use Amp\Socket\SocketException;
use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Handler\IdeHandler;
use Mougrim\XdebugProxy\Handler\XdebugHandler;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Revolt\EventLoop\UnsupportedFeatureException;

use const SIGINT;
use const SIGTERM;

use function Amp\async;
use function Amp\Socket\listen;
use function extension_loaded;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Proxy
{
    /** @var ResourceServerSocket[] */
    protected array $servers = [];

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly Config $config,
        protected readonly XmlConverter $xmlConverter,
        protected readonly IdeHandler $ideHandler,
        protected readonly XdebugHandler $xdebugHandler,
    ) {
    }

    /**
     * @throws UnsupportedFeatureException
     */
    public function run(): void
    {
        if (extension_loaded('pcntl')) {
            $terminateClosure = fn (string $callbackId, int $signal) => $this->terminate();
            EventLoop::onSignal(SIGTERM, $terminateClosure);
            EventLoop::onSignal(SIGINT, $terminateClosure);
        }

        async(fn () => $this->runIdeRegistration());
        async(fn () => $this->runXdebug());
        EventLoop::run();
    }

    /**
     * @throws SocketException
     */
    public function runXdebug(): void
    {
        $server = listen($this->config->getXdebugServer()->getListen());
        $this->servers[] = $server;
        $this->logger->notice("[Proxy][Xdebug] Listening for new connections on '{$server->getAddress()->toString()}'...");
        while ($client = $server->accept()) {
            async(fn () => $this->xdebugHandler->handle($client));
        }
    }

    /**
     * @throws SocketException
     */
    public function runIdeRegistration(): void
    {
        $listen = $this->config->getIdeRegistrationServer()->getListen();
        if (!$listen) {
            $this->logger->notice('[Proxy][IdeRegistration] IDE registration is disabled by config, skip it.');

            return;
        }
        $server = listen($listen);
        $this->servers[] = $server;
        $this->logger->notice(
            "[Proxy][IdeRegistration] Listening for new connections on '{$server->getAddress()->toString()}'..."
        );
        while ($socket = $server->accept()) {
            async(fn () => $this->ideHandler->handle($socket));
        }
    }

    public function terminate(): void
    {
        foreach ($this->servers as $server) {
            $server->close();
        }
        $this->logger->notice('[Proxy][Terminating] Terminating proxy server.');
        EventLoop::getDriver()->stop();
        $this->servers = [];
    }
}
