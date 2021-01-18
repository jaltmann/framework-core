<?php
namespace storage\command;

abstract class Delete extends AbstractCommand
{
  protected $filters = array();

  public function filter($entity, $value, $type = 'eq', $operator = 'AND')
  {
    $this->filters[] = array('entity' => $entity, 'value' => $value, 'type' => $type, 'operator' => $operator);

    return $this;
  }

}
