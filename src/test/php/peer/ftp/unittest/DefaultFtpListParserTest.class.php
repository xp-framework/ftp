<?php namespace peer\ftp\unittest;

use peer\ftp\{DefaultFtpListParser, FtpConnection, FtpDir, FtpEntry};
use test\{Assert, Before, Test, Values};
use util\Date;

class DefaultFtpListParserTest {
  private $conn;

  /** @return iterable */
  public function compactDates() {
    yield ['Jul 23 20:16', '23.07.2009 20:16'];   // 182 days in the future
    yield ['Apr 4 20:16' , '04.04.2009 20:16'];
    yield ['Jan 22 20:16', '22.01.2009 20:16'];   // exactly "today"
    yield ['Dec 1 20:16' , '01.12.2008 20:16'];
    yield ['Jul 24 20:16', '24.07.2008 20:16'];   // 182 days in the past
  }

  #[Before]
  public function conn() {
    $this->conn= new FtpConnection('ftp://mock/');
    $this->conn->parser= new DefaultFtpListParser();
  }

  #[Test]
  public function dot_directory() {
    $e= $this->conn->parser->entryFrom('drwx---r-t 37 p159995 ftpusers 4096 Apr 4 2009 .', $this->conn, '/');

    Assert::instance(FtpDir::class, $e);
    Assert::equals('/./', $e->getName());
    Assert::equals(37, $e->getNumlinks());
    Assert::equals('p159995', $e->getUser());
    Assert::equals('ftpusers', $e->getGroup());
    Assert::equals(4096, $e->getSize());
    Assert::equals(new Date('04.04.2009'), $e->getDate());
    Assert::equals(704, $e->getPermissions());
  }

  #[Test]
  public function regular_file() {
    $e= $this->conn->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 write.html', $this->conn, '/');

    Assert::instance(FtpEntry::class, $e);
    Assert::equals('/write.html', $e->getName());
    Assert::equals(1, $e->getNumlinks());
    Assert::equals('p159995', $e->getUser());
    Assert::equals('ftpusers', $e->getGroup());
    Assert::equals(415, $e->getSize());
    Assert::equals(new Date('23.05.2000'), $e->getDate());
    Assert::equals(604, $e->getPermissions());
  }

  #[Test]
  public function whitespace_in_fileName() {
    $e= $this->conn->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 answer me.html', $this->conn, '/');

    Assert::instance(FtpEntry::class, $e);
    Assert::equals('/answer me.html', $e->getName());
    Assert::equals(1, $e->getNumlinks());
    Assert::equals('p159995', $e->getUser());
    Assert::equals('ftpusers', $e->getGroup());
    Assert::equals(415, $e->getSize());
    Assert::equals(new Date('23.05.2000'), $e->getDate());
    Assert::equals(604, $e->getPermissions());
  }

  #[Test, Values('compactDates')]
  public function parse_compact_date($listed, $meaning) {
    $entry= $this->conn->parser->entryFrom(
      'drwx---r-t 37 p159995 ftpusers 4096 '.$listed.' .',
      $this->conn,
      '/',
      new Date('2009-01-22 20:16')
    );
    Assert::equals(new Date($meaning), $entry->getDate());
  }
}