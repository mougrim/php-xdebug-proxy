<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Handler;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;
use Amp\Socket\ResourceSocket;
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
    /**
     * @var SplObjectStorage<ResourceSocket, string>
     */
    protected SplObjectStorage $requestBuffers;

    public function __construct(
        protected LoggerInterface $logger,
        protected XmlConverter $xmlConverter,
        protected IdeHandler $ideHandler,
    ) {
        $this->requestBuffers = new SplObjectStorage();
    }

    public function handle(ResourceSocket $socket): void
    {
        $baseContext = [
            'xdebug' => $socket->getRemoteAddress()->toString(),
        ];
        $this->logger->notice('[Xdebug] Accepted connection', $baseContext);

        if (!$this->requestBuffers->contains($socket)) {
            $this->requestBuffers->attach($socket, '');
        }
        while (($data = $socket->read()) !== null) {
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
                    $this->ideHandler->processRequest($xmlRequest, $request, $socket);
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
                    $this->ideHandler->close($socket);
                    $this->requestBuffers->detach($socket);
                    try {
                        $socket->end();
                    } /** @noinspection BadExceptionsProcessingInspection */ catch (ClosedException) {
                        // already closed
                    } catch (StreamException $exception) {
                        $this->logger->error("Can't close socket", $context + ['exception' => $exception]);
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
        $this->ideHandler->close($socket);
    }
}
