<?php namespace peer\ftp\server\interceptor;



/**
 * Default implementation of a StorageActionInterceptor which just
 * returns TRUE in any case.
 *
 * @purpose  Interceptor
 */
class DefaultInterceptor extends \lang\Object implements StorageActionInterceptor {

  /**
   * Invoked when chdir'ing into a directory
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onCwd($session, $entry) {
    return true;
  }

  /**
   * Invoked when an entry is created
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onCreate($session, $entry) {
    return true;
  }

  /**
   * Invoked when an entry has been stored
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onStored($session, $entry) {
    return true;
  }

  /**
   * Invoked when an entry is deleted
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onDelete($session, $entry) {
    return true;
  }

  /**
   * Invoked when an entry is read
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onRead($session, $entry) {
    return true;
  }

  /**
   * Invoked when an entry is renamed
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onRename($session, $entry) {
    return true;
  }

  /**
   * Invoked when permissions are changed for an entry
   * 
   * @param  peer.ftp.server.FtpSession
   * @param  peer.ftp.server.storage.StorageEntry
   * @return bool
   */
  public function onChangePermissions($session, $entry) {
    return true;
  }
} 
