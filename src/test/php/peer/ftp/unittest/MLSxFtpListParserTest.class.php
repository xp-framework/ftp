<?php namespace peer\ftp\unittest;
 
use peer\ftp\FtpConnection;
use peer\ftp\FtpDir;
use peer\ftp\FtpEntry;
use peer\ftp\MLSxFtpListParser;
use unittest\TestCase;
use util\Date;

/**
 * Tests MLSx list parser
 *
 * @see      xp://peer.ftp.MLSxFtpListParser
 */
class MLSxFtpListParserTest extends TestCase {
  protected $parser, $connectionl;

  /**
   * Setup this testcase
   *
   * @return void
   */
  public function setUp() {
    $this->parser= new MLSxFtpListParser();
    $this->connection= new FtpConnection('ftp://mock/');
  }
  
  #[@test]
  public function directory() {
    $e= $this->parser->entryFrom('modify=20190219174820;perm=flcdmpe;type=dir;unique=9300U81318505;UNIX.group=33;UNIX.mode=0755;UNIX.owner=33; bit', $this->connection, '/');

    $this->assertInstanceOf(FtpDir::class, $e);
    $this->assertEquals('/bit/', $e->getName());
    $this->assertEquals(1, $e->getNumlinks());
    $this->assertEquals('33', $e->getUser());
    $this->assertEquals('33', $e->getGroup());
    $this->assertEquals(0, $e->getSize());
    $this->assertEquals(new Date('19.02.2019 17:48:20'), $e->getDate());
    $this->assertEquals(0755, $e->getPermissions());
  }
}
