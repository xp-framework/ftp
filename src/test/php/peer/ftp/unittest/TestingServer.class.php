<?php namespace peer\ftp\unittest;

use Throwable;
use peer\ServerSocket;
use peer\ftp\server\{Authentication, FtpProtocol};
use peer\server\AsyncServer;
use util\cmd\Console;
use util\log\Logging;

/**
 * FTP Server used by IntegrationTest. 
 *
 * Specifics
 * ---------
 * - Server listens on a free port @ 127.0.0.1</li>
 * - Authentication requires "test" / "test" as credentials</li>
 * - Storage is inside an "ftproot" subdirectory of this directory</li>
 * - Server can be shut down by issuing the "SHUTDOWN" command</li>
 * - On startup success, "+ Service (IP):(PORT)" is written to standard out</li>
 * - On shutdown, "+ Done" is written to standard out</li>
 * - On errors during any phase, "- " and the exception message are written</li>
 *
 * @see   xp://net.xp_framework.unittest.peer.ftp.IntegrationTest
 */
class TestingServer {

  /**
   * Start server
   *
   * @param   string[] args
   */
  public static function main(array $args) {
    $stor= new TestingStorage();
    $stor->add(new TestingCollection('/', $stor));
    $stor->add(new TestingCollection('/.trash', $stor));
    $stor->add(new TestingElement('/.trash/do-not-remove.txt', $stor));
    $stor->add(new TestingCollection('/htdocs', $stor));
    $stor->add(new TestingElement('/htdocs/file with whitespaces.html', $stor));
    $stor->add(new TestingElement('/htdocs/index.html', $stor, "<html/>\n"));
    $stor->add(new TestingCollection('/outer', $stor));
    $stor->add(new TestingCollection('/outer/inner', $stor));
    $stor->add(new TestingElement('/outer/inner/index.html', $stor));

    $auth= new class() implements Authentication {
      public function authenticate($user, $password) {
        return ('testtest' === $user.$password);
      }
    };

    $protocol= new class($stor, $auth) extends FtpProtocol {
      public function onShutdown($socket, $params) {
        $this->answer($socket, 200, 'Shutting down');
        $this->server->terminate= true;
      }
    };
    isset($args[0]) && $protocol->setTrace(Logging::all()->toFile($args[0]));

    $socket= new ServerSocket('127.0.0.1', 0);
    $s= new AsyncServer();
    try {
      $s->listen($socket, $protocol);
      Console::writeLinef('+ Service %s:%d', $socket->host, $socket->port);
      $s->service();
      Console::writeLine('+ Done');
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}