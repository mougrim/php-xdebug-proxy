<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\ByteStream\ClosedException;
use Amp\Socket\ServerSocket;
use Generator;
use Mougrim\XdebugProxy\Xml\XmlConverter;
use Mougrim\XdebugProxy\Xml\XmlException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;
use function explode;
use function mb_strlen;
use function substr_count;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DefaultXdebugHandler implements XdebugHandler
{
    protected LoggerInterface $logger;
    protected XmlConverter $xmlConverter;
    protected IdeHandler $ideHandler;
    /**
     * @var SplObjectStorage<ServerSocket, string>
     */
    protected SplObjectStorage $requestBuffers;

    public function __construct(LoggerInterface $logger, XmlConverter $xmlConverter, IdeHandler $ideHandler)
    {
        $this->logger = $logger;
        $this->xmlConverter = $xmlConverter;
        $this->ideHandler = $ideHandler;
        $this->requestBuffers = new SplObjectStorage();
    }

    public function handle(ServerSocket $socket): Generator
    {
        $baseContext = [
            'xdebug' => $socket->getRemoteAddress(),
        ];
        $this->logger->notice('[Xdebug] Accepted connection', $baseContext);

        if (!$this->requestBuffers->contains($socket)) {
            $this->requestBuffers->attach($socket, '');
        }
        while (($data = yield $socket->read()) !== null) {
            $buffer = $this->requestBuffers->offsetGet($socket);
            $buffer .= $data;
            while (substr_count($buffer, "\0") >= 2) {
                $exception = null;
                try {
                    [$length, $request, $buffer] = explode("\0", $buffer, 3);
                    $context = $baseContext + ['request' => $request, 'requestLength' => $length];
                    $this->logger->info('[Xdebug] Process request', $context);

                    $requestLength = mb_strlen($request, '8bit');
                    if ((string) $requestLength !== $length) {
                        throw new FromXdebugProcessError(
                            'Wrong request length',
                            $context + ['actualRequestLength' => $length]
                        );
                    }

                    try {
                        $xmlRequest = $this->xmlConverter->parse($request);
                    } catch (XmlException $exception) {
                        throw new FromXdebugProcessError("Can't parse request", $context, $exception);
                    }
                    yield from $this->ideHandler->processRequest($xmlRequest, $request, $socket);
                } catch (FromXdebugProcessError $exception) {
                    $message = "[Xdebug] {$exception->getMessage()}";
                    if ($exception->getPrevious()) {
                        $message .= ": {$exception->getPrevious()}";
                    }
                    $this->logger->error($message, $exception->getContext());
                } catch (FromXdebugProcessException $exception) {
                    $message = "[Xdebug] {$exception->getMessage()}";
                    if ($exception->getPrevious()) {
                        $message .= ": {$exception->getPrevious()}";
                    }
                    $this->logger->notice($message, $exception->getContext());
                }
                if ($exception) {
                    yield from $this->ideHandler->close($socket);
                    $this->requestBuffers->detach($socket);
                    try {
                        yield $socket->end();
                    } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException $ignore) {
                        // already closed
                    }

                    return;
                }
            }
            $this->requestBuffers->attach($socket, $buffer);
        }
        $buffer = $this->requestBuffers->offsetGet($socket);
        if ($buffer) {
            $this->logger->warning("[Xdebug] Part of data wasn't parsed", $baseContext + ['buffer']);
        }
        $this->requestBuffers->detach($socket);
        yield from $this->ideHandler->close($socket);
    }
}
