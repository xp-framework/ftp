<?php namespace peer\ftp\unittest;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};
use io\{FileNotFoundException, IOException, TempFile};
use lang\{IllegalStateException, Throwable};
use peer\AuthenticationException;
use peer\ftp\{FtpConnection, FtpDir, FtpEntry, FtpEntryList, FtpFile};
use test\{Before, After, Assert, Expect, Test};

#[StartServer(TestingServer::class)]
class IntegrationTest {
  private $server;

  /** @param web.unittest.StartServer $server */
  public function __construct($server) {
    $this->server= $server;
  }

  /** @return iterable */
  private function uploads() {
    yield [new MemoryInputStream($this->name)];

    $t= new TempFile();
    $t->open(TempFile::READWRITE);
    $t->write($this->name);
    $t->seek(0, SEEK_SET);
    yield [$t];
    $t->close();
    $t->unlink();
  }

  #[Before]
  public function open() {
    $endpoint= $this->server->connection->remoteEndpoint();
    $this->conn= new FtpConnection("ftp://test:test@{$endpoint->getAddress()}?timeout=1");
  }

  #[Test]
  public function initially_not_connected() {
    Assert::false($this->conn->isConnected());
  }

  #[_Test]
  public function connect() {
    $this->conn->connect();
  }

  #[_Test]
  public function is_connected_after_connect() {
    $this->conn->connect();
    Assert::true($this->conn->isConnected());
  }

  #[_Test]
  public function is_no_longer_connected_after_close() {
    $this->conn->connect();
    $this->conn->close();
    Assert::false($this->conn->isConnected());
  }

  #[_Test, Expect(AuthenticationException::class)]
  public function incorrect_credentials() {
    $endpoint= $this->server->connection->remoteEndpoint();
    (new FtpConnection("ftp://test:INCORRECT@{$endpoint->getAddress()}?timeout=1"))->connect();
  }

  #[_Test]
  public function retrieve_root_dir() {
    $this->conn->connect();
    with ($root= $this->conn->rootDir()); {
      Assert::instance(FtpDir::class, $root);
      Assert::equals('/', $root->getName());
      Assert::true($root->isFolder());
    }
  }

  #[_Test]
  public function retrieve_root_dir_entries() {
    $this->conn->connect();

    $entries= $this->conn->rootDir()->entries();
    Assert::instance(FtpEntryList::class, $entries);
    Assert::false($entries->isEmpty());
    Assert::instance('peer.ftp.FtpEntry[]', $entries->asArray());
  }

  #[_Test]
  public function sendCwd() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/htdocs/');
    Assert::equals('250 "/htdocs" is new working directory', $r[0]);
  }

  #[_Test]
  public function listingWithoutParams() {
    $this->conn->connect();
    $this->conn->sendCommand('CWD %s', '/htdocs/');
    $r= $this->conn->listingOf(null);
    $list= implode("\n", $r);
    Assert::equals(true, (bool)strpos($list, 'index.html'), $list);
  }

  #[_Test]
  public function cwdBackToRoot() {
    $this->sendCwd();
    $r= $this->conn->sendCommand('CWD %s', '/');
    Assert::equals('250 "/" is new working directory', $r[0]);
  }

  #[_Test]
  public function cwdRelative() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/outer/inner');
    Assert::equals('250 "/outer/inner" is new working directory', $r[0]);

    $r= $this->conn->sendCommand('CDUP');
    Assert::equals('250 CDUP command successful', $r[0]);

    $r= $this->conn->sendCommand('CWD inner');
    Assert::equals('250 "/outer/inner" is new working directory', $r[0]);
  }

  #[_Test]
  public function dotTrashDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      Assert::true($r->hasDir('.trash'));
      $dir= $r->getDir('.trash');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals('/.trash/', $dir->getName());
      
      // 2 entries exist: do-not-remove.txt & possibly .svn
      Assert::true(2 >= $dir->entries()->size());
    }
  }

  #[_Test]
  public function htdocsDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      Assert::true($r->hasDir('htdocs'));
      $dir= $r->getDir('htdocs');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals('/htdocs/', $dir->getName());
      Assert::notEquals(0, $dir->entries()->size());
    }
  }

  #[_Test]
  public function emptyDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      $dir= $r->newDir('.new');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals(0, $dir->entries()->size());
    }
  }

  #[_Test]
  public function nonExistantDir() {
    $this->conn->connect();
    Assert::false($this->conn->rootDir()->hasDir(':DOES_NOT_EXIST'));
  }

  #[_Test, Expect(FileNotFoundException::class)]
  public function getNonExistantDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir(':DOES_NOT_EXIST');
  }

  #[_Test]
  public function indexHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      Assert::true($htdocs->hasFile('index.html'));
      $index= $htdocs->getFile('index.html');
      Assert::instance(FtpFile::class, $index);
      Assert::equals('/htdocs/index.html', $index->getName());
      Assert::true($index->isFile());
    }
  }

  #[_Test]
  public function whitespacesHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      Assert::true($htdocs->hasFile('file with whitespaces.html'));
      $file= $htdocs->getFile('file with whitespaces.html');
      Assert::instance(FtpFile::class, $file);
      Assert::equals('/htdocs/file with whitespaces.html', $file->getName());
      Assert::true($file->isFile());
    }
  }

  #[_Test]
  public function nonExistantFile() {
    $this->conn->connect();
    Assert::false($this->conn->rootDir()->getDir('htdocs')->hasFile(':DOES_NOT_EXIST'));
  }

  #[_Test, Expect(FileNotFoundException::class)]
  public function getNonExistantFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getFile(':DOES_NOT_EXIST');
  }

  #[_Test, Expect(IllegalStateException::class)]
  public function directoryViaGetFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getFile('htdocs');
  }

  #[_Test, Expect(IllegalStateException::class)]
  public function fileViaGetDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getDir('index.html');
  }

  #[_Test, Values('uploads')]
  public function uploadFile($source) {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom($source);
      Assert::true($file->exists());
      Assert::equals(strlen($this->name), $file->getSize());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[_Test]
  public function renameFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $file->rename('renamed.txt');
      Assert::false($file->exists(), 'Origin file still exists');

      $file= $dir->file('renamed.txt');
      Assert::true($file->exists(), 'Renamed file does not exist');
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[_Test]
  public function moveFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $trash= $this->conn->rootDir()->getDir('.trash');

      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $file->moveTo($trash);
      Assert::false($file->exists());

      $file= $trash->file('name.txt');
      Assert::true($file->exists());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[_Test]
  public function download_to_stream() {
    $this->conn->connect();

    $m= $this->conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->downloadTo(new MemoryOutputStream())
    ;

    Assert::equals("<html/>\n", $m->bytes());
  }

  #[_Test]
  public function download_to_file() {
    $this->conn->connect();
    $f= new TempFile();
    $f->open(TempFile::READWRITE);

    $this->conn->rootDir()->getDir('htdocs')->getFile('index.html')->downloadTo($f);

    $f->seek(0, SEEK_SET);
    try {
      Assert::equals("<html/>\n", $f->read(8));
    } finally {
      $f->close();
      $f->unlink();
    }
  }

  #[_Test]
  public function in() {
    $this->conn->connect();

    $s= $this->conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->in()
    ;

    Assert::equals("<html/>\n", Streams::readAll($s));
  }

  #[_Test]
  public function consecutive_inputstream_reads() {
    $this->conn->connect();
    $dir= $this->conn->rootDir()->getDir('htdocs');

    for ($i= 0; $i < 2; $i++) {
      try {
        $s= $dir->getFile('index.html')->in();
        Assert::equals("<html/>\n", Streams::readAll($s));
      } catch (IOException $e) {
        $this->fail('Round '.($i + 1), $e, null);
      }
    }
  }

  #[_Test]
  public function out() {
    $this->conn->connect();

    $file= $this->conn->rootDir()->getDir('htdocs')->file('name.txt');
    $s= $file->out();
    try {
      $s->write($this->name);
      $s->close();

      Assert::true($file->exists());
      Assert::equals(strlen($this->name), $file->getSize());
    } finally {
      $file->delete();
    }
  }

  #[After]
  public function close() {
    $this->conn->close();
  }

  #[After]
  public function shutdown() {
    $this->server->shutdown();
  }
}