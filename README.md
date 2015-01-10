FTP protocol support for the XP Framework
========================================================================

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/ftp.svg)](http://travis-ci.org/xp-framework/ftp)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/ftp/version.png)](https://packagist.org/packages/xp-framework/ftp)

Client
------

### Example: Uploading

```php
use peer\ftp\FtpConnection;
use peer\ftp\FtpTransfer;
use io\streams\FileInputStream;
use io\File;

with ($c= new FtpConnection('ftp://user:pass@example.com/')); {
  $c->connect();

  // Upload index.txt to the connection's root directory
  $c->rootDir()->file('index.txt')->uploadFrom(
    new FileInputStream(new File('index.txt')),
    FtpTransfer::ASCII
  );

  $c->close();
}
```

### Example: Listing

```php
use peer\ftp\FtpConnection;

with ($c= new FtpConnection('ftp://user:pass@example.com/')); {
  $c->connect();

  // List root directory's contents
  foreach ($c->rootDir()->entries() as $entry) {
    Console::writeLine('- ', $entry);
  }

  $c->close();
}
```
