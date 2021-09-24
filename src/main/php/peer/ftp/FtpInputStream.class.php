<?php namespace peer\ftp;

use io\streams\InputStream;

/**
 * InputStream that reads from FTP files
 *
 * @see  peer.ftp.FtpFile::in()
 */
class FtpInputStream extends FtpTransferStream implements InputStream {

  /**
   * Returns command to send ("RETR")
   *
   * @return  string
   */
  protected function getCommand() {
    return 'RETR';
  }

  /**
   * Read a string
   *
   * @param   int limit default 8192
   * @return  string
   */
  public function read($limit= 8192) {
    if ($this->eof) return;

    $chunk= $this->socket->readBinary($limit);
    if ($this->socket->eof()) {
      $this->close();
    }
    
    return $chunk;
  }

  /**
   * Returns the number of bytes that can be read from this stream 
   * without blocking.
   *
   * @return int
   */
  public function available() {
    return $this->eof ? 0 : 1;
  }
}