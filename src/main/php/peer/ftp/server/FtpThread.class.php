<?php namespace peer\ftp\server;

use lang\Thread;
use lang\reflect\Proxy;
use util\log\Traceable;

/**
 * Server thread which does all of the accept()ing on the sockets.
 */
class FtpThread extends Thread implements Traceable {
  public
    $server               = null,
    $terminate            = false,
    $cat                  = null,
    $authenticatorHandler = null,
    $storageHandler       = null,
    $interceptors         = [],    
    $processOwner         = null,
    $processGroup         = null;

  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('server');
  }

  /**
   * Set server
   *
   * @param   peer.server.Server server
   */
  public function setServer($server) {
    $this->server= $server;
  }
  
  /**
   * Set a trace for debugging
   *
   * @param   util.log.LogCategory cat
   */
  public function setTrace($cat) { 
    $this->cat= $cat;
  }
  
  /**
   * Set an AuthenticationHandler
   *
   * @param   lang.reflect.InvokationHandler handler
   */
  public function setAuthenticatorHandler($handler) {
    $this->authenticatorHandler= $handler;
  }
  

  /**
   * Set a StorageHandler
   *
   * @param   lang.reflect.InvokationHandler handler
   */
  public function setStorageHandler($handler) {
    $this->storageHandler= $handler;
  }

  /**
   * Adds an conditional interceptor
   *
   * @param peer.ftp.server.interceptor.InterceptorCondition Condition
   * @param peer.ftp.server.interceptor.StorageActionInterceptor Interceptor
   */
  public function addInterceptorFor($conditions, $interceptor) {
    $this->interceptors[]= [$conditions, $interceptor];
  }
  
  /**
   * Adds a new interceptor
   *
   * @param peer.ftp.server.interceptor.StorageActionInterceptor Interceptor
   */
  public function addInterceptor($interceptor) {
    $this->addInterceptorFor([], $interceptor);
  }
  
  /**
   * Retrieve an instance of this thread
   *
   * @return  peer.ftp.server.FtpThread
   */
  public static function getInstance() {
    static $instance= null;

    if (!$instance) $instance= new FtpThread();
    return $instance;
  }

  /**
   * Set ProcessOwner
   *
   * @param   String processOwner
   */
  public function setProcessOwner($processOwner) {
    $this->processOwner= $processOwner;
  }

  /**
   * Get ProcessOwner
   *
   * @return  String
   */
  public function getProcessOwner() {
    return $this->processOwner;
  }

  /**
   * Set ProcessGroup
   *
   * @param   String processGroup
   */
  public function setProcessGroup($processGroup) {
    $this->processGroup= $processGroup;
  }

  /**
   * Get ProcessGroup
   *
   * @return  String
   */
  public function getProcessGroup() {
    return $this->processGroup;
  }

  /**
   * Runs the server. Loads the listener using XPClass::forName()
   * so that the class is loaded within the thread's process space
   * and will be recompiled whenever the thread is restarted.
   *
   * @throws  lang.XPException in case initializing the server fails
   * @throws  lang.SystemException in case setuid fails
   */
  public function run() {
    try {
      with ($class= \lang\XPClass::forName('peer.ftp.server.FtpProtocol'), $cl= \lang\ClassLoader::getDefault()); {
      
        // Add listener
        $this->server->setProtocol($proto= $class->newInstance(
          $storage= Proxy::newProxyInstance(
            $cl,
            [\lang\XPClass::forName('peer.ftp.server.storage.Storage')],
            $this->storageHandler
          ),
          Proxy::newProxyInstance(
            $cl,
            [\lang\XPClass::forName('security.auth.Authenticator')],
            $this->authenticatorHandler
          )
        ));
      }
      
      // Copy interceptors to connection listener
      $proto->interceptors= $this->interceptors;

      // Enable debugging      
      if ($this->cat) {
        $proto->setTrace($this->cat);
        $this->server instanceof Traceable && $this->server->setTrace($this->cat);
      }

      // Try to start the server
      $this->server->init();
    } catch (\lang\Throwable $e) {
      $this->server->shutdown();
      throw $e;
    }
    
    // Check if we should run child processes
    // with another uid/pid
    if (isset($this->processGroup)) {
      $group= posix_getgrnam($this->processGroup);
      $this->cat && $this->cat->debugf('Setting group to: %s (GID: %d)',
        $group['name'],
        $group['uid']
      );

      if (!posix_setgid($group['gid'])) throw new \lang\SystemException('Could not set GID');
    }

    if (isset($this->processOwner)) {
      $user= posix_getpwnam($this->processOwner);
      $this->cat && $this->cat->debugf('Setting user to: %s (UID: %d)',
        $user['name'],
        $user['uid']
      );
      if (!posix_setuid($user['uid'])) throw new \lang\SystemException('Could not set UID');
    }

    $this->server->service();
  }
} 