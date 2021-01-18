<?php
namespace storage\MySQLi;

class MysqliInsert extends \storage\command\Insert
{
  private $replaceIfExist = false;

  private function getValueEnumeration($values)
  {
    $ret = array();
    foreach($values as $entity => $desc)
    {
      switch ($desc['type'])
      {
        case 'value':
          $ret[] = $this->escape($desc['val']);
          break;

        case 'func':
          $ret[] = $desc['val'];
          break;

        case 'null':
          $ret[] = 'NULL';
          break;

        default:
          die($desc['type'].' not implemented');
          break;
      }

      $ret[] = ',';
    }
    array_pop($ret);

    return $ret;
  }

  protected function _build()
  {
    if (count($this->values) == 0)
    {
      //die('missing values');
      return false;
    }

    $stmt = array();
    $stmt[] = 'INSERT';
    $stmt[] = 'INTO';
    //TODO prefix!

    $rn = $this->relation['name'];

    if ($this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    $stmt[] = $rn;
    $stmt[] = '(';
    $stmt[] = implode(',', array_keys($this->values));
    $stmt[] = ')';


    $stmt[] = 'VALUES';
    $stmt[] = '(';
    $stmt = array_merge($stmt, $this->getValueEnumeration($this->values));
    $stmt[] = ')';


    if ($this->replaceIfExist)
    {
      $stmt[] = 'ON DUPLICATE KEY UPDATE';
      $stmt = array_merge($stmt, $this->getValueEnumeration($this->values));
    }

    //echo 'Insert: '.implode(' ', $stmt)."\n";
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
