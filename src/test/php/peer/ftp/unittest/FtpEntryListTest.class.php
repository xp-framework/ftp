<?php namespace peer\ftp\unittest;

use peer\ftp\{DefaultFtpListParser, FtpConnection, FtpEntryList, FtpListIterator};
use unittest\{Assert, Test};

/**
 * TestCase FTP listing functionality
 *
 * @see  peer.ftp.FtpListIterator
 * @see  peer.ftp.FtpEntryList
 */
class FtpEntryListTest {
  private $conn;

  /**
   * Iterates on a given list
   *
   * @param   string[] list
   * @return  string[] results each element as {qualified.className}({elementName})
   */
  private function iterationOn($list) {
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
  private function listFixture() {
    return new FtpEntryList([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ], $this->conn);
  }

  #[Before]
  public function conn() {
    $this->conn= new FtpConnection('ftp://mock');
    $this->conn->parser= new DefaultFtpListParser();
  }

  #[Test]
  public function iteration() {
    $names= ['/secret/', '/wetter.html', '/.htaccess'];
    $classes= ['peer.ftp.FtpDir', 'peer.ftp.FtpFile', 'peer.ftp.FtpFile'];
    $offset= 0;

    foreach ($this->listFixture() as $key => $entry) {
      Assert::instance($classes[$offset], $entry);
      Assert::equals($names[$offset], $key);
      $offset++;
    } 
  }

  #[Test]
  public function asArray() {
    $names= ['/secret/', '/wetter.html', '/.htaccess'];
    $classes= ['peer.ftp.FtpDir', 'peer.ftp.FtpFile', 'peer.ftp.FtpFile'];
    $offset= 0;

    foreach ($this->listFixture()->asArray() as $entry) {
      Assert::instance($classes[$offset], $entry);
      Assert::equals($names[$offset], $entry->getName());
      $offset++;
    } 
  }

  #[Test]
  public function emptyDirectoryIsEmpty() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    Assert::true((new FtpEntryList($empty, $this->conn))->isEmpty());
  }

  #[Test]
  public function emptyDirectorySize() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    Assert::equals(0, (new FtpEntryList($empty, $this->conn))->size());
  }

  #[Test]
  public function emptyDirectory() {
    $empty= [
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ];
    Assert::equals([], $this->iterationOn($empty));
  }

  #[Test]
  public function fixtureIsEmpty() {
    Assert::false($this->listFixture()->isEmpty());
  }

  #[Test]
  public function fixtureSize() {
    Assert::equals(3, $this->listFixture()->size());
  }

  #[Test]
  public function directoryWithOneFile() {
    Assert::equals(['peer.ftp.FtpFile(/wetter.html)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html'
    ])); 
  }

  #[Test]
  public function directoryWithOneDir() {
    Assert::equals(['peer.ftp.FtpDir(/secret/)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret'
    ])); 
  }

  #[Test]
  public function directoryWithDirsAndFiles() {
    Assert::equals(['peer.ftp.FtpDir(/secret/)', 'peer.ftp.FtpFile(/wetter.html)', 'peer.ftp.FtpFile(/.htaccess)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ])); 
  }

  #[Test]
  public function dotDirectoriesAtEnd() {
    Assert::equals(['peer.ftp.FtpDir(/secret/)'], $this->iterationOn([
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..'
    ])); 
  }

  #[Test]
  public function dotDirectoriesMixedWithRegularResults() {
    Assert::equals(['peer.ftp.FtpDir(/secret/)', 'peer.ftp.FtpFile(/wetter.html)', 'peer.ftp.FtpFile(/.htaccess)'], $this->iterationOn([
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 .',
      'drwxr-xr-x   2 p159995  ftpusers     4096 Mar  9  2007 secret',
      '-rw-r--r--   1 p159995  ftpusers       82 Oct 31  2006 wetter.html',
      'drwx---r-t  36 p159995  ftpusers     4096 May 14 17:44 ..',
      '-rw-------   1 p159995  ftpusers      102 Dec 14  2007 .htaccess'
    ])); 
  }
}