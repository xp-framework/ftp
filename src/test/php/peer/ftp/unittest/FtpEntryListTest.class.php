<?php namespace peer\ftp\unittest;

use peer\ftp\FtpEntryList;
use peer\ftp\FtpConnection;
use peer\ftp\DefaultFtpListParser;
use peer\ftp\FtpListIterator;

/**
 * TestCase FTP listing functionality
 *
 * @see      xp://peer.ftp.FtpListIterator
 * @see      xp://peer.ftp.FtpEntryList
 * @purpose  Unittest
 */
class FtpEntryListTest extends \unittest\TestCase {
  protected $conn;

  /**
   * Sets up test case
   *
   */
  public function setUp() {
    $this->conn= new FtpConnection('ftp://mock');
    $this->conn->parser= new DefaultFtpListParser();
  }
  
  /**
   * Iterates on a given list
   *
   * @param   string[] list
   * @return  string[] results each element as {qualified.className}({elementName})
   */
  protected function iterationOn($list) {
    $it= new FtpListIterator($list, $this->conn);
    $r= [];
    foreach ($it as $entry) {
      $r[]= nameof($entry).'('.$entry->getName().')';
    }
    return $r;
  }
  
  /**
   * Creates a list fixture
   *
   * @return  peer.ftp.FtpEntryList
   */
  protected function listFixture() {
    return new FtpEntryList([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ], $this->conn);
  }

  #[@test]
  public function iteration() {
    $names= ['/secret/', '/wetter.html', '/.htaccess'];
    $classes= ['peer.ftp.FtpDir', 'peer.ftp.FtpFile', 'peer.ftp.FtpFile'];
    $offset= 0;

    foreach ($this->listFixture() as $key => $entry) {
      $this->assertInstanceOf($classes[$offset], $entry);
      $this->assertEquals($names[$offset], $key);
      $offset++;
    } 
  }

  #[@test]
  public function asArray() {
    $names= ['/secret/', '/wetter.html', '/.htaccess'];
    $classes= ['peer.ftp.FtpDir', 'peer.ftp.FtpFile', 'peer.ftp.FtpFile'];
    $offset= 0;

    foreach ($this->listFixture()->asArray() as $entry) {
      $this->assertInstanceOf($classes[$offset], $entry);
      $this->assertEquals($names[$offset], $entry->getName());
      $offset++;
    } 
  }

  #[@test]
  public function emptyDirectoryIsEmpty() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    $this->assertTrue((new FtpEntryList($empty, $this->conn))->isEmpty());
  }

  #[@test]
  public function emptyDirectorySize() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    $this->assertEquals(0, (new FtpEntryList($empty, $this->conn))->size());
  }

  #[@test]
  public function emptyDirectory() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    $this->assertEquals([], $this->iterationOn($empty));
  }

  #[@test]
  public function fixtureIsEmpty() {
    $this->assertFalse($this->listFixture()->isEmpty());
  }

  #[@test]
  public function fixtureSize() {
    $this->assertEquals(3, $this->listFixture()->size());
  }

  #[@test]
  public function directoryWithOneFile() {
    $this->assertEquals(['peer.ftp.FtpFile(/wetter.html)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html'
    ])); 
  }

  #[@test]
  public function directoryWithOneDir() {
    $this->assertEquals(['peer.ftp.FtpDir(/secret/)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret'
    ])); 
  }

  #[@test]
  public function directoryWithDirsAndFiles() {
    $this->assertEquals(['peer.ftp.FtpDir(/secret/)', 'peer.ftp.FtpFile(/wetter.html)', 'peer.ftp.FtpFile(/.htaccess)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ])); 
  }

  #[@test]
  public function dotDirectoriesAtEnd() {
    $this->assertEquals(['peer.ftp.FtpDir(/secret/)'], $this->iterationOn([
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ])); 
  }

  #[@test]
  public function dotDirectoriesMixedWithRegularResults() {
    $this->assertEquals(['peer.ftp.FtpDir(/secret/)', 'peer.ftp.FtpFile(/wetter.html)', 'peer.ftp.FtpFile(/.htaccess)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ])); 
  }
}
