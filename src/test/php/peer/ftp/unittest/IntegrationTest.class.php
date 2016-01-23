<?php namespace peer\ftp\unittest;

use peer\ftp\FtpDir;
use peer\ftp\FtpEntryList;
use peer\ftp\FtpEntry;
use peer\ftp\FtpFile;
use peer\AuthenticationException;
use io\FileNotFoundException;
use lang\IllegalStateException;
use io\streams\MemoryInputStream;
use io\streams\MemoryOutputStream;
use io\streams\Streams;
use io\IOException;
use peer\ftp\FtpConnection;
use lang\Throwable;

/**
 * TestCase for FTP API.
 *
 * @see      xp://peer.ftp.FtpConnection
 */
#[@action(new StartServer('peer.ftp.unittest.TestingServer', 'connected', 'shutdown'))]
class IntegrationTest extends \unittest\TestCase {
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

  /**
   * Sets up test case
   */
  public function setUp() {
    $this->conn= new FtpConnection('ftp://test:test@'.self::$bindAddress.'?passive=1&timeout=1');
  }

  /**
   * Tears down test case
   */
  public function tearDown() {
    $this->conn->close();
  }

  #[@test]
  public function connect() {
    $this->conn->connect();
  }

  #[@test, @expect(AuthenticationException::class)]
  public function incorrect_credentials() {
    (new FtpConnection('ftp://test:INCORRECT@'.self::$bindAddress.'?timeout=1'))->connect();
  }

  #[@test]
  public function retrieve_root_dir() {
    $this->conn->connect();
    with ($root= $this->conn->rootDir()); {
      $this->assertInstanceOf(FtpDir::class, $root);
      $this->assertEquals('/', $root->getName());
    }
  }


  #[@test]
  public function retrieve_root_dir_entries() {
    $this->conn->connect();
    $entries= $this->conn->rootDir()->entries();
    $this->assertInstanceOf(FtpEntryList::class, $entries);
    $this->assertFalse($entries->isEmpty());
    foreach ($entries as $entry) {
      $this->assertInstanceOf(FtpEntry::class, $entry);
    }
  }

  #[@test]
  public function sendCwd() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/htdocs/');
    $this->assertEquals('250 "/htdocs" is new working directory', $r[0]);
  }

  #[@test]
  public function listingWithoutParams() {
    $this->conn->connect();
    $this->conn->sendCommand('CWD %s', '/htdocs/');
    $r= $this->conn->listingOf(null);
    $list= implode("\n", $r);
    $this->assertEquals(true, (bool)strpos($list, 'index.html'), $list);
  }

  #[@test]
  public function cwdBackToRoot() {
    $this->sendCwd();
    $r= $this->conn->sendCommand('CWD %s', '/');
    $this->assertEquals('250 "/" is new working directory', $r[0]);
  }

  #[@test]
  public function cwdRelative() {
    $this->conn->connect();
    $r= $this->conn->sendCommand('CWD %s', '/outer/inner');
    $this->assertEquals('250 "/outer/inner" is new working directory', $r[0]);

    $r= $this->conn->sendCommand('CDUP');
    $this->assertEquals('250 CDUP command successful', $r[0]);

    $r= $this->conn->sendCommand('CWD inner');
    $this->assertEquals('250 "/outer/inner" is new working directory', $r[0]);
  }

  #[@test]
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

  #[@test]
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

  #[@test]
  public function nonExistantDir() {
    $this->conn->connect();
    $this->assertFalse($this->conn->rootDir()->hasDir(':DOES_NOT_EXIST'));
  }

  #[@test, @expect(FileNotFoundException::class)]
  public function getNonExistantDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir(':DOES_NOT_EXIST');
  }

  #[@test]
  public function indexHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      $this->assertTrue($htdocs->hasFile('index.html'));
      $index= $htdocs->getFile('index.html');
      $this->assertInstanceOf(FtpFile::class, $index);
      $this->assertEquals('/htdocs/index.html', $index->getName());
    }
  }

  #[@test]
  public function whitespacesHtml() {
    $this->conn->connect();
    with ($htdocs= $this->conn->rootDir()->getDir('htdocs')); {
      $this->assertTrue($htdocs->hasFile('file with whitespaces.html'));
      $file= $htdocs->getFile('file with whitespaces.html');
      $this->assertInstanceOf(FtpFile::class, $file);
      $this->assertEquals('/htdocs/file with whitespaces.html', $file->getName());
    }
  }

  #[@test]
  public function nonExistantFile() {
    $this->conn->connect();
    $this->assertFalse($this->conn->rootDir()->getDir('htdocs')->hasFile(':DOES_NOT_EXIST'));
  }

  #[@test, @expect(FileNotFoundException::class)]
  public function getNonExistantFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getFile(':DOES_NOT_EXIST');
  }

  #[@test, @expect(IllegalStateException::class)]
  public function directoryViaGetFile() {
    $this->conn->connect();
    $this->conn->rootDir()->getFile('htdocs');
  }

  #[@test, @expect(IllegalStateException::class)]
  public function fileViaGetDir() {
    $this->conn->connect();
    $this->conn->rootDir()->getDir('htdocs')->getDir('index.html');
  }

  #[@test]
  public function uploadFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $this->assertTrue($file->exists());
      $this->assertEquals(strlen($this->name), $file->getSize());
      $file->delete();
    } catch (\lang\Throwable $e) {

      // Unfortunately, try { } finally does not exist...
      try {
        $file && $file->delete();
      } catch (\io\IOException $ignored) {
        // Can't really do anything here
      }
      throw $e;
    }
  }

  #[@test]
  public function renameFile() {
    $this->conn->connect();

    try {
      $dir= $this->conn->rootDir()->getDir('htdocs');
      $file= $dir->file('name.txt')->uploadFrom(new MemoryInputStream($this->name));
      $file->rename('renamed.txt');
      $this->assertFalse($file->exists(), 'Origin file still exists');

      $file= $dir->file('renamed.txt');
      $this->assertTrue($file->exists(), 'Renamed file does not exist');
      $file->delete();
    } catch (\lang\Throwable $e) {

      // Unfortunately, try { } finally does not exist...
      try {
        $file && $file->delete();
      } catch (\io\IOException $ignored) {
        // Can't really do anything here
      }
      throw $e;
    }
  }

  #[@test]
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
      $file->delete();
    } catch (\lang\Throwable $e) {

      // Unfortunately, try { } finally does not exist...
      try {
        $file && $file->delete();
      } catch (\io\IOException $ignored) {
        // Can't really do anything here
      }
      throw $e;
    }
  }

  #[@test]
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

  #[@test]
  public function getInputStream() {
    $this->conn->connect();

    $s= $this->conn
      ->rootDir()
      ->getDir('htdocs')
      ->getFile('index.html')
      ->getInputStream()
    ;

    $this->assertEquals("<html/>\n", Streams::readAll($s));
  }

  #[@test]
  public function getInputStreams() {
    $this->conn->connect();
    $dir= $this->conn->rootDir()->getDir('htdocs');

    for ($i= 0; $i < 2; $i++) {
      try {
        $s= $dir->getFile('index.html')->getInputStream();
        $this->assertEquals("<html/>\n", Streams::readAll($s));
      } catch (IOException $e) {
        $this->fail('Round '.($i + 1), $e, null);
      }
    }
  }

  #[@test]
  public function getOutputStream() {
    $this->conn->connect();

    $file= $this->conn->rootDir()->getDir('htdocs')->file('name.txt');
    $s= $file->getOutputStream();
    try {
      $s->write($this->name);
      $s->close();

      $this->assertTrue($file->exists());
      $this->assertEquals(strlen($this->name), $file->getSize());
      $file->delete();
    } catch (Throwable $e) {

      // Unfortunately, try { } finally does not exist...
      try {
        $file && $file->delete();
      } catch (IOException $ignored) {
        // Can't really do anything here
      }
      throw $e;
    }
  }
}
