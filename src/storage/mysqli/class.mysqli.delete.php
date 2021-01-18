<?php
namespace storage\MySQLi;

class MysqliDelete extends \storage\command\Delete
{
  protected function _build()
  {
    $stmt = array();
    $stmt[] = 'DELETE';
    $stmt[] = 'FROM';
    $rn = $this->relation['name'];

    if ($this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    $stmt[] = $rn;

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
