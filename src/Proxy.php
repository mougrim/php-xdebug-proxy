<?php

namespace Mougrim\XdebugProxy;

use Amp\Loop;
use Amp\Socket\SocketException;
use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Handler\IdeHandler;
use Mougrim\XdebugProxy\Handler\XdebugHandler;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Psr\Log\LoggerInterface;
use function Amp\asyncCoroutine;
use function Amp\Socket\listen;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class Proxy
{
    protected $logger;
    protected $config;
    protected $xmlConverter;
    protected $ideHandler;
    protected $xdebugHandler;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        XmlConverter $xmlConverter,
        IdeHandler $ideHandler,
        XdebugHandler $xdebugHandler
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->xmlConverter = $xmlConverter;
        $this->ideHandler = $ideHandler;
        $this->xdebugHandler = $xdebugHandler;
    }

    public function run()
    {
        Loop::defer([$this, 'runIdeRegistration']);
        Loop::defer([$this, 'runXdebug']);
        Loop::run();
    }

    /**
     * @throws SocketException
     */
    public function runXdebug()
    {
        $xdebugHandler = asyncCoroutine([$this->xdebugHandler, 'handle']);
        $server = listen($this->config->getXdebugServer()->getListen());
        $this->logger->notice("[Proxy][Xdebug] Listening for new connections on '{$server->getAddress()}'...");
        while ($socket = yield $server->accept()) {
            $xdebugHandler($socket);
        }
    }

    /**
     * @throws SocketException
     */
    public function runIdeRegistration()
    {
        $listen = $this->config->getIdeRegistrationServer()->getListen();
        if (!$listen) {
            $this->logger->notice('[Proxy][IdeRegistration] IDE registration is disabled by config, skip it.');

            return;
        }
        $ideHandler = asyncCoroutine([$this->ideHandler, 'handle']);
        $server = listen($listen);
        $this->logger->notice("[Proxy][IdeRegistration] Listening for new connections on '{$server->getAddress()}'...");
        while ($socket = yield $server->accept()) {
            $ideHandler($socket);
        }
    }
}
