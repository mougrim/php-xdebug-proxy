## PHP xdebug (dbgp) proxy ChangeLog

### master

There are next changes:

- there were code style fixes [#14](https://github.com/mougrim/php-xdebug-proxy/pull/14)
- more info about default IDE in config was added to README.md [#15](https://github.com/mougrim/php-xdebug-proxy/pull/15)
- now request preparers are called on request to xdebug from last to first [#16](https://github.com/mougrim/php-xdebug-proxy/pull/16)
- minimum php version now is 7.1 [#18](https://github.com/mougrim/php-xdebug-proxy/pull/18)
- constants visibility was added [#18](https://github.com/mougrim/php-xdebug-proxy/pull/18)
- deprecated interface \Mougrim\XdebugProxy\RequestPreparer was removed [#18](https://github.com/mougrim/php-xdebug-proxy/pull/18)

### v0.2.1

There are next changes:

- possibility to disable IDE registration was added [#12](https://github.com/mougrim/php-xdebug-proxy/pull/12)

### v0.2.0

There are next changes:

- defaultIde config param was added [#8](https://github.com/mougrim/php-xdebug-proxy/pull/8)
- predefinedIdeList config param was added [#8](https://github.com/mougrim/php-xdebug-proxy/pull/8)
- now by default debug and info logs are disabled [#9](https://github.com/mougrim/php-xdebug-proxy/pull/9)
- log levels for some logs are changed [#9](https://github.com/mougrim/php-xdebug-proxy/pull/9)
- [README.md](README.md) was updated [#11](https://github.com/mougrim/php-xdebug-proxy/pull/11)
- doc for [`RequestPreparer\RequestPreparer`](src/RequestPreparer/RequestPreparer.php) was added [#11](https://github.com/mougrim/php-xdebug-proxy/pull/11)

### v0.1.0

There are next changes:

- [soft-mocks](https://github.com/badoo/soft-mocks/#using-with-xdebug) support was added [#7](https://github.com/mougrim/php-xdebug-proxy/pull/7)
- `\Mougrim\XdebugProxy\Handler\CommandToXdebugParser::buildCommand()` method was added [#7](https://github.com/mougrim/php-xdebug-proxy/pull/7)
- now `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` is used instead of `\Mougrim\XdebugProxy\RequestPreparer` [#7](https://github.com/mougrim/php-xdebug-proxy/pull/7)
- `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` methods can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem [#7](https://github.com/mougrim/php-xdebug-proxy/pull/7)
- now `\Mougrim\XdebugProxy\Factory\Factory::createRequestPreparers()` accepts `$logger` param and also can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem [#7](https://github.com/mougrim/php-xdebug-proxy/pull/7)
