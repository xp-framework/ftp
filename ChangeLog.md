FTP protocol support for the XP Framework ChangeLog
========================================================================

## ?.?.? / ????-??-??

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
