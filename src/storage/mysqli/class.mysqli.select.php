<?php
namespace storage\MySQLi;

class MysqliSelect extends \storage\command\Select
{
  protected function _build()
  {
    $stmt = array();
    $stmt[] = 'SELECT';

    if (count($this->entities) > 0)
    {
      if (count($this->flagEntities) > 0 && count($this->groupBy) == 0)
      {
        //TODO unique $this->entities
        $this->entities = array_merge($this->entities, $this->flagEntities);
      }
      $stmt[] = $this->listToArray($this->entities, ',');
    }
    else
    {
      $stmt[] = '*';
    }

    $stmt[] = 'FROM';

    $rn = $this->relation['name'];

    if ($this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    $stmt[] = $rn.' `'.$this->relation['name'].'`';

    $hasWhere = false;
    if (count($this->filters) > 0)
    {
      if (!$hasWhere) $stmt[] = 'WHERE';
      $hasWhere = true;

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

          case 'not_eq':
            $stmt[] = '<>';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'gt':
            $stmt[] = '>';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'ge':
            $stmt[] = '>=';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'lt':
            $stmt[] = '<';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'le':
            $stmt[] = '<=';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'like':
            $stmt[] = 'LIKE';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'not_like':
            $stmt[] = 'NOT LIKE';
            $stmt[] = $this->escape($filter['value']);
            break;

          case 'is_null':
            $stmt[] = 'IS NULL';
            break;

          case 'is_not_null':
            $stmt[] = 'IS NOT NULL';
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
    }

    //Handling Flags as Bitmasks
    if (count($this->flags) > 0)
    {
      if (!$hasWhere) $stmt[] = 'WHERE';
      $hasWhere = true;

      foreach ($this->flags as $fName => $fValue)
      {
        $flagColumn = $this->flagDescriptor[$fName]['entity_name'];
        $idx = $this->flagDescriptor[$fName]['idx'];
        $bitMask = pow(2, $idx);

        $comparator = ($fValue === true) ? '=' : '!=';
        //<columnname> & <checkbit> (!=|=) <checkbit>
        $stmt[] = $flagColumn;
        $stmt[] = '&';
        $stmt[] = $bitMask;
        $stmt[] = $comparator;
        $stmt[] = $bitMask;

        $stmt[] = 'AND';
      }
    }

    if ($hasWhere) array_pop($stmt); //removing AND

    if (count($this->orderBy) > 0)
    {
      $stmt[] = 'ORDER BY';
      $stmt[] = implode(', ', $this->orderBy);
    }

    if (count($this->groupBy) > 0)
    {
      $stmt[] = 'GROUP BY';
      $stmt[] = implode(', ', $this->groupBy);

      $hasHaving = false;
      if ($this->having) {
        if (!$hasHaving) $stmt[] = 'HAVING';
        $hasHaving = true;
        foreach($this->having as $filter)
        {
          $stmt[] = $filter['entity'];
          switch ($filter['type']) {
            case 'eq':
              $stmt[] = '=';
              $stmt[] = $this->escape($filter['value']);
              break;
            case 'in':
              $stmt[] = 'in (';
              foreach ($filter['value'] as $v) {
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
          $stmt[] = 'AND';
        }
        array_pop($stmt);
      }
    }

    if ($this->limit !== false)
    {
      $stmt[] = 'LIMIT';
      $stmt[] = $this->limit;
    }

    if ($this->offset !== false)
    {
      $stmt[] = 'OFFSET';
      $stmt[] = $this->offset;
    }

    //echo implode(' ', $stmt)."\n";
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
