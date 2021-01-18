<?php
namespace storage\MySQLi;

class MysqliBulkInsert extends \storage\command\Insert
{
  private $rows = array();
  private $keys = array();

  public function addRow()
  {
    if (count($this->values) > 0)
    {
      $this->rows[] = $this->values;
      $this->keys = array_unique(array_merge($this->keys, array_keys($this->values)));
    }

    $this->values = array();
  }

  public function free()
  {
    $this->keys = array();
    $this->rows = array();
    $this->values = array();
  }

  private function getValueByDesc($entityDesc)
  {
    switch ($entityDesc['type'])
    {
      case 'value':
        return $this->escape($entityDesc['val']);
        break;

      case 'func':
        return $entityDesc['val'];
        break;

      case 'null':
        return 'NULL';
        break;

      default:
        die($entityDesc['type'].' not implemented');
        break;
    }

    return 'NULL';
  }

  protected function _build()
  {
    $this->addRow();
    if (count($this->rows) == 0)
    {
      return false;
    }

    $stmt = array();
    $stmt[] = 'INSERT';
    $stmt[] = 'INTO';

    $rn = $this->relation['name'];

    if ($this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    $stmt[] = $rn;
    $stmt[] = '(';
    $stmt[] = implode(',', $this->keys);
    $stmt[] = ')';


    $stmt[] = 'VALUES';
    foreach ($this->rows as $columns)
    {
      $stmt[] = '(';
      foreach ($this->keys as $key)
      {
        $stmt[] = (!isset($columns[$key])) ? 'NULL' : $this->getValueByDesc($columns[$key]);
        $stmt[] = ',';
      }
      array_pop($stmt);
      $stmt[] = ')';
      $stmt[] = ',';
    }
    array_pop($stmt);

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
