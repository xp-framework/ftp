<?php namespace peer\ftp;
 
use lang\IllegalArgumentException;
use peer\{AuthenticationException, ProtocolException, ConnectException, SSLSocket, Socket, SocketException, URL};
use util\log\Traceable;

/**
 * FTP client
 *
 * Usage example:
 * ```
 * $c= create(new FtpConnection('ftp://user:pass@example.com/'))->connect();
 * 
 * // Retrieve root directory's listing
 * Console::writeLine($c->rootDir()->entries());
 *
 * $c->close();
 * ```
 *
 * @test  net.xp_framework.unittest.peer.ftp.FtpConnectionTest
 * @test  net.xp_framework.unittest.peer.ftp.IntegrationTest
 * @see   https://datatracker.ietf.org/doc/html/rfc959
 */
class FtpConnection implements Traceable {
  protected
    $url      = null,
    $root     = null,
    $passive  = false,
    $cat      = null;

  public
    $parser   = null,
    $socket   = null;

  /**
   * Constructor. Accepts a DSN of the following form:
   * `{scheme}://[{user}:{password}@]{host}[:{port}]/[?{options}]`
   *
   * Scheme is one of the following:
   * - ftp (default)
   * - ftps (with SSL)
   *
   * Note: SSL connect is only available if OpenSSL support is enabled 
   * into your version of PHP.
   *
   * Options include:
   * - timeout - integer value indicating connection timeout in seconds, default: 4
   * - passive - boolean value controlling whether to use passive mode or not, default: true
   *
   * @param  string|peer.URL $dsn
   * @throws lang.IllegalArgumentException if scheme is unsupported
   */
  public function __construct($dsn) {
    $this->url= $dsn instanceof URL ? $dsn : new URL($dsn);

    switch ($this->url->getScheme()) {
      case 'ftp':
        $this->socket= new Socket($this->url->getHost(), $this->url->getPort(21));
        break;

      case 'ftps':
        $this->socket= new SSLSocket($this->url->getHost(), $this->url->getPort(21));
        break;

      default:
        throw new IllegalArgumentException('Unsupported scheme "'.$this->url->getScheme().'"');
    }

    switch (strtolower($this->url->getParam('passive', 'true'))) {
      case 'true': case 'yes': case 'on': case '1':
        $this->setPassive(true);
        break;

      case 'false': case 'no': case 'off': case '0':
        $this->setPassive(false);
        break;

      default:
        throw new IllegalArgumentException('Unexpected value "'.$this->url->getParam('passive').'" for passive');
    }
  }

  /** @return string */
  public function user() { return $this->url->getUser(); }

  /** @return peer.SocketEndpoint */
  public function remoteEndpoint() { return $this->socket->remoteEndpoint(); }

  /** @return bool */
  public function passive() { return $this->passive; }

  /** @return double */
  public function timeout() { return (double)$this->url->getParam('timeout', 4); }

  /**
   * Connect (and log in, if necessary)
   *
   * @return  peer.ftp.FtpConnection this instance
   * @throws  peer.ConnectException in case there's an error during connecting
   * @throws  peer.AuthenticationException when authentication fails
   * @throws  peer.SocketException for general I/O failures
   */
  public function connect() {
    $this->socket->connect($this->timeout());
    
    // Read banner message, which may span several lines w/ status code 220
    do {
      sscanf($this->getResponse()[0], "%d%c%[^\r]", $status, $continue, $message);
      if (220 !== $status) {
        throw new ProtocolException(sprintf(
          'Unexpected response [%d:%s], expecting 220',
          $code,
          $message
        ));
      }
    } while ('-' === $continue);
    
    // User & password
    if (null !== ($user= $this->url->getUser())) {
      try {
        $this->expect($this->sendCommand('USER %s', $user), [331]);
        $this->expect($this->sendCommand('PASS %s', $this->url->getPassword()), [230]);
      } catch (ProtocolException $e) {
        $this->socket->close();
        throw new AuthenticationException(sprintf(
          'Authentication failed for %s@%s:%d (using password: %s): %s',
          $this->url->getUser(),
          $this->url->getHost(),
          $this->url->getPort(21),
          $this->url->getPassword() ? 'yes' : 'no',
          $e->getMessage()
        ), $this->url->getUser(), $this->url->getPassword());
      }
    }

    // Setup list parser
    $this->setupListParser();
    
    // Retrieve root directory
    sscanf($this->expect($this->sendCommand('PWD'), [257]), '"%[^"]"', $dir);
    $this->root= new FtpDir(strtr($dir, '\\', '/'), $this);

    return $this;
  }

  /**
   * Setup directory list parser
   *
   * @return void
   */
  protected function setupListParser() {
    $type= $this->expect($this->sendCommand('SYST'), [215]);
    if ('Windows_NT' === $type) {
      $this->parser= new WindowsFtpListParser();
    } else {
      $this->parser= new DefaultFtpListParser();
    }
  }

  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() {
    if ($this->socket) {
      try {
        $this->socket->eof() || $this->socket->write("QUIT\r\n");
      } catch (SocketException $ignored) {
        // Simply disconnect
      }
      $this->socket->close();
      $this->socket= null;
    }
    return true;
  }

  /**
   * Returns true if connection is established
   *
   * @return bool
   */
  public function isConnected() {
    return $this->socket !== null && $this->socket->isConnected();
  }

  /**
   * Retrieve transfer socket
   *
   * @return  peer.Socket
   */
  public function transferSocket() {
    $port= $this->expect($this->sendCommand('PASV'), [227]);
    $a= $p= [];
    sscanf($port, '%*[^(] (%d,%d,%d,%d,%d,%d)', $a[0], $a[1], $a[2], $a[3], $p[0], $p[1]);
      
    // Open transfer socket
    $transfer= new Socket(implode('.', $a), $p[0] * 256 + $p[1]);
    $transfer->connect();
    return $transfer;
  }

  /**
   * Enables or disables the passive ftp mode at runtime.
   *
   * @param   bool enable enable or disable passive mode
   * @return  void
   */
  public function setPassive($enable) {
    $this->passive= $enable;
  }

  /**
   * Get root directory
   *
   * @return  peer.ftp.FtpDir
   */
  public function rootDir() { return $this->root; }
  
  /**
   * Read response
   *
   * @return  string[]
   */
  public function getResponse() {
    $response= '';
    do {
      $response.= $this->socket->read();
      $eof= $this->socket->eof();
    } while (!$eof && !strstr($response, "\r\n"));

    // Detect EOF
    if ($eof) {
      $this->cat && $this->cat->debug('<<< (EOF)');
      $this->socket->close();
      throw new SocketException('Connection closed by remote host');
    }

    $this->cat && $this->cat->debug('<<<', $response);
    return explode("\n", rtrim($response, "\r\n"));
  }
  
  /**
   * Check if return code meets expected response
   *
   * @param   string[] response
   * @param   int[] codes expected
   * @return  string message
   * @throws  peer.ProtocolException in case expectancy is not met
   */
  public function expect($r, $codes) {
    sscanf($r[0], "%d %[^\r\n]", $code, $message);
    if (!in_array($code, $codes)) {
      $error= sprintf(
        'Unexpected response [%d:%s], expecting %s',
        $code,
        $message,
        1 == sizeof($codes) ? $codes[0] : 'one of ('.implode(', ', $codes).')'
      );
      throw new ProtocolException($error);
    }
    return $message;
  }

  /**
   * Retrieve a listing of a given directory
   *
   * @param   string name the directory's name
   * @param   string options default NULL
   * @return  string[] list or NULL if nothing can be found
   * @throws  io.IOException
   */
  public function listingOf($name, $options= null) {
    with ($transfer= $this->transferSocket()); {
      $r= $this->sendCommand('LIST %s%s', $options ? $options.' ' : '', $name);
      sscanf($r[0], "%d %[^\r\n]", $code, $message);
      if (550 === $code) {          // Precondition failed
        $transfer->close();
        return null;
      } else if (150 === $code) {   // Listing
        $list= [];
        while ($line= $transfer->readLine()) {
          $list[]= $line;
        }
        $transfer->close();
        $r= $this->getResponse();
        sscanf($r[0], "%d %[^\r\n]", $code, $message);
        if (450 === $code) {        // No such file or directory
          return null;
        } else {                    // Some FTP servers send an empty directory listing
          $this->expect($r, [226]);
          return $list ? $list : null;
        }
      } else {                      // Unexpected response
        $transfer->close();
        throw new \io\IOException('Listing '.$this->name.$name.' failed ('.$code.': '.$message.')');
      }
    }
  }

  /**
   * Sends a raw command to the FTP server and returns the server's
   * response (unparsed) as an array of strings.
   *
   * Accepts a command which will be handled as format-string for
   * further arbitrary arguments, e.g.:
   *
   * ```php
   * $c->sendCommand('CLNT %s', $clientName);
   * ```
   *
   * @param   string command
   * @param   string... args
   * @return  string[] result
   * @throws  peer.SocketException in case of an I/O error
   */
  public function sendCommand($command, ... $args) {
    if (null === $this->socket) {
      throw new SocketException('Not connected');
    }

    $cmd= $args ? vsprintf($command, $args) : $command;
    $this->cat && $this->cat->debug('>>>', $cmd);
    $this->socket->write($cmd."\r\n");
    return $this->getResponse();
  }

  /**
   * Set a trace for debugging
   *
   * @param   util.log.LogCategory cat
   */
  public function setTrace($cat) {
    $this->cat= $cat;
  }
}