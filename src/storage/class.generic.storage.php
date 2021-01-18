<?php
namespace storage;

class GenericStorage extends AbstractStorage
{
  protected $config = false;

  public function __construct($storage_name)
  {

    $storage = \util\Config::readConfig('storage');
    if (!isset($storage[$storage_name]))
    {
      throw new \Exception('Storage config for '.$storage_name.' not found');
    }

    $this->config = $storage[$storage_name];

    $this->connect();
  }

  public function __destruct()
  {
  }

  protected function connect() {}
  public function getPrefix() {}
  public function getConnection() {}
  public function query($stmt) {}
  public function insert_id() {}

}
