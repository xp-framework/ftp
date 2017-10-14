<?php namespace peer\ftp\collections;

use peer\ftp\FtpDir;
use io\collections\IOCollection;


/**
 * FTP collection
 *
 * @test     xp://net.xp_framework.unittest.peer.ftp.FtpCollectionsTest
 * @purpose  IOCollection implementation
 */
class FtpCollection implements IOCollection {
  protected 
    $dir    = null,
    $origin = null;

  private 
    $it     = null;

  private static $INVALID;

  static function __static() {
    self::$INVALID= newinstance('Iterator', [], '{
      public function rewind() { throw new \lang\IllegalStateException("Collection needs to be opened first"); }
      public function key() { return null; }
      public function current() { return null; }
      public function next() { return null; }
      public function valid() { return false; }
    }');
  }

  /**
   * Constructor
   *
   * @param   peer.ftp.FtpDir dir
   */
  public function __construct(FtpDir $dir) {
    $this->dir= $dir;
    $this->it= self::$INVALID;
  }

  /**
   * Returns this element's name
   *
   * @return  string
   */
  public function getName() {
    return basename($this->dir->getName());
  }

  /**
   * Returns this element's URI
   *
   * @return  string
   */
  public function getURI() {
    return $this->dir->getName();
  }
  
  /**
   * Open this collection
   *
   */
  public function open() { 
    $this->it= $this->dir->entries()->getIterator();
  }

  /**
   * Rewind this collection (reset internal pointer to beginning of list)
   *
   */
  public function rewind() { 
    $this->it->rewind();
  }

  /**
   * Retrieve next element in collection. Return NULL if no more entries
   * are available
   *
   * @return  io.collection.IOElement
   */
  public function next() {
    if (!$this->it->valid()) return null;

    $entry= $this->it->current();
    if ($entry instanceof FtpDir) {
      $next= new FtpCollection($entry);
    } else {
      $next= new FtpElement($entry);
    }
    $next->setOrigin($this);
    return $next;
  }

  /**
   * Close this collection
   *
   */
  public function close() { 
    $this->it= self::$INVALID;
  }

  /**
   * Retrieve this element's size in bytes
   *
   * @return  int
   */
  public function getSize() { 
    return $this->dir->getSize();
  }

  /**
   * Retrieve this element's created date and time
   *
   * @return  util.Date
   */
  public function createdAt() {
    return $this->dir->getDate();
  }

  /**
   * Retrieve this element's last-accessed date and time
   *
   * @return  util.Date
   */
  public function lastAccessed() {
    return $this->dir->getDate();
  }

  /**
   * Retrieve this element's last-modified date and time
   *
   * @return  util.Date
   */
  public function lastModified() {
    return $this->dir->lastModified();
  }

  /**
   * Creates a string representation of this object
   *
   * @return  string
   */
  public function toString() { 
    return nameof($this).'(->'.$this->dir->toString().')';
  }

  /**
   * Gets origin of this element
   *
   * @return  io.collections.IOCollection
   */
  public function getOrigin() {
    return $this->origin;
  }

  /**
   * Sets origin of this element
   *
   * @param   io.collections.IOCollection
   */
  public function setOrigin(IOCollection $origin) {
    $this->origin= $origin;
  }

  /**
   * Gets input stream to read from this element
   *
   * @return  io.streams.InputStream
   * @throws  io.IOException
   */
  public function getInputStream() {
    throw new \io\IOException('Cannot read from a directory');
  }

  /**
   * Gets output stream to read from this element
   *
   * @return  io.streams.OutputStream
   * @throws  io.IOException
   */
  public function getOutputStream() {
    throw new \io\IOException('Cannot write to a directory');
  }
} 
