<?php namespace peer\ftp\server\storage;

define('ST_ELEMENT',     0x0000);
define('ST_COLLECTION',  0x0001);

/**
 * This interface describes objects that implement a storage for FTP
 * servers.
 */
interface Storage {
  const ELEMENT    = 0x0000;
  const COLLECTION = 0x0001;

  /**
   * Sets base
   *
   * @param   int clientId
   * @param   string uri
   * @return  string new base
   */
  public function setBase($clientId, $uri);
  
  /**
   * Retrieves base
   *
   * @param   int clientId
   * @return  string
   */
  public function getBase($clientId);

  /**
   * Helper method
   *
   * @param   string clientId
   * @param   string uri
   * @return  string
   */
  public function realname($clientId, $uri);

  /**
   * Looks up a element. Returns a StorageCollection, a StorageElement 
   * or NULL in case it nothing is found.
   *
   * @param   int clientId
   * @param   string uri
   * @return  peer.ftp.server.storage.StorageEntry
   */
  public function lookup($clientId, $uri);

  /**
   * Creates a new StorageEntry and return it
   *
   * @param   string clientId
   * @param   string uri
   * @param   int type one of the ST_* constants
   * @return  peer.ftp.server.storage.StorageEntry
   */
  public function createEntry($clientId, $uri, $type);
  
  /**
   * Creates a new StorageEntry and return it
   *
   * @param   int clientId
   * @param   string uri
   * @param   int type one of the ST_* constants
   * @return  peer.ftp.server.storage.StorageEntry
   */
  public function create($clientId, $uri, $type);

}