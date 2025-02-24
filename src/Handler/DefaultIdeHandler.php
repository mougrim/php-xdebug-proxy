<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;
use Amp\CancelledException;
use Amp\Socket\ConnectException;
use Amp\Socket\ResourceSocket;
use Amp\Socket\Socket;
use Mougrim\XdebugProxy\Config\IdeServer as IdeServerConfig;
use Mougrim\XdebugProxy\Enum\RegistrationError;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Mougrim\XdebugProxy\RequestPreparer\RequestPreparer;
use Mougrim\XdebugProxy\Xml\XmlContainer;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Mougrim\XdebugProxy\Xml\XmlDocument;
use Mougrim\XdebugProxy\Xml\XmlException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function Amp\async;
use function Amp\Socket\connect;
use function array_diff;
use function array_keys;
use function array_reverse;
use function array_slice;
use function count;
use function explode;
use function get_class;
use function implode;
use function preg_match;
use function strlen;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DefaultIdeHandler implements IdeHandler, CommandToXdebugParser
{
    protected readonly string $defaultIde;
    /** @var array<string, string> */
    protected array $ideList;
    /**
     * @var SplObjectStorage<ResourceSocket, Socket>
     */
    protected readonly SplObjectStorage $ideSockets;
    protected int $maxIdeSockets = 100;

    /**
     * @param RequestPreparer[] $requestPreparers
     */
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly IdeServerConfig $config,
        protected readonly XmlConverter $xmlConverter,
        protected readonly array $requestPreparers,
    ) {
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

    public function setMaxIdeSockets(int $maxIdeSockets): static
    {
        $this->maxIdeSockets = $maxIdeSockets;

        return $this;
    }

    public function getIdeList(): array
    {
        return $this->ideList;
    }

    public function handle(ResourceSocket $socket): void
    {
        [$ip, $port] = explode(':', $socket->getRemoteAddress()->toString());
        $baseContext = [
            'ide' => "{$ip}:{$port}",
        ];
        $this->logger->notice('[IdeRegistration] Accepted connection.', $baseContext);

        $request = '';
        while (($data = $socket->read()) !== null) {
            $request .= $data;
            if (str_contains($request, "\0")) {
                break;
            }
        }
        $requests = explode("\0", $request);
        if ($request && $request[strlen($request) - 1] !== "\0") {
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
            if (!str_contains($request, ' ')) {
                $this->logger->error('[IdeRegistration] Invalid request from IDE.', $context);
                continue;
            }
            $this->logger->notice('[IdeRegistration] Process request from IDE.', $context);

            [$command, $arguments] = $this->parseCommand($request);
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
                            RegistrationError::MissingRequiredArguments,
                            'Next required arguments are missing: '
                                . implode(', ', $missingRequiredArguments),
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
                                RegistrationError::ArgumentFormat,
                                'Port should be a number',
                                $command
                            );
                        }
                        $newIde = "{$ip}:{$arguments['-p']}";
                        $context['ide'] = $newIde;
                        if (isset($this->ideList[$arguments['-k']])) {
                            $this->logger->notice(
                                "[IdeRegistration] Change ide from '{$this->ideList[$arguments['-k']]}' to '{$newIde}'",
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
                            ->addAttribute('success', '1')
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
                            ->addAttribute('success', '1')
                            ->addAttribute('idekey', $arguments['-k']);
                        break;
                    default:
                        $this->logger->error('[IdeRegistration] Unknown command from IDE.', $context);
                        throw new IdeRegistrationException(
                            RegistrationError::UnknownCommand,
                            "Unknown command '{$command}'"
                        );
                }
            } catch (IdeRegistrationException $exception) {
                $xmlContainerMessage = (new XmlContainer('message'))
                    ->setContent($exception->getMessage());
                $xmlContainerError = (new XmlContainer('error'))
                    ->addAttribute('id', (string) $exception->getError()->value)
                    ->addChild($xmlContainerMessage);
                $xmlContainer = (new XmlContainer($exception->getCommand()))
                    ->addAttribute('success', '0')
                    ->addChild($xmlContainerError);
            }
            $xmlDocument = new XmlDocument('1.0', 'UTF-8', $xmlContainer);
            try {
                $responses[] = $this->xmlConverter->generate($xmlDocument);
            } catch (XmlException $exception) {
                $this->logger->notice("[IdeRegistration] Can't generate response: {$exception}", $context);
                try {
                    $socket->end();
                } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException|StreamException) {
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
            $socket->write($response);
            $socket->end();
        } catch (ClosedException|StreamException $exception) {
            $this->logger->error(
                "[IdeRegistration] Can't write response to ide: {$exception}",
                $baseContext + ['response' => $response]
            );
        }
    }

    /**
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     */
    public function processRequest(XmlDocument $xmlRequest, string $rawRequest, ResourceSocket $xdebugSocket): void
    {
        $context = [
            'xdebug' => $xdebugSocket->getRemoteAddress()->toString(),
            'request' => $rawRequest,
        ];
        if (!$this->ideSockets->contains($xdebugSocket)) {
            $this->processInit($xmlRequest, $rawRequest, $xdebugSocket);
        }
        $ideSocket = $this->ideSockets->offsetGet($xdebugSocket);
        $context['ide'] = $ideSocket->getRemoteAddress()->toString();
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
            $ideSocket->write(strlen($request) . "\0{$request}\0");
        } catch (ClosedException|StreamException $exception) {
            throw new FromXdebugProcessError(
                "Can't send request to ide",
                $context + ['generatedRequest' => $request],
                $exception
            );
        }

        $this->logger->debug('[Xdebug][Ide] Request was sent to ide, waiting response.', $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws RequestPreparerError
     */
    protected function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest, array $context): void
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

    public function close(ResourceSocket $xdebugSocket): void
    {
        if (!$this->ideSockets->contains($xdebugSocket)) {
            return;
        }
        $ideSocket = $this->ideSockets->offsetGet($xdebugSocket);

        try {
            $ideSocket->end();
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException) {
            // already closed
        } catch (StreamException $exception) {
            $this->logger->error(
                "Can't close ide socket: {$exception}",
                ['exception' => $exception],
            );
        }
        $this->ideSockets->detach($xdebugSocket);
    }

    /**
     * @throws FromXdebugProcessError
     * @throws FromXdebugProcessException
     */
    protected function processInit(XmlDocument $xmlRequest, string $rawRequest, ResourceSocket $xdebugSocket): void
    {
        $context = [
            'xdebug' => $xdebugSocket->getRemoteAddress()->toString(),
            'request' => $rawRequest,
        ];
        $xmlContainer = $xmlRequest->getRoot();
        if (!$xmlContainer) {
            throw new FromXdebugProcessError("Can't get document root", $context);
        }

        if ($xmlContainer->getName() !== 'init') {
            throw new FromXdebugProcessError("First request's root should be init", $context);
        }

        $ideKey = $xmlContainer->getAttributes()['idekey'] ?? null;
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
            $ideSocket = connect("tcp://{$ide}");
        } catch (ConnectException|CancelledException $exception) {
            throw new FromXdebugProcessError("Can't connect to ide", $context, $exception);
        }
        $this->ideSockets->attach($xdebugSocket, $ideSocket);

        $this->logger->notice('[Xdebug][Ide][Init] Successful connected to ide.', $context);

        async(fn () => $this->handleIde($ideKey, $xdebugSocket, $ideSocket));
    }

    protected function handleIde(string $ideKey, ResourceSocket $xdebugSocket, Socket $ideSocket): void
    {
        $context = [
            'ide' => $ideSocket->getRemoteAddress()->toString(),
            'key' => $ideKey,
            'xdebug' => $xdebugSocket->getRemoteAddress()->toString(),
        ];
        $buffer = '';
        try {
            while (($chunk = $ideSocket->read()) !== null) {
                $buffer .= $chunk;
                while (str_contains($buffer, "\0")) {
                    [$request, $buffer] = explode("\0", $buffer, 2);
                    $this->logger->info(
                        '[Xdebug][Ide] Process ide request',
                        $context + ['request' => $request]
                    );
                    $request = $this->prepareRequestToXdebug($request, $context);
                    $this->logger->debug(
                        '[Xdebug][Ide] Send prepared request to xdebug',
                        $context + ['request' => $request]
                    );
                    $xdebugSocket->write($request . "\0");
                }
            }
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $exception) {
            // skip exception, close other connections below
        } catch (StreamException|RequestPreparerError $error) {
            $this->logger->critical(
                "Can't prepare request",
                $context + [
                    'exception' => $error,
                ],
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
            $xdebugSocket->end();
        } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException) {
            // already closed
        } catch (StreamException $exception) {
            $this->logger->error(
                "Can't close xdebug socket",
                $context + ['exception' => $exception]
            );
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return string prepared request
     *
     * @throws RequestPreparerError
     */
    protected function prepareRequestToXdebug(string $request, array $context): string
    {
        foreach (array_reverse($this->requestPreparers) as $requestPreparer) {
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

    /**
     * @return array{string, array<string, string>}
     */
    public function parseCommand(string $request): array
    {
        [$command, $arguments] = explode(' ', $request, 2);
        $arguments = $this->parseArguments($arguments);

        return [$command, $arguments];
    }

    /**
     * @param array<string, string> $arguments
     */
    public function buildCommand(string $command, array $arguments): string
    {
        $argumentStrings = [];
        foreach ($arguments as $argument => $value) {
            $argumentStrings[] = "{$argument} {$value}";
        }

        return $command . ' ' . implode(' ', $argumentStrings);
    }

    /**
     * @return array<string, string>
     */
    protected function parseArguments(string $arguments): array
    {
        $context = [
            'arguments' => $arguments,
        ];
        $parts = explode(' ', $arguments);
        $partsQty = count($parts);
        /** @var array<string, string> $result */
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
