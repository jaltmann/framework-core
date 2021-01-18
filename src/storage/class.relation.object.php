<?php
namespace storage;

class RelationObject extends GenericObject
{
  private $objectCache = array();

  public function __construct($name, $storage_def = 'default')
  {
    parent::__construct($name, $storage_def);
  }

  public function get($id)
  {
    if (isset($this->objectCache[$id]))
    {
      return $this->objectCache[$id];
    }

    return false;
  }

  protected function delete()
  {
    return (new mysqli\MysqliDelete($this->relation, $this->storage));
  }

  private function insert($replaceIfExists = false)
  {
    //$replaceIfExists not implemented yet
    $query = (new mysqli\MysqliInsert($this->relation, $this->storage));

    return $query;
  }

  private function bulkInsert()
  {
    $query = (new mysqli\MysqliBulkInsert($this->relation, $this->storage));
    return $query;
  }

  private function update()
  {
    $query = (new mysqli\MysqliUpdate($this->relation, $this->storage));

    return $query;
  }

  protected function select($entities = false)
  {
    $query = (new mysqli\MysqliSelect($this->relation, $this->storage));

    if ($entities !== false)
    {
      foreach ($entities as $entity)
      {
        $query = $query->get($entity);
      }
    }

    return $query;
  }

  protected function count()
  {
  	$query = (new mysqli\MysqliSelect($this->relation, $this->storage))->get('count(*)', 'count', false);

  	return $query;
  }

  protected function execute($query, $useCacheOnSelect = true)
  {
    $ids = array();
    $stmt = $query->build();
    $result = $this->storage->query($stmt);

    if (is_subclass_of($query, 'storage\command\Select'))
    {
      $rows = $this->storage->fetchRows($result, -1);

      if (!$useCacheOnSelect)
      {
        return $rows;
      }

      $pk = isset($this->relation['keys']['primary']) ? $this->relation['keys']['primary'] : false;
      foreach ($rows as $row)
      {
        $id = false;
        $uid = false;
        if ($pk)
        {
          $id = array();
          foreach ($pk as $entity)
          {
            $id[] = $row[$entity]; //TODO check if exists
          }
          $uid = implode(',', $id);
          $id = '#'.implode('#', $id);
        }
        else
        {
          $uid = uniqid();
          $id = '_'.$uid;

        }
        $this->objectCache[$id] = new CacheObject($row, $this->relation['entities'], 'get');
        $this->objectCache[$id]->uid($uid);
        $ids[] = $id;
      }

      return $ids;
    }

    return $result;
  }

  public function add($values = false, $id = false)
  {
    if ($id === false)
      $id = '-'.uniqid();

    $pk = isset($this->relation['keys']['primary']) ? $this->relation['keys']['primary'] : false;
    $row = array();
    foreach ($this->relation['entities'] as $entityName => $entity)
    {
      // $v = isset($entity['default']) ? $entity['default'] : NULL;
      if(isset($entity['default']))
      {
        $type = gettype($entity['default']);
        switch ($type)
        {
          case 'string':
            $v = $entity['default'];
            break;
          case 'array':
              if (isset($entity['default']['value']))
                $v = $entity['default']['value'];
              else
                $v = NULL;
              break;
          default:
            $v = NULL;
            break;
        }
      }
      else {
        $v = NULL;
      }

      if ($values !== false && isset($values[$entityName]))
      {
        $v = $values[$entityName];
      }
      $row[$entityName] = (is_array($v) || is_object($v)) ? json_encode($v) : $v;
    }

    $this->objectCache[$id] = new CacheObject($row, $this->relation['entities'], 'set');
    $this->objectCache[$id]->setChanged(true);

    return $id;
  }

  private function validateEntities($dobj, &$query)
  {
    foreach ($this->relation['entities'] as $entityName => $entity)
    {

      if (isset($entity['default']) && !isset($dobj[$entityName]))
      {
        $type = gettype($entity['default']);
        switch ($type) {

          case 'array':
            if (isset($entity['default']['type']) && isset($entity['default']['value']))
              $query->set($entityName, $entity['default']['value'], $entity['default']['type']);
            else
              $query->set($entityName, $entity['default'], 'value');
            break;
          case 'string':
          default:
            $query->set($entityName, $entity['default'], 'value');
            break;
        }
      }

      else if (isset($entity['nullable'])) //check if column can be nullable
      {
        //TODO implement!!!
        /*
        if ($entity['nullable'] === false && (!isset($dobj[$entityName]) || isset($dobj[$entityName]['isnull']) ))
        {
          //TODO handle
          die($entityName.' can not be null!');
        }
        */
      }

      //Length validation
      if (isset($entity['length']) && isset($dobj[$entityName]))
      {
        if (strlen($dobj[$entityName]['value']) > $entity['length'])
        {
          //TODO handle
          die('Value of entity '.$entityName.' ( '.$entity['length'].' ) will be truncated "'.$dobj[$entityName]['value'].'"'."\n");
        }
      }
    }

    return true;
  }

  public function store($filterIds = false)
  {
    if ($filterIds !== false && !is_array($filterIds) )
    {
      $filterIds = array($filterIds);
    }

    foreach ($this->objectCache as $id => $object)
    {
      if ($filterIds !== false && !in_array($id, $filterIds))
      {
        continue;
      }

      $query = false;
      $insert = false;
      switch ($object->getObjectSource())
      {
        case 'set':
          if ($object->isChanged())
          {

            if ($id < 0)
            {
              $query = $this->insert();
              $insert = true;
            }
            else
              $query = $this->update();

            $dobj = $object->getObj();
            foreach ($dobj as $k => $v)
            {
              if ($v['isset'])
              {
                $type = isset($v['isnull']) ? 'null' : 'value';
                $query->set($k, $v['value'], $type);
              }

            }

            $this->validateEntities($dobj, $query);
          }
          break;

        case 'get':
          if ($object->isChanged())
          {
            $query = $this->update();
            $pk = isset($this->relation['keys']['primary']) ? $this->relation['keys']['primary'] : false;
            if ($pk === false)
            {
              //TODO unique
              die('can not update without primary key');
            }

            foreach ($object->getObj() as $k => $v)
            {
              if (in_array($k, $pk)) //when pk --> where condition
              {
                $query->filter($k, $v['value']);
              }

              //check if field can be set
              if (isset($v['set']))
              {
                $type = isset($v['isnull']) ? 'null' : 'value';
                $query->set($k, $v['value'], $type);
              }

            }
          }
          break;

        default:
          die('ObjectSource '.$object->getObjectSource().' is not handled');
          break;
      }

      if ($query !== false)
      {
        //TODO get ids and move ids
        $success = $this->execute($query);
        if ($success === false)
        {
          return false; //TODO store error message and display in DEVMODE!
        }

        $object->setChanged(false);

        if ($insert)
        {
          $object->uid($this->storage->insert_id());
        }
      }

    }

    return true;
  }

  public function bulk($onlyInsert = true)
  {
    if ($onlyInsert !== true) die('bulk update not implemented yet');

    $query = $this->bulkInsert();
    foreach ($this->objectCache as $id => $object)
    {
      if ($object->isChanged() && $id < 0)
      {
        $query->addRow();
        $dobj = $object->getObj();
        foreach ($dobj as $k => $v)
        {
          if ($v['isset'])
          {
            $type = isset($v['isnull']) ? 'null' : 'value';
            $query->set($k, $v['value'], $type);
          }
        }
        $this->validateEntities($dobj, $query);
      }
    }

    $this->execute($query);
    $query->free();
  }

  public function free()
  {
    $this->objectCache = array();
    return true;
  }

  public function size()
  {
    return count($this->objectCache);
  }

  public function getFlagIndex($entity, $flag)
  {
      $attributes = get_object_vars($this);

      if (!isset($attributes['relation']['entities'][$entity]['flags'][$flag]['idx']))
        return false;

      return $attributes['relation']['entities'][$entity]['flags'][$flag]['idx'];
  }
}
