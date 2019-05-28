<?php

namespace Mougrim\XdebugProxy\RequestPreparer;

// Badoo\SoftMocks is optional for xdebug-proxy
use /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUndefinedNamespaceInspection */
    Badoo\SoftMocks;
use Mougrim\XdebugProxy\Handler\CommandToXdebugParser;
use Mougrim\XdebugProxy\Xml\XmlDocument;
use Psr\Log\LoggerInterface;
use Throwable;
use function file_exists;
use function in_array;
use function is_file;
use function parse_url;
use function rawurldecode;
use function realpath;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class SoftMocksRequestPreparer implements RequestPreparer
{
    protected $logger;

    /**
     * @param LoggerInterface $logger
     * @param string $initScript
     *
     * @throws Error
     */
    public function __construct(LoggerInterface $logger, string $initScript = null)
    {
        if ($initScript === null) {
            $initScript = '';
        }
        $this->logger = $logger;
        if (!$initScript) {
            $possibleInitScriptPaths = [
                __DIR__.'/../../vendor/badoo/soft-mocks/src/init_with_composer.php',
                __DIR__.'/../../../../badoo/soft-mocks/src/init_with_composer.php',
            ];
            foreach ($possibleInitScriptPaths as $possiblInitScriptPath) {
                if (file_exists($possiblInitScriptPath)) {
                    $initScript = $possiblInitScriptPath;

                    break;
                }
            }
        }

        if (!$initScript) {
            throw new Error("Can't find soft-mocks init script");
        }
        /** @noinspection PhpIncludeInspection */
        require $initScript;
    }

    public function prepareRequestToIde(XmlDocument $xmlRequest, string $rawRequest): void
    {
        $context = [
            'request' => $rawRequest,
        ];
        $root = $xmlRequest->getRoot();
        if (!$root) {
            return;
        }
        foreach ($root->getChildren() as $child) {
            if (!in_array($child->getName(), ['stack', 'xdebug:message'], true)) {
                continue;
            }
            $attributes = $child->getAttributes();
            if (isset($attributes['filename'])) {
                $filename = $this->getOriginalFilePath($attributes['filename'], $context);
                if ($attributes['filename'] !== $filename) {
                    $this->logger->info("Change '{$attributes['filename']}' to '{$filename}'", $context);
                    $child->addAttribute('filename', $filename);
                }
            }
        }
    }

    protected function getOriginalFilePath(string $file, array $context): string
    {
        // workaround some symbols like '+' are encoded like %2B
        $file = rawurldecode($file);
        $parts = parse_url($file);
        if ($parts === false) {
            $this->logger->warning("Can't parse file '{$file}'", $context);

            return $file;
        }
        if ($parts['scheme'] !== 'file') {
            $this->logger->warning("Scheme isn't file '{$file}'", $context);

            return $file;
        }

        try {
            /** @noinspection PhpUndefinedClassInspection */
            return 'file://'.SoftMocks::getOriginalFilePath($parts['path']);
        } catch (Throwable $throwable) {
            $this->logger->warning("Can't get original file path: {$throwable}", $context);

            return $file;
        }
    }

    public function prepareRequestToXdebug(string $request, CommandToXdebugParser $commandToXdebugParser): string
    {
        [$command, $arguments] = $commandToXdebugParser->parseCommand($request);
        $context = [
            'request' => $request,
            'arguments' => $arguments,
        ];
        if ($command === 'breakpoint_set') {
            if (isset($arguments['-f'])) {
                $file = $this->getRewrittenFilePath($arguments['-f'], $context);
                if ($file) {
                    $this->logger->info("Change '{$arguments['-f']}' to '{$file}'", $context);
                    $arguments['-f'] = $file;
                    $request = $commandToXdebugParser->buildCommand($command, $arguments);
                }
            } else {
                $this->logger->error("Command {$command} is without argument '-f'", $context);
            }
        }

        return $request;
    }

    protected function getRewrittenFilePath(string $file, array $context): string
    {
        $originalFile = $file;
        $parts = parse_url($file);
        if ($parts === false) {
            $this->logger->warning("Can't parse file '{$file}'", $context);

            return '';
        }
        if ($parts['scheme'] !== 'file') {
            $this->logger->warning("Scheme isn't file '{$file}'", $context);

            return '';
        }
        try {
            /** @noinspection PhpUndefinedClassInspection */
            $rewrittenFile = (string) SoftMocks::getRewrittenFilePath($parts['path']);
        } catch (Throwable $throwable) {
            $this->logger->warning("Can't get rewritten file path: {$throwable}", $context);

            return '';
        }
        if (!$rewrittenFile) {
            return '';
        }
        if (is_file($rewrittenFile)) {
            $file = realpath($rewrittenFile);
            if (!$file) {
                $this->logger->error("Can't get real path for {$rewrittenFile}", $context);
            }
        } else {
            $this->logger->debug("Rewritten file '{$rewrittenFile}' isn't exists for '{$originalFile}'", $context);
            $file = $rewrittenFile;
        }
        if (!$file) {
            return '';
        }

        return 'file://'.$file;
    }
}
