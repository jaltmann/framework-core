<?php
namespace storage;

abstract class AbstractStorage
{
  abstract public function __construct($storage_name);

  public function __destruct()
  {
  }

  abstract protected function connect();
  abstract public function getPrefix();
  abstract public function getConnection();
  abstract public function query($stmt);
  abstract public function insert_id();

}
