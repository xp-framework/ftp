<?php namespace peer\ftp;

use io\streams\InputStream;
use io\{Channel, IOException};
use lang\IllegalArgumentException;

/**
 * Represents an upload
 *
 * @see   peer.ftp.FtpFile::uploadFrom()
 */
class FtpUpload extends FtpTransfer {
  protected $in;

  /**
   * Constructor
   *
   * @param  peer.ftp.FtpFile $remote
   * @param  io.streams.InputStream|io.Channel $source
   * @throws lang.IllegalArgumentException
   */
  public function __construct(FtpFile $remote, $source) {
    $this->remote= $remote;
    if ($source instanceof InputStream) {
      $this->in= $source;
    } else if ($source instanceof Channel) {
      $this->in= $source->in();
    } else {
      throw new IllegalArgumentException('Expected either an input stream or a channel, have '.typeof($source));
    }
  }

  /**
   * Creates a new FtpDownload instance without a remote file
   *
   * @see    peer.ftp.FtpFile::start()
   * @param  io.streams.InputStream|io.Channel $source
   * @return self
   */
  public static function from($in) {
    return new self(null, $in);
  }

  /**
   * Returns command to send
   *
   * @return  string
   */
  protected function getCommand() {
    return 'STOR';
  }

  /**
   * Retrieves this transfer's total size
   *
   * @param   int size
   */
  public function size() {
    return -1;
  }

  /**
   * Continues this transfer
   *
   * @throws  peer.SocketException in case this transfer fails
   * @throws  lang.IllegalStateException in case start() has not been called before
   */
  protected function doTransfer() {
    if ($this->in->available() <= 0) {
      $this->state= 2;
      $this->close();
      $this->listener && $this->listener->completed($this);
      return;
    }

    try {
      $chunk= $this->in->read(8192);
      $this->socket->write($chunk);
    } catch (IOException $e) {
      $this->listener && $this->listener->failed($this, $e);
      $this->close();
      throw $e;
    }

    $this->transferred+= strlen($chunk);
    $this->listener && $this->listener->transferred($this);
  }
  
  /**
   * Returns the origin of this transfer
   *
   * @return  io.streams.InputStream
   */
  public function inputStream() {
    return $this->in;
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'@('.$this->in->toString().' -> '.$this->remote->getName().')';
  }
}