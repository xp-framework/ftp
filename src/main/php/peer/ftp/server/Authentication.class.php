<?php namespace peer\ftp\server;

interface Authentication {

  /**
   * Returns whether authentication suceeded
   *
   * @param  string $user
   * @param  string $password
   * @return bool
   */
  public function authenticate($user, $password);
}