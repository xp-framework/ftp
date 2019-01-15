FTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

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
