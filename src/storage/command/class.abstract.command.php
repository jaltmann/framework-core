<?php
namespace storage\command;

abstract class AbstractCommand
{
  protected $relation = false;
  protected $storage = false;
  protected abstract function _build();
  protected abstract function _escape($value);

  public function __construct($relation, $storage)
  {
    $this->relation = $relation;
    $this->storage = $storage;
    return $this;
  }

  protected function escape($value)
  {
    return $this->_escape($value);
  }

  public function build()
  {
    return $this->_build();
  }
}
