<?php namespace peer\ftp\unittest;

use lang\{Runtime, IllegalStateException};
use peer\{Socket, SocketException};
use test\Provider;
use test\execution\Context;

class StartServer implements Provider {
  private $server;
  private $process= null;
  public $connection= null;

  /**
   * Constructor
   *
   * @param string $server Server process main class
   */
  public function __construct($server) {
    $this->server= strtr($server, '\\', '.');
  }

  public function values(Context $context) {
    $this->process= Runtime::getInstance()->newInstance(null, 'class', $this->server, ['debug']);
    $this->process->in->close();

    // Check if startup succeeded
    $status= $this->process->out->readLine();
    if (2 !== sscanf($status, '+ Service %[0-9.]:%d', $host, $port)) {
      $this->shutdown();
      throw new IllegalStateException('Cannot start server: '.$status, null);
    }

    $this->connection= new Socket($host, $port);
    yield $this;
  }

  /** @return void */
  public function shutdown() {
    if (null === $this->process) return;

    try {
      $this->connection->write("SHUTDOWN\n");
    } catch (SocketException $ignored) {
      // ...
    }

    $this->process->err->close();
    $this->process->out->close();
    $this->process->terminate();
    $this->process= null;
  }
}