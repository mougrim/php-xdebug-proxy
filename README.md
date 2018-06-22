## PHP xdebug (dbgp) proxy

This is [dbgp](https://xdebug.org/docs-dbgp.php) xdebug proxy.

[![Latest Stable Version](https://poser.pugx.org/mougrim/php-xdebug-proxy/version)](https://packagist.org/packages/mougrim/php-xdebug-proxy)
[![Latest Unstable Version](https://poser.pugx.org/mougrim/php-xdebug-proxy/v/unstable)](https://packagist.org/packages/mougrim/php-xdebug-proxy)
[![License](https://poser.pugx.org/mougrim/php-xdebug-proxy/license)](https://packagist.org/packages/mougrim/php-xdebug-proxy)

## Installation

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


## Run

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

## Config

If you want customize logger, config you factory, you can use custom config path. You just copy `config` directory to you custom path:

```bash
cp -r /path/to/php-xdebug-proxy/config /your/custom/path
```

There are 3 files:

- `config.php`: you can customize listen ip and port;
- `logger.php`: you can customize logger, file should return object, which is instanceof `\Psr\Log\LoggerInterface`;
- `factory.php`: you can customize classes, which is used in proxy, file should return object, which is instanceof `\Mougrim\XdebugProxy\Factory\Factory`.

Then change configs and run:

```bash
bin/xdebug-proxy --configs=/your/custom/path/config
```

## Thanks

Many thanks to [Eelf](https://github.com/eelf) for proxy example [smdbgpproxy](https://github.com/eelf/smdbgpproxy).
