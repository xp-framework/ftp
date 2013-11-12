<?php namespace peer\ftp\server\interceptor;
 


/**
 * Interceptor
 *
 * @purpose  Interceptor
 */
class EntrynameInterceptor extends DefaultInterceptor {
  public $regexp= null;

  /**
   * Constructor
   *
   * @param string regexp Regular expression to match entry name
   */
  public function __construct($regexp) {
    $this->regexp= $regexp;
  }

  /**
   * Checks if the entry name is valid (ends with special filename
   * extension (e.g. .gif, .jpg)
   *
   * @param   string name The entry name
   * @return  bool
   */
  public function validFilename($name) {
    return preg_match($this->regexp, $name);
  }

  /**
   * Invoked when an entry is created
   * 
   * @param   peer.ftp.server.FtpSession
   * @param   peer.ftp.server.storage.StorageEntry
   * @return  bool
   */
  public function onCreate($session, $entry) {
    if (is('peer.ftp.server.storage.StorageCollection', $entry)) return;
  
    if (!$this->validFilename($entry->getFilename())) {
      throw new \lang\IllegalAccessException('Invalid filename');
    }
  }

  /**
   * Invoked when an entry is renamed
   * 
   * @param   peer.ftp.server.FtpSession
   * @param   peer.ftp.server.storage.StorageEntry
   * @return  bool
   */
  public function onRename($session, $entry) {
    if (is('peer.ftp.server.storage.StorageCollection', $entry)) return;

    if (!$this->validFilename($entry->getFilename())) {
      throw new \lang\IllegalAccessException('Invalid filename');
    }
  }
}
