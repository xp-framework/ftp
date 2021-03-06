<?php namespace peer\ftp\server\interceptor;

/**
 * Interface for Interceptor condition
 *
 * @purpose  Interceptor condition
 */
interface InterceptorCondition {

  /**
   * Checks the condition
   *
   * @param peer.ftp.server.FtpSession session
   * @param peer.ftp.server.storage.StorageEntry entry
   * @return bool
   */
  public function check($session, $entry);
}