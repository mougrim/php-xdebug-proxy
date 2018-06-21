<?php

namespace Mougrim\XdebugProxy;

use Mougrim\XdebugProxy\Config\Config;
use Mougrim\XdebugProxy\Factory\Factory;
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
            $configsPath = $this->getConfigsPath($options);

            $logger = $this->getLogger($configsPath);

            $factory = $this->getFactory($configsPath);
            $config = $this->getConfig($configsPath, $factory);
            $xmlConverter = $factory->createXmlConverter($logger);
            $ideHandler = $factory->createIdeHandler($logger, $xmlConverter, $factory->createRequestPreparers());
            $xdebugHandler = $factory->createXdebugHandler($logger, $xmlConverter, $ideHandler);
            $factory->createProxy($logger, $config, $xmlConverter, $ideHandler, $xdebugHandler)
                ->run();
        } catch (RunError $error) {
            $this->errorFallback($error->getMessage());
            $this->end($error->getCode() ?: 1);
        }
    }

    protected function getOptions(): array
    {
        $shortToLongOptions = [
            'c' => 'configs',
        ];
        $rawOptions = getopt('c:', ['configs:']);
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
