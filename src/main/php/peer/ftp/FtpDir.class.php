<?php namespace peer\ftp;

use io\FileNotFoundException;
use io\IOException;
use peer\ProtocolException;


/**
 * FTP directory
 *
 * @see      xp://peer.ftp.FtpConnection#rootDir
 * @purpose  FtpEntry implementation
 */
class FtpDir extends FtpEntry {
  public
    $entries  = null,
    $_offset  = 0;

  /**
   * Constructor
   *
   * @param   string name
   * @param   peer.ftp.FtpConnection connection
   */
  public function __construct($name, FtpConnection $connection) {
    $normalized= '/'.trim($name, '/').'/';
    parent::__construct('//' === $normalized ? '/' : $normalized, $connection);
  }
  
  /**
   * Returns a list of entries
   *
   * @see     https://tools.ietf.org/html/rfc3659#page-23
   * @return  peer.ftp.FtpEntryList
   * @throws  io.IOException in case of an I/O error
   */
  public function entries() {
    $transfer= $this->connection->transferSocket();

    $r= $this->connection->sendCommand('MLSD %s', $this->name);
    sscanf($r[0], "%d %[^\r\n]", $code, $message);

    // Expect "150 Opening ASCII mode data connection for MLSD"
    if (150 === $code) {
      $list= [];
      while ($line= $transfer->readLine()) {
        $list[]= $line;
      }
      $transfer->close();

      $r= $this->connection->getResponse();
      sscanf($r[0], "%d %[^\r\n]", $code, $message);

      // Expect "226 Transfer complete"
      if (226 === $code) {
        return new FtpEntryList($list, $this->connection, $this->name);
      }
    } else {
      $transfer->close();
    }

    throw new IOException('Cannot list "'.$this->name.'": '.$code.' '.$message);
  }

  /**
   * Delete this entry
   *
   * @throws  io.IOException in case of an I/O error
   */
  public function delete() {
    $this->connection->expect($this->connection->sendCommand('RMD %s', $this->name), [250]);
  }
  
  /**
   * Find an entry
   *
   * @param   string name
   * @return  peer.ftp.FtpEntry entry or NULL if nothing was found
   * @throws  io.IOException in case listing fails
   * @throws  peer.ProtocolException in case listing yields an unexpected result
   */
  protected function findEntry($name) {
    if (null === ($list= $this->connection->listingOf($this->name.$name, '-ald'))) {
      return null;      // Not found
    }

    // If we get more than one result and the first result ends with a 
    // dot, the server ignored the "-d" option and listed the directory's 
    // contents instead. In this case, replace the "." by the directory
    // name. Otherwise, we don't expect more than one result!
    $entry= $list[0];
    if (($s= sizeof($list)) > 1) {
      if ('.' === $entry{strlen($entry)- 1}) {
        $entry= substr($entry, 0, -1).basename($name);
      } else {
        throw new ProtocolException('List "'.$this->name.$name.'" yielded '.$s.' result(s), expected: 1 ('.\xp::stringOf($list).')');
      }
    }
    
    // Calculate base
    $base= $this->name;
    if (false !== ($p= strrpos(rtrim($name, '/'), '/'))) {
      $base.= substr($name, 0, $p+ 1);
    }
    
    return $this->connection->parser->entryFrom($entry, $this->connection, $base);
  }

  /**
   * Checks whether a file by the given name exists in this
   * directory.
   *
   * @param   string name
   * @return  bool TRUE if the file exists, FALSE otherwise
   * @throws  lang.IllegalStateException in case the file exists but is a directory
   */
  public function hasFile($name) {
    if (!($e= $this->findEntry($name))) {
      return false;
    } else if ($e instanceof FtpDir) {
      throw new \lang\IllegalStateException('File "'.$name.'" is a directory');
    }
    return true;
  }

  /**
   * Returns an FtpFile instance representing a file in this
   * directory.
   *
   * @param   string name
   * @return  peer.ftp.FtpFile the instance
   * @throws  io.FileNotFoundException in case the file was not found
   * @throws  lang.IllegalStateException in case the file exists but is a directory
   */
  public function getFile($name) {
    if (!($e= $this->findEntry($name))) {
      throw new FileNotFoundException('File "'.$name.'" not found');
    } else if ($e instanceof FtpDir) {
      throw new \lang\IllegalStateException('File "'.$name.'" is a directory');
    }
    return $e;
  }

  /**
   * Creates a file in this directory and returns an FtpFile instance
   * representing it.
   *
   * @param   string name
   * @return  peer.ftp.FtpFile the instance
   * @throws  lang.IllegalStateException in case the file already exists
   */
  public function newFile($name) {
    if ($e= $this->findEntry($name)) {
      throw new \lang\IllegalStateException('File "'.$name.'" already exists ('.$e->toString().')');
    }
    return new FtpFile($this->name.$name, $this->connection);
  }

  /**
   * Returns an FtpFile instance representing a file in this
   * directory.
   *
   * Note: Same as getFile() but does not throw exceptions if the file
   * does not exist but will return an FtpFile in any case.
   *
   * @param   string name
   * @return  peer.ftp.FtpFile the instance
   * @throws  lang.IllegalStateException in case the file is a directory
   */
  public function file($name) {
    if (!($e= $this->findEntry($name))) {
      return new FtpFile($this->name.$name, $this->connection);
    } else if ($e instanceof FtpDir) {
      throw new \lang\IllegalStateException('File "'.$name.'" is a directory');
    }
    return $e;
  }

  /**
   * Checks whether a subdirectory by the given name exists in this
   * directory.
   *
   * @param   string name
   * @return  bool TRUE if the file exists, FALSE otherwise
   * @throws  lang.IllegalStateException in case the directory is a file
   */
  public function hasDir($name) {
    if (!($e= $this->findEntry($name))) {
      return false;
    } else if ($e instanceof FtpFile) {
      throw new \lang\IllegalStateException('Directory "'.$name.'" is a file');
    }
    return true;
  }

  /**
   * Returns an FtpDir instance representing a subdirectory in this
   * directory.
   *
   * @param   string name
   * @return  peer.ftp.FtpDir the instance
   * @throws  io.FileNotFoundException in case the directory was not found
   * @throws  lang.IllegalStateException in case the directory exists but is a file
   */
  public function getDir($name) {
    if (!($e= $this->findEntry($name))) {
      throw new FileNotFoundException('Directory "'.$name.'" not found');
    } else if ($e instanceof FtpFile) {
      throw new \lang\IllegalStateException('Directory "'.$name.'" is a file');
    }
    return $e;
  }
  
  /**
   * Create a new directory
   *
   * @param   string name
   * @throws  io.IOException if directory cannot be created
   * @throws  peer.ProtocolException in case the created directory cannot be located or is a file
   */
  protected function makeDir($name) {
    $this->connection->expect($this->connection->sendCommand('MKD %s', $this->name.$name), [257]);
    
    if (!($created= $this->findEntry($name))) {
      throw new ProtocolException('MKDIR "'.$name.'" succeeded but could not find created directory afterwards');
    } else if (!$created instanceof FtpDir) {
      throw new ProtocolException('MKDIR "'.$name.'" succeeded but directory listing reveals a file');
    }
    return $created;
  }

  /**
   * Creates a subdirectory in this directory and returns an FtpDir 
   * instance representing it.
   *
   * @param   string name
   * @return  peer.ftp.FtpDir the created instance
   * @throws  lang.IllegalStateException in case the directory already exists
   * @throws  io.IOException in case the directory could not be created
   */
  public function newDir($name) {
    if ($e= $this->findEntry($name)) {
      throw new \lang\IllegalStateException('Directory "'.$name.'" already exists ('.$e->toString().')');
    }

    return $this->makeDir($name);
  }

  /**
   * Returns an FtpDir instance representing a subdirectory in this
   * directory.
   *
   * Note: Same as getDir() but does not throw exceptions if the 
   * directory does not exist but will create it and thus return an 
   * FtpDir in any case.
   *
   * @param   string name
   * @return  peer.ftp.FtpDir the instance
   * @throws  lang.IllegalStateException in case the directory exists and is a file
   * @throws  io.IOException in case the directory could not be created
   */
  public function dir($name) {
    if (!($e= $this->findEntry($name))) {
      return $this->makeDir($name);
    } else if ($e instanceof FtpFile) {
      throw new \lang\IllegalStateException('Directory "'.$name.'" is a file');
    }
    return $e;
  }

  /**
   * Get entries (iterative function)
   *
   * @deprecated Use entries() instead!   
   * @return  peer.ftp.FtpEntry FALSE to indicate EOL
   * @throws  peer.SocketException in case the directory could not be read
   */
  public function getEntry() {
    raise('lang.MethodNotImplementedException', 'Deprecated', 'FtpDir::getEntry');
  }
}
