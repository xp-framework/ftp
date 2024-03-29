<?php namespace peer\ftp;

use IteratorAggregate, Traversable;
use lang\Value;
use util\Objects;

/**
 * List of entries on an FTP server
 *
 * @test  peer.ftp.unittest.FtpEntryListTest
 * @see   peer.ftp.FtpDir::entries()
 */
class FtpEntryList implements Value, IteratorAggregate {
  protected $connection, $list, $base;

  /**
   * Constructor
   *
   * @param   string[] list
   * @param   peer.ftp.FtpConnection connection
   * @param   string base default "/"
   */
  public function __construct(array $list, FtpConnection $connection, $base= '/') {
    $this->list= $list;
    $this->connection= $connection;
    $this->base= $base;
  }
  
  /** Iterators over all entries */
  public function getIterator(): Traversable {
    $dotdirs= [$this->base.'./', $this->base.'../'];
    foreach ($this->list as $line) {
      $e= $this->connection->parser->entryFrom($line, $this->connection, $this->base);
      in_array($e->getName(), $dotdirs) || yield $e->getName() => $e;
    }
  }

  /**
   * Returns the number of elements in this list.
   *
   * @return  int
   */
  public function size() {
    return sizeof($this->list) - 2;     // XXX what happens if "." and ".." are not returned by the FTP server?
  }

  /**
   * Tests if this list has no elements.
   *
   * @return  bool
   */
  public function isEmpty() {
    return 0 === $this->size();
  }

  /**
   * Returns all elements in this list as an array.
   *
   * @return  peer.ftp.FtpEntry[] an array of all entries
   * @throws  lang.FormatException in case an entry cannot be parsed
   */
  public function asArray() {
    $dotdirs= [$this->base.'./', $this->base.'../'];
    foreach ($this->list as $line) {
      $e= $this->connection->parser->entryFrom($line, $this->connection, $this->base);
      in_array($e->getName(), $dotdirs) || $r[]= $e;
    }
    return $r;
  }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /** @return string */
  public function toString() {
    return nameof($this).'('.$this->size().' entries)@'.Objects::stringOf($this->list);
  }

  /**
   * Comparison implementation
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value === $this ? 0 : 1;
  }
}