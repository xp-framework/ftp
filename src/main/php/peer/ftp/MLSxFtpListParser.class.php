<?php namespace peer\ftp;

use util\Date;
use util\DateUtil;

/**
 * Parses output from a FTP MLST command
 *
 * @test  xp://peer.ftp.unittest.MLSxFtpListParserTest
 * @see   xp://peer.ftp.FtpListParser
 */
class MLSxFtpListParser implements FtpListParser {

  /**
   * Parse raw listing entry.
   *
   * @see     https://tools.ietf.org/html/rfc3659#section-7.4
   * @param   string $raw a single line
   * @param   peer.ftp.FtpConnection $connection
   * @param   string $base default "/"
   * @param   util.Date $ref default NULL
   * @return  peer.ftp.FtpEntry
   */
  public function entryFrom($raw, FtpConnection $conn= null, $base= '/', Date $ref= null) {

    $facts= [];
    list($pairs, $pathname)= explode('; ', $raw, 2);
    $p= 0;
    do {
      sscanf(substr($pairs, $p), '%[^=]=%[^;];', $name, $value);
      $facts[$name]= $value;
      $p+= strlen($name) + strlen($value) + 2;
    } while ($p < strlen($pairs));

    if ('dir' === $facts['type'] || 'cdir' === $facts['type'] || 'pdir' === $facts['type']) {
      $e= new FtpDir($base.$pathname, $conn);
      $e->setNumlinks(1);
    } else {
      $e= new FtpFile($base.$pathname, $conn);
      $e->setNumlinks(0);
    }

    $e->setSize(isset($facts['size']) ? $facts['size'] : 0);

    // See https://tools.ietf.org/html/rfc3659#section-2.3, "the syntax of a time value":
    $e->setDate(Date::create(...sscanf($facts['modify'], '%4d%2d%2d%2d%2d%2d')));

    // See https://tools.ietf.org/html/rfc3659#section-7.5, "standard set of facts":
    $e->setUser(isset($facts['UNIX.owner']) ? $facts['UNIX.owner'] : null);
    $e->setGroup(isset($facts['UNIX.group']) ? $facts['UNIX.group'] : null);
    $e->setPermissions(isset($facts['UNIX.mode']) ? octdec($facts['UNIX.mode']) : null);

    return $e;
  }
} 
