## PHP xdebug (dbgp) proxy ChangeLog

### [Unreleased]

There are next changes:

### [v0.6.0]

There are next changes:
- Update minimum php version from 7.4 to 8.1 ([#44](https://github.com/mougrim/php-xdebug-proxy/pull/44))
- Remove psalm, now phpstan is used ([#44](https://github.com/mougrim/php-xdebug-proxy/pull/44))

### [v0.5.1]

There are next changes:

- Bump actions/download-artifact from 2 to 4.1.7 in /.github/workflows ([#41](https://github.com/mougrim/php-xdebug-proxy/pull/41))
- Bump symfony/process from 5.4.3 to 5.4.46 ([#42](https://github.com/mougrim/php-xdebug-proxy/pull/42))

### [v0.5.0]

There are next changes:

- Minimum PHP version supported upgraded to 7.4 ([#29](https://github.com/mougrim/php-xdebug-proxy/pull/29))
- PHPUnit version upgraded to 9 ([#29](https://github.com/mougrim/php-xdebug-proxy/pull/29))
- Add run unit tests Github action ([#30](https://github.com/mougrim/php-xdebug-proxy/pull/30), [#31](https://github.com/mougrim/php-xdebug-proxy/pull/31))
- Remove travis ([#30](https://github.com/mougrim/php-xdebug-proxy/pull/30))
- Fix code style issues ([#32](https://github.com/mougrim/php-xdebug-proxy/pull/32))
- Fix by workaround grumphp issue with TypeError, see [phpro/grumphp#957](https://github.com/phpro/grumphp/issues/957) ([#32](https://github.com/mougrim/php-xdebug-proxy/pull/32))
- Update friendsofphp/php-cs-fixer to 3 ([#33](https://github.com/mougrim/php-xdebug-proxy/pull/33))
- Add psalm ([#34](https://github.com/mougrim/php-xdebug-proxy/pull/34), [#35](https://github.com/mougrim/php-xdebug-proxy/pull/35))
- Add check cs github action ([#36](https://github.com/mougrim/php-xdebug-proxy/pull/36))

### [v0.4.1]

There are next changes:

- Fix changes in grumphp and bad composer.lock ([#26](https://github.com/mougrim/php-xdebug-proxy/pull/26))

### [v0.4.0]

There are next changes:

- next methods were added ([#21](https://github.com/mougrim/php-xdebug-proxy/pull/21)):
  - `\Mougrim\XdebugProxy\Xml\XmlDocument::toArray()`
  - `\Mougrim\XdebugProxy\Xml\XmlContainer::toArray()`
  - `\Mougrim\XdebugProxy\Xml\XmlContainer::getAttribute()`
- [config](softMocksConfig) for soft-mocks was added ([#22](https://github.com/mougrim/php-xdebug-proxy/pull/22))
- parameter $config was added to method `\Mougrim\XdebugProxy\Factory\Factory::createRequestPreparers()` ([#22](https://github.com/mougrim/php-xdebug-proxy/pull/22))
- now `\Mougrim\XdebugProxy\Factory\SoftMocksFactory::createConfig()` should return `\Mougrim\XdebugProxy\Config\SoftMocksConfig` ([#22](https://github.com/mougrim/php-xdebug-proxy/pull/22))

### [v0.3.0]

There are next changes:

- there were code style fixes ([#14](https://github.com/mougrim/php-xdebug-proxy/pull/14))
- more info about default IDE in config was added to README.md ([#15](https://github.com/mougrim/php-xdebug-proxy/pull/15))
- now request preparers are called on request to xdebug from last to first ([#16](https://github.com/mougrim/php-xdebug-proxy/pull/16))
- minimum php version now is 7.1 ([#18](https://github.com/mougrim/php-xdebug-proxy/pull/18))
- constants visibility was added ([#18](https://github.com/mougrim/php-xdebug-proxy/pull/18))
- deprecated interface \Mougrim\XdebugProxy\RequestPreparer was removed ([#18](https://github.com/mougrim/php-xdebug-proxy/pull/18))

### [v0.2.1]

There are next changes:

- possibility to disable IDE registration was added ([#12](https://github.com/mougrim/php-xdebug-proxy/pull/12))

### [v0.2.0]

There are next changes:

- defaultIde config param was added ([#8](https://github.com/mougrim/php-xdebug-proxy/pull/8))
- predefinedIdeList config param was added ([#8](https://github.com/mougrim/php-xdebug-proxy/pull/8))
- now by default debug and info logs are disabled ([#9](https://github.com/mougrim/php-xdebug-proxy/pull/9))
- log levels for some logs are changed ([#9](https://github.com/mougrim/php-xdebug-proxy/pull/9))
- [README.md](README.md) was updated ([#11](https://github.com/mougrim/php-xdebug-proxy/pull/11))
- doc for [`RequestPreparer\RequestPreparer`](src/RequestPreparer/RequestPreparer.php) was added ([#11](https://github.com/mougrim/php-xdebug-proxy/pull/11))

### [v0.1.0]

There are next changes:

- [soft-mocks](https://github.com/badoo/soft-mocks/#using-with-xdebug) support was added ([#7](https://github.com/mougrim/php-xdebug-proxy/pull/7))
- `\Mougrim\XdebugProxy\Handler\CommandToXdebugParser::buildCommand()` method was added ([#7](https://github.com/mougrim/php-xdebug-proxy/pull/7))
- now `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` is used instead of `\Mougrim\XdebugProxy\RequestPreparer` ([#7](https://github.com/mougrim/php-xdebug-proxy/pull/7))
- `\Mougrim\XdebugProxy\RequestPreparer\RequestPreparer` methods can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem ([#7](https://github.com/mougrim/php-xdebug-proxy/pull/7))
- now `\Mougrim\XdebugProxy\Factory\Factory::createRequestPreparers()` accepts `$logger` param and also can throw `\Mougrim\XdebugProxy\RequestPreparer\Error` and `\Mougrim\XdebugProxy\RequestPreparer\Exception` if there is some problem ([#7](https://github.com/mougrim/php-xdebug-proxy/pull/7))

[unreleased]: https://github.com/mougrim/php-xdebug-proxy/compare/0.6.0...HEAD
[v0.6.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.5.1...0.6.0
[v0.5.1]: https://github.com/mougrim/php-xdebug-proxy/compare/0.5.0...0.5.1
[v0.5.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.4.1...0.5.0
[v0.4.1]: https://github.com/mougrim/php-xdebug-proxy/compare/0.4.0...0.4.1
[v0.4.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.3.0...0.4.0
[v0.3.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.2.1...0.3.0
[v0.2.1]: https://github.com/mougrim/php-xdebug-proxy/compare/0.2.0...0.2.1
[v0.2.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.1.0...0.2.0
[v0.1.0]: https://github.com/mougrim/php-xdebug-proxy/compare/0.0.1...0.1.0
