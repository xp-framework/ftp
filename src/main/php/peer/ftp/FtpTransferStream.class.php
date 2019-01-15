<?php namespace peer\ftp;

use lang\Value;
use peer\Socket;

/**
 * Base class for in- and output streams
 *
 * @see   xp://peer.ftp.FtpOutputStream
 * @see   xp://peer.ftp.FtpIntputStream
 */
abstract class FtpTransferStream {
  protected
    $eof    = false,
    $file   = null,
    $socket = null;

  /**
   * Constructor
   *
   * @param   peer.ftp.FtpFile file
   */
  public function __construct(FtpFile $file) {
    with ($conn= $file->getConnection(), $cmd= $this->getCommand()); {

      // Always use binary mode
      // Check for "200 Type set to X"
      $conn->expect($conn->sendCommand('TYPE I'), [200]);

      // Always use passive mode, just to be sure
      // Check for "227 Entering Passive Mode (h1,h2,h3,h4,p1,p2)."
      $this->socket= $conn->transferSocket();
      
      // Begin transfer depending on the direction returned by getCommand()
      // Check for "150 Opening XXX mode data connection for ..."
      $conn->expect($conn->sendCommand($cmd.' '.$file->getName()), [150]);
    }
    $this->file= $file;
  }
  
  /**
   * Returns command to send (one of RETR, STOR)
   *
   * @return  string
   */
  protected abstract function getCommand();

  /**
   * Close this buffer.
   *
   */
  public function close() {
    if ($this->eof) return;   // Already closed
    $this->eof= true;
    $this->socket->close();
    
    // Check for "226 transfer complete"
    // Reset mode to ASCII
    with ($conn= $this->file->getConnection()); {
      $r= $conn->getResponse();
      $conn->expect($conn->sendCommand('TYPE A'), [200]);
      sscanf($r[0], "%d %[^\r\n]", $code, $message);
      if (226 != $code) {
        throw new \peer\ProtocolException('Transfer incomplete ('.$code.': '.$message.')');
      }
    }
  }

  /** @return string */
  public function hashCode() { return '>'.$this->file->hashCode(); }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->file->toString().'>'; }

  /**
   * Comparison implementation
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->file->compareTo($value->file) : 1;
  }

  /**
   * Destructor. Ensures transfer socket is closed
   *
   */
  public function __destruct() {
    $this->socket->isConnected() && $this->socket->close();
  }
}
