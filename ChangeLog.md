FTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

* Fixed deprecation warning *Non-canonical cast (double) is deprecated,
  use the (float) cast instead* in PHP 8.5
  (@thekid)

## 11.1.0 / 2024-03-24

* Made compatible with XP 12 - @thekid
* Added PHP 8.2, 8.3 and 8.4 to the test matrix - @thekid

## 11.0.3 / 2021-10-21

* Replaced deprecated util.DateUtil class with `util.Dates` - @thekid

## 11.0.2 / 2021-10-21

* Made library compatible with `xp-framework/logging` version 11.0.0
  (@thekid)

## 11.0.1 / 2021-10-21

* Made library compatible with XP 11 - @thekid

## 11.0.0 / 2021-09-26

* Merged PR #11: Drop support for XP < 9 - @thekid
* Removed deprecated *get(Input|Output)Stream()* from `FtpFile` - @thekid
* Removed deprecated *FtpListIterator* class - @thekid
* Merged PR #9: Default transfer mode to binary - @thekid

## 10.2.0 / 2021-09-26

* Merged PR #10: Replace `FtpListIterator` with yield, deprecating the
  class while doing so
  (@thekid)
* Removed deprecated methods from `FtpConnection` and `FtpDir` which have
  survived there since 2007, when xp-framework/rfc#140 was implemented!
  No BC break since all these methods did was raise exceptions.
  (@thekid)
* Merged PR #7: Add FtpEntry::isFile() and FtpEntry::isFolder() methods
  (@thekid)
* Merged PR #8: Also accept io.Channel instances for up- and downloading
  (@thekid)

## 10.1.0 / 2021-09-24

* Merged PR #6: Add `FtpFile::in()` and `FtpFile::out()` and implement
  the `io.Channel` interface to make FTP files usable with a variety of
  APIs from `io.streams`.
  (@thekid)
* Migrated testing from Travis CI to GitHub actions - @thekid

## 10.0.1 / 2021-09-24

* Fixed PHP 8.0 and PHP 8.1 compatibility - @thekid
* Fixed connection to FTP servers with multiline banner message - @thekid
* Replaced deprecated xp::stringOf() calls with util.Objects - @thekid

## 10.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 9.1.1 / 2020-04-04

* Made compatible with XP 10 - @thekid

## 9.1.0 / 2019-01-15

* Made `FtpEntry`, `FtpEntryList`, `FtpTransfer` and `FtpTransferStream`
  implement `lang.Value`, restoring their custom string representations.
  (@thekid)
* Added compatibility with PHP 7.3 - @thekid

## 9.0.1 / 2018-08-24

* Made compatible with `xp-framework/logging` version 9.0.0 - @thekid

## 9.0.0 / 2017-11-13

* Dropped dependency on `xp-framework/security` - @thekid
* Merged PR #3: Default passive mode to true - @thekid

## 8.0.0 / 2017-10-14

* Added forward compatibility with XP 9.0.0 - @thekid
* **Heads up: Dropped PHP 5.5 support**. Minimum PHP version is now PHP 5.6.0
  (@thekid)

## 7.2.0 / 2016-12-18

* Added `isConnected()` method to FtpConnection class in order to detect
  and gracefully handle disconnects.
* Added `timeout()`, `passive()`, `user` and `remoteEndpoint` accessors
  to FtpConnection class
  (@thekid)

## 7.1.0 / 2016-08-29

* Added forward compatibility with XP 8.0.0 - @thekid

## 7.0.1 / 2016-04-21

* Merged PR #2: Fix problem with listing empty directories - @thekid

## 7.0.0 / 2016-02-22

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
* Added version compatibility with XP 7 - @thekid

## 6.2.2 / 2016-01-23

* Fix code to use `nameof()` instead of the deprecated `getClassName()`
  method from lang.Generic. See xp-framework/core#120
  (@thekid)

## 6.2.1 / 2015-12-20

* Rewrote code to avoid deprecated ensure statement - @thekid

## 6.2.0 / 2015-12-14

* **Heads up**: Changed minimum XP version to XP 6.5.0, and with it the
  minimum PHP version to PHP 5.5.
  (@thekid)

## 6.1.2 / 2015-09-26

* Merged PR #1: Use short array syntax / ::class in annotations - @thekid

## 6.1.1 / 2015-07-12

* Added forward compatibility with XP 6.4.0 - @thekid

## 6.1.0 / 2015-06-13

* Added forward compatibility with PHP7 - @thekid

## 6.0.1 / 2015-02-12

* Changed dependency to use XP ~6.0 (instead of dev-master) - @thekid

## 6.0.0 / 2015-01-10

* Heads up: Renoved deprecated peer.ftp.server.FtpConnectionListener
  class which has been superseded by FtpProtocol - (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
