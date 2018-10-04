## PHP xdebug (dbgp) proxy upgrade instructions

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is, if you want to upgrade from version A to version C and there is version B between A and C, you need to follow the instructions for both A and B.

### Upgrade from v0.0.1

- class `\Mougrim\XdebugProxy\RequestPreparer` is deprecated and will be removed in next releases, use `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` instead of it
- `\Mougrim\XdebugProxy\Handler\CommandToXdebugParser::buildCommand()` method was added, implement this method if you implement it
