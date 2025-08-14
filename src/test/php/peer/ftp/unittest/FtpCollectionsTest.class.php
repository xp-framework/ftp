<?php namespace peer\ftp\unittest;

use io\collections\iterate\IOCollectionIterator;
use peer\ftp\collections\FtpCollection;
use peer\ftp\{DefaultFtpListParser, FtpConnection, FtpDir, FtpEntryList};
use test\{Assert, Before, Test};

class FtpCollectionsTest {
  private $dir;

  #[Before]
  public function dir() {
    $connection= new FtpConnection('ftp://mock');
    $connection->parser= new DefaultFtpListParser();

    $this->dir= new class('/', $connection) extends FtpDir {
      public function entries() {
        return new FtpEntryList([
          'drwx---r-t  37 p159995  ftpusers     4096 Jul 30 18:59 .',
          'drwx---r-t  37 p159995  ftpusers     4096 Jul 30 18:59 ..',
          'drwxr-xr-x   2 p159995  ftpusers     4096 Mar 19  2007 .ssh',
          '-rw-------   1 p159995  ftpusers     7507 Nov 21  2000 .bash_history',
        ], $this->connection, '/');
      }
    };
  }
  
  #[Test]
  public function has_next_and_next() {
    $results= [];
    for ($c= new IOCollectionIterator(new FtpCollection($this->dir)); $c->hasNext(); ) {
      $results[]= $c->next()->getURI();
    }
    Assert::equals(['/.ssh/', '/.bash_history'], $results);
  }

  #[Test]
  public function foreach_iteration() {
    $results= [];
    foreach (new IOCollectionIterator(new FtpCollection($this->dir)) as $e) {
      $results[]= $e->getURI();
    }
    Assert::equals(['/.ssh/', '/.bash_history'], $results);
  }
}