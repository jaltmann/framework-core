<?php
namespace storage\command;

abstract class Update extends AbstractCommand
{
  protected $limit = false;
  protected $filters = array();

  protected $values = array();

  public function set($entity, $value, $type = 'value') //function
  {
    $this->values[$entity] = array('val' => $value, 'type' => $type);
    return $this;
  }

  public function filter($entity, $value, $type = 'eq', $operator = 'AND')
  {
    $this->filters[] = array('entity' => $entity, 'value' => $value, 'type' => $type, 'operator' => $operator);

    return $this;
  }

  public function limit($limit)
  {
    $this->limit = $limit;
    return $this;
  }

}
