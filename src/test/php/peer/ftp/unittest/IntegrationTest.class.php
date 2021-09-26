<?php namespace peer\ftp\unittest;

use io\streams\{MemoryInputStream, MemoryOutputStream, Streams};
use io\{FileNotFoundException, IOException, TempFile};
use lang\{IllegalStateException, Throwable};
use peer\AuthenticationException;
use peer\ftp\{FtpConnection, FtpDir, FtpEntry, FtpEntryList, FtpFile};
use unittest\{Action, Expect, Test, TestCase};

/**
 * TestCase for FTP API.
 *
 * @see      xp://peer.ftp.FtpConnection
 */
#[Action(eval: 'new StartServer("peer.ftp.unittest.TestingServer", "connected", "shutdown")')]
class IntegrationTest extends TestCase {
  public static $bindAddress= null;
  protected $conn= null;

  /**
   * Callback for when server is connected
   *
   * @param  string $bindAddress
   */
  public static function connected($bindAddress) {
    self::$bindAddress= $bindAddress;
  }

  /**
   * Callback for when server should be shut down
   */
  public static function shutdown() {
    $c= new FtpConnection('ftp://test:test@'.self::$bindAddress);
    $c->connect();
    $c->sendCommand('SHUTDOWN');
    $c->close();
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

  /**
   * Sets up test case
   */
  public function setUp() {
    $this->conn= new FtpConnection('ftp://test:test@'.self::$bindAddress.'?timeout=1');
  }

  /**
   * Tears down test case
   */
  public function tearDown() {
    $this->conn->close();
  }

  #[Test]
  public function initially_not_connected() {
    $this->assertFalse($this->conn->isConnected());
  }

  #[Test]
  public function connect() {
    $this->conn->connect();
  }

  #[Test]
  public function is_connected_after_connect() {
    $this->conn->connect();
    $this->assertTrue($this->conn->isConnected());
  }

  #[Test]
  public function is_no_longer_connected_after_close() {
    $this->conn->connect();
    $this->conn->close();
    $this->assertFalse($this->conn->isConnected());
  }

  #[Test, Expect(AuthenticationException::class)]
  public function incorrect_credentials() {
    (new FtpConnection('ftp://test:INCORRECT@'.self::$bindAddress.'?timeout=1'))->connect();
  }

  #[Test]
  public function retrieve_root_dir() {
    $this->conn->connect();
    with ($root= $this->conn->rootDir()); {
      $this->assertInstanceOf(FtpDir::class, $root);
      $this->assertEquals('/', $root->getName());
    }
  }


  #[Test]
  public function retrieve_root_dir_entries() {
    $this->conn->connect();
    $entries= $this->conn->rootDir()->entries();
    $this->assertInstanceOf(FtpEntryList::class, $entries);
    $this->assertFalse($entries->isEmpty());
    foreach ($entries as $entry) {
      $this->assertInstanceOf(FtpEntry::class, $entry);
    }
  }

  #[Test]
  public function sendCwd() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/htdocs/');
    $this->assertEquals('250 "/htdocs" is new working directory', $r[0]);
  }

  #[Test]
  public function listingWithoutParams() {
    $this->conn->connect();
    $this->conn->sendCommand('CWD %s', '/htdocs/');
    $r= $this->conn->listingOf(null);
    $list= implode("\n", $r);
    $this->assertEquals(true, (bool)strpos($list, 'index.html'), $list);
  }

  #[Test]
  public function cwdBackToRoot() {
    $this->sendCwd();
    $r= $this->conn->sendCommand('CWD %s', '/');
    $this->assertEquals('250 "/" is new working directory', $r[0]);
  }

  #[Test]
  public function cwdRelative() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/outer/inner');
    $this->assertEquals('250 "/outer/inner" is new working directory', $r[0]);

    $r= $this->conn->sendCommand('CDUP');
    $this->assertEquals('250 CDUP command successful', $r[0]);

    $r= $this->conn->sendCommand('CWD inner');
    $this->assertEquals('250 "/outer/inner" is new working directory', $r[0]);
  }

  #[Test]
  public function dotTrashDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      $this->assertTrue($r->hasDir('.trash'));
      $dir= $r->getDir('.trash');
      $this->assertInstanceOf(FtpDir::class, $dir);
      $this->assertEquals('/.trash/', $dir->getName());
      
      // 2 entries exist: do-not-remove.txt & possibly .svn
      $this->assertTrue(2 >= $dir->entries()->size());
    }
  }

  #[Test]
  public function htdocsDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      $this->assertTrue($r->hasDir('htdocs'));
      $dir= $r->getDir('htdocs');
      $this->assertInstanceOf(FtpDir::class, $dir);
      $this->assertEquals('/htdocs/', $dir->getName());
      $this->assertNotEquals(0, $dir->entries()->size());
    }
  }

  #[Test]
  public function emptyDir() {
    $this->conn->connect();
    with ($r= $this->conn->rootDir()); {
      $dir= $r->newDir('.new');
      $this->assertInstanceOf(FtpDir::class, $dir);
      $this->assertEquals(0, $dir->entries()->size());
    }
  }

  #[Test]
  public function nonExistantDir() {
    $this->conn->connect();
    $this->assertFalse($this->conn->rootDir()->hasDir(':DOES_NOT_EXIST'));
  }

  #[Test, Expect(FileNotFoundException::class)]
  public function getNonExistantDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir(':DOES_NOT_EXIST');
  }

  #[Test]
  public function indexHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      $this->assertTrue($htdocs->hasFile('index.html'));
      $index= $htdocs->getFile('index.html');
      $this->assertInstanceOf(FtpFile::class, $index);
      $this->assertEquals('/htdocs/index.html', $index->getName());
    }
  }

  #[Test]
  public function whitespacesHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      $this->assertTrue($htdocs->hasFile('file with whitespaces.html'));
      $file= $htdocs->getFile('file with whitespaces.html');
      $this->assertInstanceOf(FtpFile::class, $file);
      $this->assertEquals('/htdocs/file with whitespaces.html', $file->getName());
    }
  }

  #[Test]
  public function nonExistantFile() {
    $this->conn->connect();
    $this->assertFalse($this->conn->rootDir()->getDir('htdocs')->hasFile(':DOES_NOT_EXIST'));
  }

  #[Test, Expect(FileNotFoundException::class)]
  public function getNonExistantFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getFile(':DOES_NOT_EXIST');
  }

  #[Test, Expect(IllegalStateException::class)]
  public function directoryViaGetFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getFile('htdocs');
  }

  #[Test, Expect(IllegalStateException::class)]
  public function fileViaGetDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getDir('index.html');
  }

  #[Test, Values('uploads')]
  public function uploadFile($source) {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom($source);
      $this->assertTrue($file->exists());
      $this->assertEquals(strlen($this->name), $file->getSize());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function renameFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $file->rename('renamed.txt');
      $this->assertFalse($file->exists(), 'Origin file still exists');

      $file= $dir->file('renamed.txt');
      $this->assertTrue($file->exists(), 'Renamed file does not exist');
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function moveFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $trash= $this->conn->rootDir()->getDir('.trash');

      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $file->moveTo($trash);
      $this->assertFalse($file->exists());

      $file= $trash->file('name.txt');
      $this->assertTrue($file->exists());
    } finally {
      isset($file) && $file->delete();
    }
  }

  #[Test]
  public function downloadFile() {
    $this->conn->connect();

    $m= $this->conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->downloadTo(new MemoryOutputStream())
    ;

    $this->assertEquals("<html/>\n", $m->getBytes());
  }

  #[Test]
  public function in() {
    $this->conn->connect();

    $s= $this->conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->in()
    ;

    $this->assertEquals("<html/>\n", Streams::readAll($s));
  }

  #[Test]
  public function consecutive_inputstream_reads() {
    $this->conn->connect();
    $dir= $this->conn->rootDir()->getDir('htdocs');

    for ($i= 0; $i < 2; $i++) {
      try {
        $s= $dir->getFile('index.html')->in();
        $this->assertEquals("<html/>\n", Streams::readAll($s));
      } catch (IOException $e) {
        $this->fail('Round '.($i + 1), $e, null);
      }
    }
  }

  #[Test]
  public function out() {
    $this->conn->connect();

    $file= $this->conn->rootDir()->getDir('htdocs')->file('name.txt');
    $s= $file->out();
    try {
      $s->write($this->name);
      $s->close();

      $this->assertTrue($file->exists());
      $this->assertEquals(strlen($this->name), $file->getSize());
    } finally {
      $file->delete();
    }
  }
}