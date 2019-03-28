<?php

namespace Mougrim\XdebugProxy\Handler;

use Amp\ByteStream\ClosedException;
use Amp\Socket\ClientSocket;
use Amp\Socket\ConnectException;
use Amp\Socket\ServerSocket;
use Generator;
use Mougrim\XdebugProxy\Config\IdeServer as IdeServerConfig;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\Xml\XmlContainer;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Mougrim\XdebugProxy\Xml\XmlDocument;
use Mougrim\XdebugProxy\Xml\XmlException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;
use function Amp\asyncCoroutine;
use function Amp\Socket\connect;
use function array_diff;
use function array_keys;
use function array_slice;
use function count;
use function explode;
use function get_class;
use function implode;
use function preg_match;
use function strlen;
use function strpos;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DefaultIdeHandler implements IdeHandler, CommandToXdebugParser
{
    /** @protected */
    const REGISTRATION_COMMAND_INIT = 'proxyinit';
    /** @protected */
    const REGISTRATION_COMMAND_STOP = 'proxystop';

    /** @protected */
    const REGISTRATION_ERROR_UNKNOWN_COMMAND = 1;
    /** @protected */
    const REGISTRATION_ERROR_ARGUMENT_FORMAT = 2;
    /** @protected */
    const REGISTRATION_ERROR_MISSING_REQUIRED_ARGUMENTS = 3;

    /** @protected */
    const REGISTRATION_ARGUMENTS = [
        self::REGISTRATION_COMMAND_INIT => [
            'supportedArguments' => ['-p', '-k'],
            'requiredArguments' => ['-p', '-k'],
        ],
        self::REGISTRATION_COMMAND_STOP => [
            'supportedArguments' => ['-k'],
            'requiredArguments' => ['-k'],
        ],
    ];

    protected $logger;
    protected $config;
    protected $xmlConverter;
    protected $requestPreparers;
    protected $defaultIde;
    protected $ideList = [];
    /**
     * @var ServerSocket[]|SplObjectStorage
     */
    protected $ideSockets;
    protected $maxIdeSockets = 100;

    /**
     * @param LoggerInterface $logger
     * @param IdeServerConfig $config
     * @param XmlConverter $xmlConverter
     * @param RequestPreparer[] $requestPreparers
     */
    public function __construct(
        LoggerInterface $logger,
        IdeServerConfig $config,
        XmlConverter $xmlConverter,
        array $requestPreparers
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->xmlConverter = $xmlConverter;
        $this->requestPreparers = $requestPreparers;
        $this->ideSockets = new SplObjectStorage();
        $this->defaultIde = $config->getDefaultIde();
        if ($this->defaultIde) {
            $this->logger->notice("Use default ide: {$this->defaultIde}");
        }
        $this->ideList = $config->getPredefinedIdeList();
        if ($this->ideList) {
            $this->logger->notice('Use predefined ides', ['predefinedIdeList' => $this->ideList]);
        }
    }

    public function getMaxIdeSockets(): int
    {
        return $this->maxIdeSockets;
    }

    /**
     * @param int $maxIdeSockets
     *
     * @return $this
     */
    public function setMaxIdeSockets(int $maxIdeSockets): DefaultIdeHandler
    {
        $this->maxIdeSockets = $maxIdeSockets;

        return $this;
    }

    public function getIdeList(): array
    {
        return $this->ideList;
    }

    /**
     * @param ServerSocket $socket
     *
     * @return Generator
     */
    public function handle(ServerSocket $socket): Generator
    {
        list($ip, $port) = explode(':', $socket->getRemoteAddress());
        $baseContext = [
            'ide' => "{$ip}:{$port}",
        ];
        $this->logger->notice('[IdeRegistration] Accepted connection.', $baseContext);

        $request = '';
        while (($data = yield $socket->read()) !== null) {
            $request .= $data;
            if (strpos($request, "\0") !== false) {
                break;
            }
        }
        $requests = explode("\0", $request);
        if ($request[strlen($request) - 1] !== "\0") {
            $this->logger->warning(
                "[IdeRegistration] Part of request isn't full, skip it",
                $baseContext + ['request' => $request]
            );
            $requests = array_slice($requests, 0, -1);
        }
        $responses = [];
        foreach ($requests as $request) {
            if (!$request) {
                continue;
            }
            $context = $baseContext;
            $context['request'] = $request;
            if (strpos($request, ' ') === false) {
                $this->logger->error('[IdeRegistration] Invalid request from IDE.', $context);
                continue;
            }
            $this->logger->notice('[IdeRegistration] Process request from IDE.', $context);

            list($command, $arguments) = $this->parseCommand($request);
            $context['command'] = $command;

            try {
                if (isset(static::REGISTRATION_ARGUMENTS[$command])) {
                    $unsupportedArguments = array_diff(
                        array_keys($arguments),
                        static::REGISTRATION_ARGUMENTS[$command]['supportedArguments']
                    );
                    if ($unsupportedArguments) {
                        $this->logger->warning(
                            '[IdeRegistration] Skip unsupported arguments.',
                            $context + ['unsupportedArguments' => $unsupportedArguments]
                        );
                    }
                    $missingRequiredArguments = array_diff(
                        static::REGISTRATION_ARGUMENTS[$command]['requiredArguments'],
                        array_keys($arguments)
                    );
                    if ($missingRequiredArguments) {
                        $this->logger->error(
                            '[IdeRegistration] Missing required arguments.',
                            $context + ['missingRequiredArguments' => $missingRequiredArguments]
                        );
                        throw new IdeRegistrationException(
                            static::REGISTRATION_ERROR_MISSING_REQUIRED_ARGUMENTS,
                            'Next required arguments are missing: '.implode(', ', $missingRequiredArguments),
                            $command
                        );
                    }
                }

                switch ($command) {
                    case 'proxyinit':
                        $context['key'] = $arguments['-k'];
                        if (!preg_match('/^[1-9]\d*$/', $arguments['-p'])) {
                            $this->logger->error(
                                '[IdeRegistration] Port should be a number.',
                                $context + ['port' => $arguments['-p']]
                            );
                            throw new IdeRegistrationException(
                                static::REGISTRATION_ERROR_ARGUMENT_FORMAT,
                                'Port should be a number',
                                $command
                            );
                        }
                        $newIde = "{$ip}:{$arguments['-p']}";
                        $context['ide'] = $newIde;
                        if (isset($this->ideList[$arguments['-k']])) {
                            $this->logger->notice(
                                "[IdeRegistration] Change ide from '{$this->ideList[$arguments['-k']]}' to '{$newIde}'".
                                $context
                            );
                        } else {
                            $this->logger->notice(
                                "[IdeRegistration] Add new ide '{$newIde}'",
                                $context
                            );
                        }
                        $this->ideList[$arguments['-k']] = $newIde;
                        $xmlContainer = (new XmlContainer('proxyinit'))
                            ->addAttribute('success', 1)
                            ->addAttribute('idekey', $arguments['-k'])
                            ->addAttribute('address', $ip)
                            ->addAttribute('port', $arguments['-p']);
                        break;
                    case 'proxystop':
                        if (isset($this->ideList[$arguments['-k']])) {
                            $this->logger->notice(
                                "[IdeRegistration] Remove ide key '{$arguments['-k']}' in '{$this->ideList[$arguments['-k']]}'",
                                $context
                            );
                            unset($this->ideList[$arguments['-k']]);
                        } else {
                            $this->logger->notice(
                                "[IdeRegistration] Ide key '{$arguments['-k']}' isn't used",
                                $context
                            );
                        }
                        $xmlContainer = (new XmlContainer('proxystop'))
                            ->addAttribute('success', 1)
                            ->addAttribute('idekey', $arguments['-k']);
                        break;
                    default:
                        $this->logger->error('[IdeRegistration] Unknown command from IDE.', $context);
                        throw new IdeRegistrationException(
                            static::REGISTRATION_ERROR_UNKNOWN_COMMAND,
                            "Unknown command '{$command}'"
                        );
                        break;
                }
            } catch (IdeRegistrationException $exception) {
                $xmlContainerMessage = (new XmlContainer('message'))
                    ->setContent($exception->getMessage());
                $xmlContainerError = (new XmlContainer('error'))
                    ->addAttribute('id', $exception->getErrorId())
                    ->addChild($xmlContainerMessage);
                $xmlContainer = (new XmlContainer($exception->getCommand()))
                    ->addAttribute('success', 0)
                    ->addChild($xmlContainerError);
            }
            $xmlDocument = (new XmlDocument('1.0', 'UTF-8'))
                ->setRoot($xmlContainer);
            try {
                $responses[] = $this->xmlConverter->generate($xmlDocument);
            } catch (XmlException $exception) {
                $this->logger->notice("[IdeRegistration] Can't generate response: {$exception}", $context);
                try {
                    yield $socket->end();
                } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $ignoreException) {
                    // we can't do anything else after try to close connection
                }

                return;
            }
        }
        $response = '';
        if ($responses) {
            $response = implode("\0", $responses);
        }
        $this->logger->notice(
            '[IdeRegistration] Send response.',
            $baseContext + ['response' => $response]
        );
        try {
            yield $socket->end($response);
        } catch (ClosedException $exception) {
            $this->logger->error(
                "[IdeRegistration] Can't write response to ide: {$exception}",
                $baseContext + ['response' => $response]
            );
        }
    }

    /**
     * @param XmlDocument $xmlRequest
     * @param string $rawRequest
     * @param ServerSocket $xdebugSocket
     *
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     *
     * @return Generator
     */
    public function processRequest(XmlDocument $xmlRequest, string $rawRequest, ServerSocket $xdebugSocket): Generator
    {
        $context = [
            'xdebug' => $xdebugSocket->getRemoteAddress(),
            'request' => $rawRequest,
        ];
        if (!$this->ideSockets->contains($xdebugSocket)) {
            yield from $this->processInit($xmlRequest, $rawRequest, $xdebugSocket);
        }
        /** @var ClientSocket $ideSocket */
        $ideSocket = $this->ideSockets->offsetGet($xdebugSocket);
        $context['ide'] = $ideSocket->getRemoteAddress();
        try {
            $this->prepareRequestToIde($xmlRequest, $rawRequest, $context);
        } catch (RequestPreparerError $error) {
            throw new FromXdebugProcessError("Can't prepare request to ide", $context, $error);
        }

        try {
            $request = $this->xmlConverter->generate($xmlRequest);
        } catch (XmlException $exception) {
            throw new FromXdebugProcessError("Can't generate response", $context, $exception);
        }
        try {
            yield $ideSocket->write(strlen($request)."\0{$request}\0");
        } catch (ClosedException $exception) {
            throw new FromXdebugProcessError(
                "Can't send request to ide",
                $context + ['generatedRequest' => $request],
                $exception
            );
        }

        $this->logger->debug('[Xdebug][Ide] Request was sent to ide, waiting response.', $context);
    }

    /**
     * @param XmlDocument $xmlRequest
     * @param string $rawRequest
     * @param array $context
     *
     * @throws RequestPreparerError
     *
     * @return void
     */
    protected function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest, array $context)
    {
        foreach ($this->requestPreparers as $requestPreparer) {
            try {
                $requestPreparer->prepareRequestToIde($xmlRequest, $rawRequest);
            } catch (RequestPreparerException $exception) {
                $this->logger->error(
                    "Can't prepare request to ide: {$exception}",
                    $context + [
                        'preparer' => get_class($requestPreparer),
                    ]
                );
            }
        }
    }

    public function close(ServerSocket $xdebugSocket): Generator
    {
        if (!$this->ideSockets->contains($xdebugSocket)) {
            return;
        }
        /** @var ClientSocket $ideSocket */
        $ideSocket = $this->ideSockets->offsetGet($xdebugSocket);

        try {
            yield $ideSocket->end();
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $ignore) {
            // already closed
        }
        $this->ideSockets->detach($xdebugSocket);
    }

    /**
     * @param XmlDocument $xmlRequest
     * @param string $rawRequest
     * @param ServerSocket $xdebugSocket
     *
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     *
     * @return Generator
     */
    protected function processInit(XmlDocument $xmlRequest, string $rawRequest, ServerSocket $xdebugSocket): Generator
    {
        $context = [
            'xdebug' => $xdebugSocket->getRemoteAddress(),
            'request' => $rawRequest,
        ];
        if (!$xmlRequest->getRoot()) {
            throw new FromXdebugProcessError("Can't get document root", $context);
        }

        if ($xmlRequest->getRoot()->getName() !== 'init') {
            throw new FromXdebugProcessError("First request's root should be init", $context);
        }

        $ideKey = $xmlRequest->getRoot()->getAttributes()['idekey'] ?? null;
        if (!$ideKey) {
            throw new FromXdebugProcessError('Ide key is empty', $context);
        }
        $context['ideKey'] = $ideKey;
        $ide = $this->ideList[$ideKey] ?? $this->defaultIde;
        if (!$ide) {
            throw new FromXdebugProcessException('No any ide', $context);
        }
        $context['ide'] = $ide;

        if ($this->ideSockets->count() >= $this->maxIdeSockets) {
            throw new FromXdebugProcessException('Max connections exceeded', $context);
        }

        $this->logger->notice('[Xdebug][Ide][Init] Try to init connect to ide.', $context);

        try {
            /** @var ClientSocket $ideSocket */
            $ideSocket = yield connect("tcp://{$ide}");
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ConnectException $exception) {
            throw new FromXdebugProcessError("Can't connect to ide", $context, $exception);
        }
        $this->ideSockets->attach($xdebugSocket, $ideSocket);

        $this->logger->notice('[Xdebug][Ide][Init] Successful connected to ide.', $context);

        $handleIde = asyncCoroutine([$this, 'handleIde']);
        $handleIde($ideKey, $xdebugSocket, $ideSocket);
    }

    public function handleIde(string $ideKey, ServerSocket $xdebugSocket, ClientSocket $ideSocket): Generator
    {
        $context = [
            'ide' => $ideSocket->getRemoteAddress(),
            'key' => $ideKey,
            'xdebug' => $xdebugSocket->getRemoteAddress(),
        ];
        $buffer = '';
        try {
            while (($chunk = yield $ideSocket->read()) !== null) {
                $buffer .= $chunk;
                while (strpos($buffer, "\0") !== false) {
                    list($request, $buffer) = explode("\0", $buffer, 2);
                    $this->logger->info(
                        '[Xdebug][Ide] Process ide request',
                        $context + ['request' => $request]
                    );
                    $request = $this->prepareRequestToXdebug($request, $context);
                    $this->logger->debug(
                        '[Xdebug][Ide] Send prepared request to xdebug',
                        $context + ['request' => $request]
                    );
                    $xdebugSocket->write($request."\0");
                }
            }
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $exception) {
            // skip exception, close other connections below
        } catch (RequestPreparerError $error) {
            $this->logger->critical(
                "Can't prepare request: {$error}",
                $context
            );
            // close other connections below
        }

        if ($buffer) {
            $this->logger->error(
                "[Xdebug][Ide] Buffer isn't empty after end handle from ide",
                $context + ['buffer' => $buffer]
            );
        }

        $this->logger->notice('[Xdebug][Ide] End handle from ide, close connections.', $context);
        $this->close($xdebugSocket);
        try {
            yield $xdebugSocket->end();
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $ignore) {
            // already closed
        }
    }

    /**
     * @param string $request
     * @param array $context
     *
     * @throws RequestPreparerError
     *
     * @return string prepared request
     */
    protected function prepareRequestToXdebug(string $request, array $context): string
    {
        foreach ($this->requestPreparers as $requestPreparer) {
            try {
                $request = $requestPreparer->prepareRequestToXdebug($request, $this);
            } catch (RequestPreparerException $exception) {
                $this->logger->error(
                    "Can't prepare request to xdebug: {$exception}",
                    $context + [
                        'preparer' => get_class($requestPreparer),
                        'request' => $request,
                    ]
                );
            }
        }

        return $request;
    }

    public function parseCommand(string $request): array
    {
        list($command, $arguments) = explode(' ', $request, 2);
        $arguments = $this->parseArguments($arguments);

        return [$command, $arguments];
    }

    public function buildCommand(string $command, array $arguments): string
    {
        $argumentStrings = [];
        foreach ($arguments as $argument => $value) {
            $argumentStrings[] = "{$argument} {$value}";
        }

        return $command.' '.implode(' ', $argumentStrings);
    }

    protected function parseArguments(string $arguments): array
    {
        $context = [
            'arguments' => $arguments,
        ];
        $parts = explode(' ', $arguments);
        $partsQty = count($parts);
        $result = [];
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $partsQty; $i++) {
            $part = $parts[$i];
            if (!$part) {
                continue;
            }
            if ($part[0] !== '-') {
                $this->logger->error("Can't parse argument {$part}", $context);
                continue;
            }
            if (!isset($parts[$i + 1])) {
                $this->logger->error("Can't get value argument {$part}", $context);
                break;
            }

            $i++;
            $result[$part] = $parts[$i];
        }

        return $result;
    }
}
