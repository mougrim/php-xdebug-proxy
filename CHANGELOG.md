## PHP xdebug (dbgp) proxy ChangeLog

## master

There are next changes:
- defaultIde config param was added
- predefinedIdeList config param was added
- now by default debug and info logs are disabled
- log levels for some logs are changed
- [README.md](README.md) was updated
- doc for [`RequestPreparer\RequestPreparer`](src/RequestPreparer/RequestPreparer.php) was added

### v0.1.0

There are next changes:

- [soft-mocks](https://github.com/badoo/soft-mocks/#using-with-xdebug) support was added
- `\Mougrim\XdebugProxy\Handler\CommandToXdebugParser::buildCommand()` method was added
- now `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` is used instead of `\Mougrim\XdebugProxy\RequestPreparer`
- `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` methods can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem
- now `\Mougrim\XdebugProxy\Factory\Factory::createRequestPreparers()` accepts `$logger` param and also can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem
