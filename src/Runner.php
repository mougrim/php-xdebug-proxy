<?php

namespace Mougrim\XdebugProxy;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Factory\Factory;
use Mougrim\XdebugProxy\RequestPreparer\Error as RequestPreparerError;
use Mougrim\XdebugProxy\RequestPreparer\Exception as RequestPreparerException;
use Psr\Log\LoggerInterface;
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
 */
class Runner
{
    public function run()
    {
        try {
            $options = $this->getOptions();

            if (isset($options['help'])) {
                $this->showHelp();

                return;
            }

            $configsPath = $this->getConfigsPath($options);

            $logger = $this->getLogger($configsPath);

            $factory = $this->getFactory($configsPath);
            $config = $this->getConfig($configsPath, $factory);
            $xmlConverter = $factory->createXmlConverter($logger);
            $requestPreparers = [];
            try {
                $requestPreparers = $factory->createRequestPreparers($logger);
            } catch (RequestPreparerException $exception) {
                $logger->warning("Can't create request preparers: {$exception}");
            } catch (RequestPreparerError $exception) {
                $logger->critical("Can't create request preparers: {$exception}");
                $this->end(1);

                return;
            }
            $ideHandler = $factory->createIdeHandler($logger, $config->getIdeServer(), $xmlConverter, $requestPreparers);
            $xdebugHandler = $factory->createXdebugHandler($logger, $xmlConverter, $ideHandler);
            $factory->createProxy($logger, $config, $xmlConverter, $ideHandler, $xdebugHandler)
                ->run();
        } catch (RunError $error) {
            $this->errorFallback('');
            $this->errorFallback('There is error:');
            $this->errorFallback($error->getMessage());
            $this->errorFallback('');
            $this->showHelp($error->getCode() ?: 1);
        }
    }

    protected function showHelp($exitCode = 0)
    {
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

    protected function getOptions(): array
    {
        $shortToLongOptions = [
            'c' => 'configs',
            'h' => 'help',
        ];
        $rawOptions = getopt('c:h', ['configs:', 'help']);
        $result = [];
        foreach ($shortToLongOptions as $shortOption => $longOption) {
            if (isset($rawOptions[$shortOption])) {
                $result[$longOption] = $rawOptions[$shortOption];
            } elseif (isset($rawOptions[$longOption])) {
                $result[$longOption] = $rawOptions[$longOption];
            }
        }

        return $result;
    }

    protected function getConfigsPath(array $options): string
    {
        $configPath = $options['configs'] ?? __DIR__.'/../config';
        $realConfigPath = realpath($configPath);
        if (!$realConfigPath || !is_dir($realConfigPath)) {
            throw new RunError("Wrong config path {$configPath}", 1);
        }
        $this->infoFallback("Using config path {$realConfigPath}");

        return $realConfigPath;
    }

    protected function getConfig(string $configsPath, Factory $factory): Config
    {
        $configPath = $configsPath.'/config.php';
        /** @var array $config */
        $config = $this->requireConfig($configPath);
        if (!is_array($config)) {
            throw new RunError("Config '{$configPath}' should return array.");
        }

        return $factory->createConfig($config);
    }

    protected function getFactory(string $configsPath): Factory
    {
        $factoryConfigPath = $configsPath.'/factory.php';
        /** @var Factory $factory */
        $factory = $this->requireConfig($factoryConfigPath);
        if (!$factory instanceof Factory) {
            throw new RunError("Factory config '{$factoryConfigPath}' should return Factory object.");
        }

        return $factory;
    }

    protected function getLogger(string $configsPath): LoggerInterface
    {
        $loggerConfigPath = $configsPath.'/logger.php';
        /** @var LoggerInterface $logger */
        $logger = $this->requireConfig($loggerConfigPath);
        if (!$logger instanceof LoggerInterface) {
            throw new RunError("Logger config '{$loggerConfigPath}' should return LoggerInterface object.");
        }

        return $logger;
    }

    protected function requireConfig(string $path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RunError("Wrong config path {$path}.");
        }
        /** @noinspection PhpIncludeInspection */
        return require $path;
    }

    public function getScriptName(): string
    {
        return $_SERVER['argv'][0] ?? 'xdebug-proxy';
    }

    protected function end(int $exitCode)
    {
        exit($exitCode);
    }

    protected function errorFallback(string $message)
    {
        fwrite(STDERR, $message.PHP_EOL);
    }

    protected function infoFallback(string $message)
    {
        fwrite(STDOUT, $message.PHP_EOL);
    }
}
