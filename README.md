## PHP xdebug (dbgp) proxy

This is [dbgp](https://xdebug.org/docs-dbgp.php) xdebug proxy.

The idea is described in document [Multi-user debugging in PhpStorm with Xdebug and DBGp proxy](https://confluence.jetbrains.com/display/PhpStorm/Multi-user+debugging+in+PhpStorm+with+Xdebug+and+DBGp+proxy#Multi-userdebugginginPhpStormwithXdebugandDBGpproxy-HowdoesXdebugwork).

The benefits are that this proxy is written in php - language, which you know.

[![Latest Stable Version](https://poser.pugx.org/mougrim/php-xdebug-proxy/version)](https://packagist.org/packages/mougrim/php-xdebug-proxy)
[![Latest Unstable Version](https://poser.pugx.org/mougrim/php-xdebug-proxy/v/unstable)](https://packagist.org/packages/mougrim/php-xdebug-proxy)
[![License](https://poser.pugx.org/mougrim/php-xdebug-proxy/license)](https://packagist.org/packages/mougrim/php-xdebug-proxy)
[![Build Status](https://api.travis-ci.org/mougrim/php-xdebug-proxy.png?branch=master)](https://travis-ci.org/mougrim/php-xdebug-proxy)

### Installation

This package can be installed as a [Composer](https://getcomposer.org/) project:

```bash
composer.phar create-project mougrim/php-xdebug-proxy
```

Or dependency:

```bash
composer.phar require mougrim/php-xdebug-proxy --dev
```

For parse XML you should install `ext-dom`.

For write logs by default you should install `amphp/log` (use `--dev` if you installed `php-xdebug-proxy` as dependency):

```bash
composer.phar require amphp/log '^1.0.0'
```


### Run

You can run next command:
```bash
bin/xdebug-proxy
```

The proxy will be run with default config:
```text
Using config path /path/to/php-xdebug-proxy/config
[2018-06-21 09:31:51] xdebug-proxy.NOTICE: [Proxy][IdeRegistration] Listening for new connections on '127.0.0.1:9001'... array ( ) array ( )
[2018-06-21 09:31:51] xdebug-proxy.NOTICE: [Proxy][Xdebug] Listening for new connections on '127.0.0.1:9002'... array ( ) array ( )
```

So by default proxy listens '127.0.0.1:9001' for ide registration connections and '127.0.0.1:9002' for xdebug connections.

### Config

If you want customize logger, config you factory, you can use custom config path. You just copy `config` directory to you custom path:

```bash
cp -r /path/to/php-xdebug-proxy/config /your/custom/path
```

There are 3 files:

- `config.php`:
    ```php
    <?php
    return [
        'xdebugServer' => [
            // xdebug proxy server host:port
            'listen' => '127.0.0.1:9002',
        ],
        'ideServer' => [
            // if proxy can't find ide, then it uses default ide,
            // pass empty string if you want to disable default ide
            // defaultIde is useful when there is only one user for proxy
            'defaultIde' => '127.0.0.1:9000',
            // predefined ide list in format 'idekey' => 'host:port',
            // pass empty array if you don't need predefined ide list
            // predefinedIdeList is useful when proxy's users aren't changed often,
            // so they don't need to register in proxy each proxy restart
            'predefinedIdeList' => [
                'idekey' => '127.0.0.1:9000',
            ],
        ],
        'ideRegistrationServer' => [
           // host:port for register ide in proxy
            'listen' => '127.0.0.1:9001',
        ],
    ];
    ```
- `logger.php`: you can customize logger, file should return object, which is instanceof `\Psr\Log\LoggerInterface`;
- `factory.php`: you can customize classes, which is used in proxy, file should return object, which is instanceof `\Mougrim\XdebugProxy\Factory\Factory`.

Then change configs and run:

```bash
bin/xdebug-proxy --configs=/your/custom/path/config
```

### Using with soft-mocks

See doc in [soft-mocks](https://github.com/badoo/soft-mocks/#using-with-xdebug) project.

### Thanks

Many thanks to [Eelf](https://github.com/eelf) for proxy example [smdbgpproxy](https://github.com/eelf/smdbgpproxy).
