FTP protocol support for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-framework/ftp/workflows/Tests/badge.svg)](https://github.com/xp-framework/ftp/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/ftp/version.png)](https://packagist.org/packages/xp-framework/ftp)

Userland FTP protocol implementation, no dependency on PHP's *ftp* extension.

Client
------

### Example: Uploading

```php
use peer\ftp\{FtpConnection, FtpTransfer};
use io\streams\FileInputStream;
use io\File;

$c= (new FtpConnection('ftp://user:pass@example.com/'))->connect();

// Upload index.txt to the connection's root directory
$c->rootDir()->file('index.txt')->uploadFrom(
  new FileInputStream(new File('index.txt')),
  FtpTransfer::ASCII
);

$c->close();
```

### Example: Listing

```php
use peer\ftp\FtpConnection;

$c= (new FtpConnection('ftp://user:pass@example.com/'))->connect();

// List root directory's contents
foreach ($c->rootDir()->entries() as $entry) {
  Console::writeLine('- ', $entry);
}

$c->close();
```
