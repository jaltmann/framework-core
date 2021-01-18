<?php
namespace storage\command;

abstract class Select extends AbstractCommand
{
  protected $limit = false;
  protected $offset = false;
  protected $filters = array();
  protected $orderBy = array();
  protected $groupBy = array();
  protected $having = array();

  protected $entities = array();

  protected $flagDescriptor = false;
  protected $flagEntities = array();
  protected $flags = array();

  public function __construct($relation, $storage)
  {
    parent::__construct($relation, $storage);

    //generating flags
    foreach ($relation['entities'] as $entity_name => $entity_desc)
    {
      if ($entity_desc['type'] != 'flag')
      {
        continue;
      }

      if ($this->flagDescriptor === false)
      {
        $this->flagDescriptor = array();
      }

      foreach ($entity_desc['flags'] as $flag_name => $flag_desc)
      {
        $this->flagDescriptor[$entity_name.'_'.$flag_name] = $flag_desc;
        $this->flagDescriptor[$entity_name.'_'.$flag_name]['entity_name'] = $entity_name;
      }
    }

    return $this;
  }

  public function hasFlag($flag)
  {
    if ($this->flagDescriptor === false || !isset($this->flagDescriptor[$flag]))
    {
      throw new \Exception('Flag '.$flag.' is not defined');
    }

    $this->flags[$flag] = true;


    return $this;
  }

  public function hasNotFlag($flag)
  {
    if ($this->flagDescriptor === false || !isset($this->flagDescriptor[$flag]))
    {
      throw new \Exception('Flag '.$flag.' is not defined');
    }

    $this->flags[$flag] = false;

    return $this;
  }

  public function get($entity, $alias = null, $escape = true)
  {
    if (!isset($alias))
      $alias = $entity;

    $this->entities[] = array('entity' => $entity, 'alias' => $alias, 'escape' => $escape);
    return $this;
  }

  public function filter($entity, $value, $type = 'eq', $operator = 'AND')
  {
    $this->filters[] = array('entity' => $entity, 'value' => $value, 'type' => $type, 'operator' => $operator);

    return $this;
  }

  public function isNull($entity, $operator = 'AND')
  {
    $this->filters[] = array('entity' => $entity, 'type' => 'is_null', 'operator' => $operator);

    return $this;
  }

  public function offset($offset)
  {
    $this->offset = $offset;
    return $this;
  }

  public function handleFlag($entityName)
  {
    $this->flagEntities[] = array('entity' => $entityName, 'alias' => $entityName, 'escape' => false);
    return $this;
  }

  public function handleEncrypted(array $encrypted)
  {
    $this->encrypted = $encrypted;

    return $this;
  }

  public function limit($limit)
  {
    if (!is_int($limit) || $limit < 0)
    $this->limit = $limit;
    return $this;
  }

  protected function listToArray($list, $glue)
  {
    $arr = array();
    foreach($list as $value)
    {
      $arr[] = $value['escape'] ? '`' . $value['entity'] . '`' : $value['entity'];
      $arr[] = 'AS';
      $arr[] = $value['escape'] ? '`' . $value['alias'] . '`' : $value['alias'];
      $arr[] = $glue;
    }
    array_pop($arr);

    return implode(' ', $arr);
  }

  public function orderDesc($entity)
  {
    //TODO escape orderDesc
    //TODO check entity exists!
    $this->orderBy[] = $entity.' DESC';
    return $this;
  }

  public function orderAsc($entity)
  {
    //TODO escape orderAsc
    //TODO check entity exists!
    $this->orderBy[] = $entity.' ASC';
    return $this;
  }

  public function orderFlag($entity, $index)
  {
    $this->orderBy[] = $entity.' & '.$index.' DESC'; // TODO generic DESC
    return $this;
  }

  public function groupBy($groupBy)
  {
    $this->groupBy[] = $groupBy;
    return $this;
  }

  public function having($entity, $value, $type = 'eq')
  {
    $this->having[] = array('entity' => $entity, 'value' => $value, 'type' => $type);

    return $this;
  }

}
