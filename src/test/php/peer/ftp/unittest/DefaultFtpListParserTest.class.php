<?php namespace peer\ftp\unittest;
 
use peer\ftp\FtpConnection;
use peer\ftp\DefaultFtpListParser;
use peer\ftp\FtpDir;
use peer\ftp\FtpEntry;
use util\Date;

/**
 * Tests default list parser
 *
 * @see      xp://peer.ftp.DefaultFtpListParser
 */
class DefaultFtpListParserTest extends \unittest\TestCase {
  protected $parser, $connectionl;

  /**
   * Setup this testcase
   *
   * @return void
   */
  public function setUp() {
    $this->parser= new DefaultFtpListParser();
    $this->connection= new FtpConnection('ftp://mock/');
  }

  #[@test]
  public function dotDirectory() {
    $e= $this->parser->entryFrom('drwx---r-t 37 p159995 ftpusers 4096 Apr 4 2009 .', $this->connection, '/');

    $this->assertInstanceOf('peer.ftp.FtpDir', $e);
    $this->assertEquals('/./', $e->getName());
    $this->assertEquals(37, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(4096, $e->getSize());
    $this->assertEquals(new Date('04.04.2009'), $e->getDate());
    $this->assertEquals(704, $e->getPermissions());
  }

  #[@test]
  public function regularFile() {
    $e= $this->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 write.html', $this->connection, '/');

    $this->assertInstanceOf('peer.ftp.FtpEntry', $e);
    $this->assertEquals('/write.html', $e->getName());
    $this->assertEquals(1, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(415, $e->getSize());
    $this->assertEquals(new Date('23.05.2000'), $e->getDate());
    $this->assertEquals(604, $e->getPermissions());
  }

  #[@test]
  public function whitespaceInFileName() {
    $e= $this->parser->entryFrom('-rw----r-- 1 p159995 ftpusers 415 May 23 2000 answer me.html', $this->connection, '/');

    $this->assertInstanceOf('peer.ftp.FtpEntry', $e);
    $this->assertEquals('/answer me.html', $e->getName());
    $this->assertEquals(1, $e->getNumlinks());
    $this->assertEquals('p159995', $e->getUser());
    $this->assertEquals('ftpusers', $e->getGroup());
    $this->assertEquals(415, $e->getSize());
    $this->assertEquals(new Date('23.05.2000'), $e->getDate());
    $this->assertEquals(604, $e->getPermissions());
  }

  /**
   * Return an entry from a given date in its listing with a given 
   * reference date
   *
   * @param   string listed
   * @param   util.Date ref
   * @return  peer.ftp.FtpEntry
   */
  protected function entryWithDate($listed, Date $ref) {
    return $this->parser->entryFrom(
      'drwx---r-t 37 p159995 ftpusers 4096 '.$listed.' .', 
      $this->connection, 
      '/', 
      $ref
    );
  }

  /**
   * Provides values for compactDate() test
   *
   * @return  var[]
   */
  public function compactDates() {
    return [
      ['Jul 23 20:16', '23.07.2009 20:16'],   // 182 days in the future
      ['Apr 4 20:16' , '04.04.2009 20:16'],
      ['Jan 22 20:16', '22.01.2009 20:16'],   // exactly "today"
      ['Dec 1 20:16' , '01.12.2008 20:16'],
      ['Jul 24 20:16', '24.07.2008 20:16'],   // 182 days in the past
    ];
  }

  /**
   * Test compact date format
   */
  #[@test, @values('compactDates')]
  public function compactDate($listed, $meaning) {
    $ref= new Date('2009-01-22 20:16');
    $this->assertEquals(new Date($meaning), $this->entryWithDate($listed, $ref)->getDate());
  }
}
