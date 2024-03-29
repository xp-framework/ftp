<?php namespace peer\ftp;

use io\streams\OutputStream;

/**
 * OuputStream that writes to FTP files
 *
 * @see  peer.ftp.FtpFile::out()
 */
class FtpOutputStream extends FtpTransferStream implements OutputStream {

  /**
   * Returns command to send ("STOR")
   *
   * @return  string
   */
  protected function getCommand() {
    return 'STOR';
  }

  /**
   * Write a string
   *
   * @param   var arg
   */
  public function write($arg) {
    $this->socket->write($arg);
  }
  
  /**
   * Close this stream
   *
   * @return void
   */
  public function close() {
    parent::close();
    $this->file->refresh('streaming');
  }
  

  /**
   * Flush this buffer. A NOOP for this implementation - data is written
   * directly to the transfer
   *
   */
  public function flush() { 
    // NOOP
  }
}