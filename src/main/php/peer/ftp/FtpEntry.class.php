<?php namespace peer\ftp;
 
use lang\Value;
use util\{Date, Objects};

/**
 * Base class for all FTP entries
 *
 * @see   xp://peer.ftp.FtpDir
 * @see   xp://peer.ftp.FtpFile
 * @test  xp://peer.ftp.unittest.FtpEntryListTest
 */
abstract class FtpEntry implements Value {
  protected
    $connection   = null,
    $name         = '',
    $permissions  = 0,
    $numlinks     = 0,
    $user         = '',
    $group        = '',
    $size         = 0,
    $date         = null;
  
  /**
   * Constructor
   *
   * @param   string name
   * @param   peer.ftp.FtpConnection connection
   */
  public function __construct($name, FtpConnection $connection) {
    $this->name= $name;
    $this->connection= $connection;
  }

  /** Returns whether this is a file */
  public function isFile(): bool { return false; }

  /** Returns whether this is a folder */
  public function isFolder(): bool { return false; }

  /** @return peer.ftp.FtpConnection */
  public function getConnection() { return $this->connection; }

  /**
   * Checks whether this entry exists.
   *
   * @return  bool TRUE if the file exists, FALSE otherwise
   * @throws  io.IOException in case of an I/O error
   */
  public function exists() {
    $r= $this->connection->sendCommand('SIZE %s', $this->name);
    sscanf($r[0], "%d %[^\r\n]", $code, $message);
    if (213 === $code) {
      return true;
    } else if (550 === $code) {
      return false;
    } else {
      throw new \peer\ProtocolException('SIZE: Unexpected response '.$code.': '.$message);
    }
  }

  /**
   * Rename this entry
   *
   * Notes on the new name:
   * <ul>
   *   <li>If the new name is fully qualified (starts with a "/"), 
   *       the file will be moved there.
   *   </li>
   *   <li>If the name is not qualified, the directory *this* file
   *       resides in will be prepended.
   *   </li>
   * </ul>
   *
   * @param   string to the new name
   * @throws  io.IOException in case of an I/O error
   */
  public function rename($to) {
    $target= ('/' === $to[0] ? $to : dirname($this->name).'/'.$to);
    try {
      $this->connection->expect($this->connection->sendCommand('RNFR %s', $this->name), [350]);
      $this->connection->expect($this->connection->sendCommand('RNTO %s', $target), [250]);
    } catch (\peer\ProtocolException $e) {
      throw new \io\IOException('Could not rename '.$this->name.' to '.$to.': '.$e->getMessage());
    }
  }

  /**
   * Move this entry to a new folder (without necessarily renaming it)
   *
   * @param   peer.ftp.FtpDir to the new location
   * @param   string name default NULL the new name - if omitted, will stay the same
   * @throws  io.IOException in case of an I/O error
   */
  public function moveTo(FtpDir $to, $name= null) {
    try {
      $this->connection->expect($this->connection->sendCommand('RNFR %s', $this->name), [350]);
      $this->connection->expect($this->connection->sendCommand(
        'RNTO %s%s', 
        $to->getName(), 
        $name ? $name : basename($this->name)
      ), [250]);
    } catch (\peer\ProtocolException $e) {
      throw new \io\IOException('Could not rename '.$this->name.' to '.$to->getName().': '.$e->getMessage());
    }
  }

  /**
   * Change this entry's permissions
   *
   * @param   int to the new permissions
   * @throws  io.IOException in case of an I/O error
   */
  public function changePermissions($to) {
    $this->connection->expect($this->connection->sendCommand('SITE CHMOD %s %d', $this->name, $to));
  }

  /**
   * Delete this entry
   *
   * @throws  io.IOException in case of an I/O error
   */
  public abstract function delete();

  /**
   * Set Permissions. Takes either a string or an integer as argument.
   * In case a string is passed, it should have the following form:
   *
   * <pre>
   *   rwxr-xr-x  # 755
   *   rw-r--r--  # 644
   * </pre>
   *
   * @param   var perm
   * @throws  lang.IllegalArgumentException
   */
  public function setPermissions($perm) {
    static $m= ['r' => 4, 'w' => 2, 'x' => 1, '-' => 0, 't' => 0];

    if (is_string($perm) && 9 === strlen($perm)) {
      $this->permissions= (
        ($m[$perm[0]] | $m[$perm[1]] | $m[$perm[2]]) * 100 +
        ($m[$perm[3]] | $m[$perm[4]] | $m[$perm[5]]) * 10 +
        ($m[$perm[6]] | $m[$perm[7]] | $m[$perm[8]])
      );
    } else if (is_int($perm)) {
      $this->permissions= $perm;
    } else {
      throw new \lang\IllegalArgumentException('Expected either a string(9) or int, have '.$perm);
    }
  }

  /**
   * Get Permissions
   *
   * @return  int
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * Set Numlinks
   *
   * @param   int numlinks
   */
  public function setNumlinks($numlinks) {
    $this->numlinks= $numlinks;
  }

  /**
   * Get Numlinks
   *
   * @return  int
   */
  public function getNumlinks() {
    return $this->numlinks;
  }

  /**
   * Set User
   *
   * @param   string user
   */
  public function setUser($user) {
    $this->user= $user;
  }

  /**
   * Get User
   *
   * @return  string
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Set Group
   *
   * @param   string group
   */
  public function setGroup($group) {
    $this->group= $group;
  }

  /**
   * Get Group
   *
   * @return  string
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * Set Size
   *
   * @param   int size
   */
  public function setSize($size) {
    $this->size= $size;
  }

  /**
   * Get Size
   *
   * @return  int
   */
  public function getSize() {
    return $this->size;
  }

  /**
   * Set Date
   *
   * @param   util.Date date
   */
  public function setDate(Date $date) {
    $this->date= $date;
  }

  /**
   * Get Date
   *
   * @return  util.Date
   */
  public function getDate() {
    return $this->date;
  }

  /**
   * Get last modified date. Uses the "MDTM" command internally.
   *
   * @return  util.Date or NULL if the server does not support this
   * @throws  io.IOException in case the connection is closed
   */
  public function lastModified() {
    $r= $this->connection->sendCommand('MDTM %s', $this->name);
    sscanf($r[0], "%d %[^\r\n]", $code, $message);

    if (213 === $code) {
      sscanf($message, '%4d%02d%02d%02d%02d%02d', $y, $m, $d, $h, $i, $s);  // YYYYMMDDhhmmss
      return Date::create($y, $m, $d, $h, $i, $s);
    } else if (550 === $code) {
      return null;
    } else {
      throw new \peer\ProtocolException('MDTM: Unexpected response '.$code.': '.$message);
    }
  }

  /** @param string $name */
  public function setName($name) { $this->name= $name; }

  /** @return string */
  public function getName() { return $this->name; }

  /** @return string */
  public function hashCode() { return md5($this->name); }
  
  /** @return string */
  public function toString() {
    return sprintf(
      "%s(name= %s) {\n".
      "  [permissions ] %d\n".
      "  [numlinks    ] %d\n".
      "  [user        ] %s\n".
      "  [group       ] %s\n".
      "  [size        ] %d\n".
      "  [date        ] %s\n".
      "}",
      nameof($this),
      $this->name,
      $this->permissions,
      $this->numlinks,
      $this->user,
      $this->group,
      $this->size,
      Objects::stringOf($this->date)
    );
  }

  /**
   * Comparison implementation
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->name, $value->name) : 1;
  }
}