<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses('peer.ftp.FtpListParser');

  /**
   * Parses output from a FTP LIST command from Windows FTP daemons.
   *
   * @test     xp://net.xp_framework.unittest.peer.WindowsFtpListParserTest
   * @see      xp://peer.ftp.FtpListParser
   * @purpose  FTP LIST parser implementation
   */
  class WindowsFtpListParser extends Object implements FtpListParser {

    /**
     * Parse raw listing entry.
     *
     * @param   string raw a single line
     * @param   peer.ftp.FtpConnection connection
     * @param   string base default "/"
     * @param   util.Date ref default NULL
     * @return  peer.ftp.FtpEntry
     */
    public function entryFrom($raw, FtpConnection $conn= NULL, $base= '/', Date $ref= NULL) {
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
      $e->setUser(NULL);
      $e->setGroup(NULL);
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
?>
