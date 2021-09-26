<?php namespace peer\ftp;

use Iterator, ReturnTypeWillChange;

/**
 * Iterator for FtpEntryList which removes "." and ".." directories
 *
 * @deprecated
 * @see      php://language.oop5.iterations
 * @purpose  Iterator implementation
 */
class FtpListIterator implements Iterator {
  private 
    $i= 0, 
    $v= [], 
    $c= null, 
    $e= null,
    $b= '';

  /**
   * Constructor
   *
   * @param   string[] v
   * @param   peer.ftp.FtpConnection c
   * @param   string base default "/"
   */
  public function __construct($v, FtpConnection $c, $base= '/') { 
    $this->v= $v; 
    $this->c= $c; 
    $this->b= $base;
  }

  /**
   * Get current entry
   *
   * @return  peer.ftp.FtpEntry
   */
  #[ReturnTypeWillChange]
  public function current() {
    return $this->e; 
  }
  
  /**
   * Get current key
   *
   * @return  string
   */
  #[ReturnTypeWillChange]
  public function key() { 
    return $this->e->getName(); 
  }

  /**
   * Forward to next element
   *
   */    
  #[ReturnTypeWillChange]
  public function next() {
    // Intentionally empty, cursor is forwaded inside valid()
  }

  /**
   * Rewind iterator
   *
   */
  #[ReturnTypeWillChange]
  public function rewind() { 
    $this->i= 0; 
  }

  /**
   * Check for validity
   *
   * @return  bool
   */
  #[ReturnTypeWillChange]
  public function valid() { 
    $dotdirs= [$this->b.'./', $this->b.'../'];
    do {
      if ($this->i >= sizeof($this->v)) return false;

      $this->e= $this->c->parser->entryFrom($this->v[$this->i], $this->c, $this->b);
      $this->i++; 
    } while (in_array($this->e->getName(), $dotdirs));

    return true; 
  }
}