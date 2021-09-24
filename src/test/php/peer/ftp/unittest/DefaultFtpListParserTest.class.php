<?php namespace peer\ftp\unittest;
 
use peer\ftp\{DefaultFtpListParser, FtpConnection, FtpDir, FtpEntry};
use unittest\{Test, Values};
use util\Date;

/**
 * Tests default list parser
 *
 * @see      xp://peer.ftp.DefaultFtpListParser
 */
class DefaultFtpListParserTest extends \unittest\TestCase {
  protected $parser, $connectionl;

  /** @return void */
  public function setUp() {
    $this->parser= new DefaultFtpListParser();
    $this->connection= new FtpConnection('ftp://mock/');
  }

  /** @return iterable */
  public function compactDates() {
    return [
      ['Jul 23 20:16', '23.07.2009 20:16'],   // 182 days in the future
      ['Apr 4 20:16' , '04.04.2009 20:16'],
      ['Jan 22 20:16', '22.01.2009 20:16'],   // exactly "today"
      ['Dec 1 20:16' , '01.12.2008 20:16'],
      ['Jul 24 20:16', '24.07.2008 20:16'],   // 182 days in the past
    ];
  }

  #[Test]
  public function dotDirectory() {
    $e= $this->parser->entryFrom('drwx---r-t 37 p159995 ftpusers 4096 Apr 4 2009 .', $this->connection, '/');

    $this->assertInstanceOf(FtpDir::class, $e);
    $this->assertEquals('/./', $e->getName());
    $this->assertEquals(37, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(4096, $e->getSize());
    $this->assertEquals(new Date('04.04.2009'), $e->getDate());
    $this->assertEquals(704, $e->getPermissions());
  }

  #[Test]
  public function regularFile() {
    $e= $this->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 write.html', $this->connection, '/');

    $this->assertInstanceOf(FtpEntry::class, $e);
    $this->assertEquals('/write.html', $e->getName());
    $this->assertEquals(1, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(415, $e->getSize());
    $this->assertEquals(new Date('23.05.2000'), $e->getDate());
    $this->assertEquals(604, $e->getPermissions());
  }

  #[Test]
  public function whitespaceInFileName() {
    $e= $this->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 answer me.html', $this->connection, '/');

    $this->assertInstanceOf(FtpEntry::class, $e);
    $this->assertEquals('/answer me.html', $e->getName());
    $this->assertEquals(1, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(415, $e->getSize());
    $this->assertEquals(new Date('23.05.2000'), $e->getDate());
    $this->assertEquals(604, $e->getPermissions());
  }

  #[Test, Values('compactDates')]
  public function compactDate($listed, $meaning) {
    $entry= $this->parser->entryFrom(
      'drwx---r-t 37 p159995 ftpusers 4096 '.$listed.' .',
      $this->connection,
      '/',
      new Date('2009-01-22 20:16')
    );
    $this->assertEquals(new Date($meaning), $entry->getDate());
  }
}