<?php namespace peer\ftp\server\storage;



/**
 * This interface describes objects that implement a single storage 
 * element for FTP servers.
 *
 * @see      xp://peer.ftp.server.storage.StorageEntry
 * @purpose  Storage
 */
interface StorageCollection extends StorageEntry {

  /**
   * Retrieves a list of elements
   *
   * @return  peer.ftp.server.storage.StorageEntry[]
   */
  public function elements();

}