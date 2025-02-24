<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy;

use JetBrains\PhpStorm\NoReturn;
use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Factory\Factory;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop\UnsupportedFeatureException;

use const PHP_EOL;
use const STDERR;
use const STDOUT;

use function fwrite;
use function getopt;
use function is_array;
use function is_dir;
use function is_file;
use function is_readable;
use function realpath;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type XdebugProxyConfigArray from Config
 */
class Runner
{
    public function run(): void
    {
        try {
            $options = $this->getOptions();

            if (isset($options['help'])) {
                $this->showHelp();
            }

            $configsPath = $this->getConfigsPath($options);

            $logger = $this->getLogger($configsPath);

            $factory = $this->getFactory($configsPath);
            $config = $this->getConfig($configsPath, $factory);
            $xmlConverter = $factory->createXmlConverter($logger);
            $requestPreparers = [];
            try {
                $requestPreparers = $factory->createRequestPreparers($logger, $config);
            } catch (RequestPreparerException $exception) {
                $logger->warning("Can't create request preparers: {$exception}");
            } catch (RequestPreparerError $exception) {
                $logger->critical("Can't create request preparers: {$exception}");
                $this->end(1);
            }
            $ideHandler = $factory->createIdeHandler(
                $logger,
                $config->getIdeServer(),
                $xmlConverter,
                $requestPreparers
            );
            $xdebugHandler = $factory->createXdebugHandler($logger, $xmlConverter, $ideHandler);
            $factory->createProxy($logger, $config, $xmlConverter, $ideHandler, $xdebugHandler)
                ->run();
        } catch (RunError|UnsupportedFeatureException $error) {
            $this->errorFallback('');
            $this->errorFallback('There is error:');
            $this->errorFallback($error->__toString());
            $this->errorFallback('');
            $this->showHelp($error->getCode() ?: 1);
        }
    }

    #[NoReturn]
    protected function showHelp(?int $exitCode = null): void
    {
        if ($exitCode === null) {
            $exitCode = 0;
        }
        $this->infoFallback('Usage:');
        $this->infoFallback("  {$this->getScriptName()} [options]");
        $this->infoFallback('');
        $this->infoFallback('Mandatory arguments to long options are mandatory for short options too.');
        $this->infoFallback('Options:');
        $this->infoFallback('  -h, --help         This help.');
        $this->infoFallback('  -c, --configs=PATH Path to directory with configs:');
        $this->infoFallback('                      - config.php: you can customize listen ip and port;');
        $this->infoFallback('                      - logger.php: you can customize logger, file should return object, which is instanceof \Psr\Log\LoggerInterface;');
        $this->infoFallback('                      - factory.php: you can customize classes, which is used in proxy, file should return object, which is instanceof \Mougrim\XdebugProxy\Factory\Factory.');
        $this->infoFallback('');
        $this->infoFallback('Documentation: <https://github.com/mougrim/php-xdebug-proxy/blob/master/README.md#readme>.');
        $this->infoFallback('');
        $this->end($exitCode);
    }

    /**
     * @return array{configs?: string, help?: false}
     */
    protected function getOptions(): array
    {
        $shortToLongOptions = [
            'c' => 'configs',
            'h' => 'help',
        ];
        /** @var array<array-key, string|false> $rawOptions */
        $rawOptions = getopt('c:h', ['configs:', 'help']);
        $result = [];
        foreach ($shortToLongOptions as $shortOption => $longOption) {
            if (isset($rawOptions[$shortOption])) {
                $result[$longOption] = $rawOptions[$shortOption];
            } elseif (isset($rawOptions[$longOption])) {
                $result[$longOption] = $rawOptions[$longOption];
            }
        }

        /** @phpstan-ignore return.type */
        return $result;
    }

    /**
     * @param array{configs?: string} $options
     */
    protected function getConfigsPath(array $options): string
    {
        $configPath = $options['configs'] ?? __DIR__ . '/../config';
        $realConfigPath = realpath($configPath);
        if (!$realConfigPath || !is_dir($realConfigPath)) {
            throw new RunError("Wrong config path {$configPath}", 1);
        }
        $this->infoFallback("Using config path {$realConfigPath}");

        return $realConfigPath;
    }

    protected function getConfig(string $configsPath, Factory $factory): Config
    {
        $configPath = $configsPath . '/config.php';
        $config = $this->requireConfig($configPath);
        if (!is_array($config)) {
            throw new RunError("Config '{$configPath}' should return array.");
        }
        /** @var XdebugProxyConfigArray $config */

        return $factory->createConfig($config);
    }

    protected function getFactory(string $configsPath): Factory
    {
        $factoryConfigPath = $configsPath . '/factory.php';
        $factory = $this->requireConfig($factoryConfigPath);
        if (!$factory instanceof Factory) {
            throw new RunError("Factory config '{$factoryConfigPath}' should return Factory object.");
        }

        return $factory;
    }

    protected function getLogger(string $configsPath): LoggerInterface
    {
        $loggerConfigPath = $configsPath . '/logger.php';
        $logger = $this->requireConfig($loggerConfigPath);
        if (!$logger instanceof LoggerInterface) {
            throw new RunError("Logger config '{$loggerConfigPath}' should return LoggerInterface object.");
        }

        return $logger;
    }

    /**
     * @throws RunError
     */
    protected function requireConfig(string $path): mixed
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RunError("Wrong config path {$path}.");
        }
        return require $path;
    }

    public function getScriptName(): string
    {
        /** @var array<int, string> $argv */
        $argv = $_SERVER['argv'];

        return $argv[0] ?? 'xdebug-proxy';
    }

    #[NoReturn]
    protected function end(int $exitCode): void
    {
        exit($exitCode);
    }

    protected function errorFallback(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }

    protected function infoFallback(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}
