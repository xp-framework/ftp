<?php namespace peer\ftp\unittest;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};
use io\{FileNotFoundException, IOException, TempFile};
use lang\{IllegalStateException, Throwable};
use peer\AuthenticationException;
use peer\ftp\{FtpConnection, FtpDir, FtpEntry, FtpEntryList, FtpFile};
use test\{Before, After, Assert, Expect, Test, Values};

#[StartServer(TestingServer::class)]
class IntegrationTest {
  const CONTENTS= 'Test contents';

  private $server;

  /** @param web.unittest.StartServer $server */
  public function __construct($server) {
    $this->server= $server;
  }

  /** Creates a new connection */
  private function connection(string $user= 'test'): FtpConnection {
    $endpoint= $this->server->connection->remoteEndpoint();
    return new FtpConnection("ftp://{$user}:test@{$endpoint->getAddress()}?timeout=1");
  }

  /** @return iterable */
  private function uploads() {
    yield [new MemoryInputStream(self::CONTENTS)];

    $t= new TempFile();
    $t->open(TempFile::READWRITE);
    $t->write(self::CONTENTS);
    $t->seek(0, SEEK_SET);
    yield [$t];
    $t->close();
    $t->unlink();
  }

  #[Test]
  public function initially_not_connected() {
    Assert::false($this->connection()->isConnected());
  }

  #[Test]
  public function is_connected_after_connect() {
    $conn= $this->connection()->connect();

    Assert::true($conn->isConnected());
  }

  #[Test]
  public function is_no_longer_connected_after_close() {
    $conn= $this->connection();
    $conn->connect();
    $conn->close();

    Assert::false($conn->isConnected());
  }

  #[Test, Expect(AuthenticationException::class)]
  public function incorrect_credentials() {
    $this->connection('INCORRECT')->connect();
  }

  #[Test]
  public function retrieve_root_dir() {
    $conn= $this->connection()->connect();

    with ($root= $conn->rootDir()); {
      Assert::instance(FtpDir::class, $root);
      Assert::equals('/', $root->getName());
      Assert::true($root->isFolder());
    }
  }

  #[Test]
  public function retrieve_root_dir_entries() {
    $conn= $this->connection()->connect();
    $entries= $conn->rootDir()->entries();

    Assert::instance(FtpEntryList::class, $entries);
    Assert::false($entries->isEmpty());
    Assert::instance('peer.ftp.FtpEntry[]', $entries->asArray());
  }

  #[Test]
  public function send_cwd() {
    $conn= $this->connection()->connect();
    $r= $conn->sendCommand('CWD %s', '/htdocs/');

    Assert::equals('250 "/htdocs" is new working directory', $r[0]);
  }

  #[Test]
  public function listing_without_params() {
    $conn= $this->connection()->connect();
    $conn->sendCommand('CWD %s', '/htdocs/');
    $r= $conn->listingOf(null);

    Assert::equals(true, (bool)strpos(implode("\n", $r), 'index.html'));
  }

  #[Test]
  public function cwd_back_to_root() {
    $conn= $this->connection()->connect();

    $r= $conn->sendCommand('CWD %s', '/htdocs/');
    Assert::equals('250 "/htdocs" is new working directory', $r[0]);

    $r= $conn->sendCommand('CWD %s', '/');
    Assert::equals('250 "/" is new working directory', $r[0]);
  }

  #[Test]
  public function cwd_relative() {
    $conn= $this->connection()->connect();
    
    $r= $conn->sendCommand('CWD %s', '/outer/inner');
    Assert::equals('250 "/outer/inner" is new working directory', $r[0]);

    $r= $conn->sendCommand('CDUP');
    Assert::equals('250 CDUP command successful', $r[0]);

    $r= $conn->sendCommand('CWD inner');
    Assert::equals('250 "/outer/inner" is new working directory', $r[0]);
  }

  #[Test]
  public function dotTrashDir() {
    $conn= $this->connection()->connect();

    with ($r= $conn->rootDir()); {
      Assert::true($r->hasDir('.trash'));
      $dir= $r->getDir('.trash');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals('/.trash/', $dir->getName());
      
      // 2 entries exist: do-not-remove.txt & possibly .svn
      Assert::true(2 >= $dir->entries()->size());
    }
  }

  #[Test]
  public function htdocsDir() {
    $conn= $this->connection()->connect();

    with ($r= $conn->rootDir()); {
      Assert::true($r->hasDir('htdocs'));
      $dir= $r->getDir('htdocs');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals('/htdocs/', $dir->getName());
      Assert::notEquals(0, $dir->entries()->size());
    }
  }

  #[Test]
  public function emptyDir() {
    $conn= $this->connection()->connect();

    with ($r= $conn->rootDir()); {
      $dir= $r->newDir('.new');
      Assert::instance(FtpDir::class, $dir);
      Assert::equals(0, $dir->entries()->size());
    }
  }

  #[Test]
  public function nonExistantDir() {
    $conn= $this->connection()->connect();
    Assert::false($conn->rootDir()->hasDir(':DOES_NOT_EXIST'));
  }

  #[Test, Expect(FileNotFoundException::class)]
  public function getNonExistantDir() {
    $conn= $this->connection()->connect();
    $conn->rootDir()->getDir(':DOES_NOT_EXIST');
  }

  #[Test]
  public function indexHtml() {
    $conn= $this->connection()->connect();
    with ($htdocs= $conn->rootDir()->getDir('htdocs')); {
      Assert::true($htdocs->hasFile('index.html'));
      $index= $htdocs->getFile('index.html');
      Assert::instance(FtpFile::class, $index);
      Assert::equals('/htdocs/index.html', $index->getName());
      Assert::true($index->isFile());
    }
  }

  #[Test]
  public function whitespacesHtml() {
    $conn= $this->connection()->connect();
    with ($htdocs= $conn->rootDir()->getDir('htdocs')); {
      Assert::true($htdocs->hasFile('file with whitespaces.html'));
      $file= $htdocs->getFile('file with whitespaces.html');
      Assert::instance(FtpFile::class, $file);
      Assert::equals('/htdocs/file with whitespaces.html', $file->getName());
      Assert::true($file->isFile());
    }
  }

  #[Test]
  public function nonExistantFile() {
    $conn= $this->connection()->connect();
    Assert::false($conn->rootDir()->getDir('htdocs')->hasFile(':DOES_NOT_EXIST'));
  }

  #[Test, Expect(FileNotFoundException::class)]
  public function getNonExistantFile() {
    $conn= $this->connection()->connect();
    $conn->rootDir()->getDir('htdocs')->getFile(':DOES_NOT_EXIST');
  }

  #[Test, Expect(IllegalStateException::class)]
  public function directoryViaGetFile() {
    $conn= $this->connection()->connect();
    $conn->rootDir()->getFile('htdocs');
  }

  #[Test, Expect(IllegalStateException::class)]
  public function fileViaGetDir() {
    $conn= $this->connection()->connect();
    $conn->rootDir()->getDir('htdocs')->getDir('index.html');
  }

  #[Test, Values('uploads')]
  public function uploadFile($source) {
    $conn= $this->connection()->connect();

    try {
      $dir= $conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom($source);
      Assert::true($file->exists());
      Assert::equals(4, $file->getSize());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function renameFile() {
    $conn= $this->connection()->connect();

    try {
      $dir= $conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream(self::CONTENTS));
      $file->rename('renamed.txt');
      Assert::false($file->exists(), 'Origin file still exists');

      $file= $dir->file('renamed.txt');
      Assert::true($file->exists(), 'Renamed file does not exist');
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function moveFile() {
    $conn= $this->connection()->connect();

    try {
      $dir= $conn->rootDir()->getDir('htdocs');
      $trash= $conn->rootDir()->getDir('.trash');

      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream(self::CONTENTS));
      $file->moveTo($trash);
      Assert::false($file->exists());

      $file= $trash->file('name.txt');
      Assert::true($file->exists());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function download_to_stream() {
    $conn= $this->connection()->connect();

    $m= $conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->downloadTo(new MemoryOutputStream())
    ;

    Assert::equals("<html/>\n", $m->bytes());
  }

  #[Test]
  public function download_to_file() {
    $conn= $this->connection()->connect();
    $f= new TempFile();
    $f->open(TempFile::READWRITE);

    $conn->rootDir()->getDir('htdocs')->getFile('index.html')->downloadTo($f);

    $f->seek(0, SEEK_SET);
    try {
      Assert::equals("<html/>\n", $f->read(8));
    } finally {
      $f->close();
      $f->unlink();
    }
  }

  #[Test]
  public function in() {
    $conn= $this->connection()->connect();

    $s= $conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->in()
    ;

    Assert::equals("<html/>\n", Streams::readAll($s));
  }

  #[Test]
  public function consecutive_inputstream_reads() {
    $conn= $this->connection()->connect();
    $dir= $conn->rootDir()->getDir('htdocs');

    for ($i= 0; $i < 2; $i++) {
      try {
        $s= $dir->getFile('index.html')->in();
        Assert::equals("<html/>\n", Streams::readAll($s));
      } catch (IOException $e) {
        $this->fail('Round '.($i + 1), $e, null);
      }
    }
  }

  #[Test]
  public function out() {
    $conn= $this->connection()->connect();

    $file= $conn->rootDir()->getDir('htdocs')->file('name.txt');
    $s= $file->out();
    try {
      $s->write(self::CONTENTS);
      $s->close();

      Assert::true($file->exists());
      Assert::equals(strlen(self::CONTENTS), $file->getSize());
    } finally {
      $file->delete();
    }
  }

  #[After]
  public function shutdown() {
    $this->server->shutdown();
  }
}