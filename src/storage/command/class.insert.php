<?php
namespace storage\command;

abstract class Insert extends AbstractCommand
{
  protected $values = array();

  public function set($entity, $value, $type = 'value') //function
  {
    $this->values[$entity] = array('val' => $value, 'type' => $type);
    return $this;
  }

}
