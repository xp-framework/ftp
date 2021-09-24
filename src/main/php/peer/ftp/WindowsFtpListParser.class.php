<?php namespace peer\ftp;

use util\Date;

/**
 * Parses output from a FTP LIST command from Windows FTP daemons.
 *
 * @test  peer.ftp.unittest.WindowsFtpListParserTest
 * @see   peer.ftp.FtpListParser
 */
class WindowsFtpListParser implements FtpListParser {

  /**
   * Parse raw listing entry
   *
   * @param   string $raw a single line
   * @param   peer.ftp.FtpConnection $connection
   * @param   string $base default "/"
   * @param   util.Date $ref default NULL
   * @return  peer.ftp.FtpEntry
   */
  public function entryFrom($raw, FtpConnection $conn= null, $base= '/', Date $ref= null) {
    preg_match(
      '/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +(<DIR>)?([0-9]+)? +(.+)/',
      $raw,
      $result
    );

    if ($result[7]) {
      $e= new FtpDir($base.$result[9], $conn);
    } else {
      $e= new FtpFile($base.$result[9], $conn);
    }

    $e->setPermissions(0);
    $e->setNumlinks(0);
    $e->setUser(null);
    $e->setGroup(null);
    $e->setSize(intval($result[8]));
    $e->setDate(new Date(sprintf(
      '%02d/%02d/%02d %02d:%02d%02s', 
      $result[1], 
      $result[2], 
      $result[3], 
      $result[4], 
      $result[5], 
      $result[6]
    )));
    return $e;
  }
} 