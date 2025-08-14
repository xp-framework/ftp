<?php namespace peer\ftp\unittest;

use peer\ftp\{FtpConnection, FtpDir, FtpEntry, WindowsFtpListParser};
use test\{Assert, Before, Test};
use util\Date;

class WindowsFtpListParserTest {
  private $conn;

  #[Before]
  public function conn() {
    $this->conn= new FtpConnection('ftp://mock/');
    $this->conn->parser= new WindowsFtpListParser();
  }
  
  #[Test]
  public function directory() {
    $e= $this->conn->parser->entryFrom('01-04-06  04:51PM       <DIR>          _db_import', $this->conn, '/');

    Assert::instance(FtpDir::class, $e);
    Assert::equals('/_db_import/', $e->getName());
    Assert::equals(0, $e->getNumlinks());
    Assert::equals(null, $e->getUser());
    Assert::equals(null, $e->getGroup());
    Assert::equals(0, $e->getSize());
    Assert::equals(new Date('04.01.2006 16:51'), $e->getDate());
    Assert::equals(0, $e->getPermissions());
  }

  #[Test]
  public function regularFile() {
    $e= $this->conn->parser->entryFrom('11-08-06  10:04AM                   27 info.txt', $this->conn, '/');

    Assert::instance(FtpEntry::class, $e);
    Assert::equals('/info.txt', $e->getName());
    Assert::equals(0, $e->getNumlinks());
    Assert::equals(null, $e->getUser());
    Assert::equals(null, $e->getGroup());
    Assert::equals(27, $e->getSize());
    Assert::equals(new Date('08.11.2006 10:04'), $e->getDate());
    Assert::equals(0, $e->getPermissions());
  }
}