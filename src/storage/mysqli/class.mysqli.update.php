<?php
namespace storage\MySQLi;

class MysqliUpdate extends \storage\command\Update
{
  protected function _build()
  {
    if (count($this->values) == 0)
    {
      //die('missing values');
      return false;
    }

    $stmt = array();
    $stmt[] = 'UPDATE';

    $rn = $this->relation['name'];

    if ($this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    $stmt[] = $rn;
    $stmt[] = 'SET';



    foreach($this->values as $entity => $desc)
    {
      $stmt[] = $entity;
      $stmt[] = '=';

      switch ($desc['type'])
      {
      	case 'value':
      	  $stmt[] = $this->escape($desc['val']);
      	  break;

      	case 'func':
      	  $stmt[] = $desc['val'];
      	  break;

        case 'null':
            $stmt[] = 'NULL';
            break;

      	default:
      	  die($desc['type'].' not implemented');
      	  break;
      }

      $stmt[] = ',';
    }
    array_pop($stmt);

    if (count($this->filters) > 0)
    {
      $stmt[] = 'WHERE';
      foreach($this->filters as $filter)
      {
        $stmt[] = $filter['entity'];

        switch ($filter['type'])
        {
          case 'eq':
            $stmt[] = '=';
            //TODO check type of value
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'in':
            $stmt[] = 'in';
            $stmt[] = '(';
            //TODO check value is set and is array
            foreach ($filter['value'] as $v)
            {
              $stmt[] = $this->escape($v);
              $stmt[] = ',';
            }
            array_pop($stmt);
            $stmt[] = ')';
            break;

          default:
            die($filter['type'].' not implemented');
            break;
        }

        $stmt[] = $filter['operator'];
      }
      array_pop($stmt);
    }

    if ($this->limit !== false)
    {
      $stmt[] = 'LIMIT';
      $stmt[] = $this->limit;
    }

    return implode(' ', $stmt);
  }

  protected function _escape($value)
  {
    if (is_int($value))
    {
      return $value;
    }

    return '\''.$this->storage->getConnection()->real_escape_string($value).'\'';
  }
}
