## PHP xdebug (dbgp) proxy upgrade instructions

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is, if you want to upgrade from version A to version C and there is version B between A and C, you need to follow the instructions for both A and B.

### Upgrade from v0.3.0
- new parameter `$config` was added to `\Mougrim\XdebugProxy\Factory\Factory::createRequestPreparers()`, if you implement `\Mougrim\XdebugProxy\Factory\Factory` interface, then implement this parameter too
- now `\Mougrim\XdebugProxy\Factory\SoftMocksFactory::createConfig()` should return `\Mougrim\XdebugProxy\Config\SoftMocksConfig`, so if you redeclared this method, then return `\Mougrim\XdebugProxy\Config\SoftMocksConfig` or inheritor in it

### Upgrade from v0.2.1
- use interface `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` instead of `\Mougrim\XdebugProxy\RequestPreparer`
- check your php version, now php 7.1 is minimum php version
- check interface compatible for:
  - `\Mougrim\XdebugProxy\Handler\DefaultIdeHandler`
  - `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer`
  - `\Mougrim\XdebugProxy\Xml\XmlDocument`
  - `\Mougrim\XdebugProxy\Proxy`
  - `\Mougrim\XdebugProxy\Runner`

### Upgrade from v0.1.0
- New parameter `$config` was added to `\Mougrim\XdebugProxy\Factory\Factory::createIdeHandler()`, if you implement `\Mougrim\XdebugProxy\Factory\Factory` interface, then implement this parameter too.
- New parameter `$config` was added to `\Mougrim\XdebugProxy\Handler\DefaultIdeHandler`'s constructor, if you extends this class' constructor, then implement this parameter.
- Now by default `defaultIde` is `'127.0.0.1:9000'` and `predefinedIdeList` is `'idekey' => '127.0.0.1:9000'`. Pass in `config.php` `ideServer` with `'defaultIde' => ''` and `'predefinedIdeList' => []` if you don't need default ide and predefined ide list. See [README.md](README.md#config) for more details about `config.php`.

### Upgrade from v0.0.1

- class `\Mougrim\XdebugProxy\RequestPreparer` is deprecated and will be removed in next releases, use `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` instead of it
- `\Mougrim\XdebugProxy\Handler\CommandToXdebugParser::buildCommand()` method was added, implement this method if you implement it
