<?php namespace peer\ftp\unittest;

use lang\{IllegalStateException, Runtime, Throwable, XPClass};
use unittest\{TestClassAction, PrerequisitesNotMetError};

/**
 * Starts a server for integration tests
 */
class StartServer implements TestClassAction {
  protected $serverProcess;
  protected $mainClass;
  protected $connected;
  protected $shutdown;

  /**
   * Constructor
   *
   * @param string $mainClass Server process main class
   * @param string $connected Name of method to invoke once connected
   * @param string $shutdown Name of method to invoke to shut down
   * @param string[] $arguments Arguments to server process
   */
  public function __construct($mainClass, $connected, $shutdown, $arguments= []) {
    $this->mainClass= $mainClass;
    $this->connected= $connected;
    $this->shutdown= $shutdown;
    $this->arguments= $arguments;
  }

  /**
   * Starts server
   *
   * @param  lang.XPClass $c
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTestClass(XPClass $c) {

    // Start server process
    $this->serverProcess= Runtime::getInstance()->newInstance(null, 'class', $this->mainClass, $this->arguments);
    $this->serverProcess->in->close();

    // Check if startup succeeded
    $status= $this->serverProcess->out->readLine();
    if (1 != sscanf($status, '+ Service %[0-9.:]', $bindAddress)) {
      try {
        $this->afterTestClass($c);
      } catch (IllegalStateException $e) {
        $status.= $e->getMessage();
      }
      throw new PrerequisitesNotMetError('Cannot start server: '.$status, null);
    }

    $c->getMethod($this->connected)->invoke(null, [$bindAddress]);
  }

  /**
   * Shuts down server
   *
   * @param  lang.XPClass $c
   * @return void
   */
  public function afterTestClass(XPClass $c) {

    // Tell the server to shut down
    try {
      $c->getMethod($this->shutdown)->invoke(null, []);
    } catch (Throwable $ignored) {
      // Fall through, below should terminate the process anyway
    }

    $status= $this->serverProcess->out->readLine();
    if (!strlen($status) || '+' != $status[0]) {
      while ($l= $this->serverProcess->out->readLine()) {
        $status.= $l;
      }
      while ($l= $this->serverProcess->err->readLine()) {
        $status.= $l;
      }
      $this->serverProcess->close();
      throw new IllegalStateException($status);
    }
    $this->serverProcess->close();
  }
}