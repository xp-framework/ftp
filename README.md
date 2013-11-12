FTP protocol support for the XP Framework
========================================================================

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
