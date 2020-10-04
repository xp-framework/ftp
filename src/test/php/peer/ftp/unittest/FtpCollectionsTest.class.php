<?php namespace peer\ftp\unittest;

use io\collections\iterate\IOCollectionIterator;
use peer\ftp\collections\FtpCollection;
use peer\ftp\{FtpConnection, FtpDir};
use unittest\{Test, TestCase};


/**
 * TestCase for FTP collections API
 *
 * @see      xp://peer.ftp.collections.FtpCollection
 * @purpose  Unittest
 */
class FtpCollectionsTest extends TestCase {
  protected $dir= null;

  /**
   * Sets up test case
   *
   */
  public function setUp() {
    $conn= new FtpConnection('ftp://mock');
    $conn->parser= new \peer\ftp\DefaultFtpListParser();
    $this->dir= newinstance(FtpDir::class, ['/', $conn], '{
      public function entries() {
        return new FtpEntryList(array(
          "drwx---r-t  37 p159995  ftpusers     4096 Jul 30 18:59 .",
          "drwx---r-t  37 p159995  ftpusers     4096 Jul 30 18:59 ..",
          "drwxr-xr-x   2 p159995  ftpusers     4096 Mar 19  2007 .ssh",
          "-rw-------   1 p159995  ftpusers     7507 Nov 21  2000 .bash_history",
        ), $this->connection, "/");
      }
    }');
  }
  
  /**
   * Test hasNext() and next() methods
   *
   */
  #[Test]
  public function hasNextAndNext() {
    $results= [];
    for ($c= new IOCollectionIterator(new FtpCollection($this->dir)); $c->hasNext(); ) {
      $results[]= $c->next()->getURI();
    }
    $this->assertEquals(['/.ssh/', '/.bash_history'], $results);
  }

  /**
   * Test iteration via foreach
   *
   */
  #[Test]
  public function foreachIteration() {
    $results= [];
    foreach (new IOCollectionIterator(new FtpCollection($this->dir)) as $e) {
      $results[]= $e->getURI();
    }
    $this->assertEquals(['/.ssh/', '/.bash_history'], $results);
  }
}