<?php
namespace storage;

class GenericObject
{
  protected $name;
  protected $storage;

  protected $relation = array();

  public function __construct($name, $storage_def)
  {
    $class_id = 'storage_'.$storage_def;
    if (\core\Loader::getInstance()->getClass($class_id) === false)
    {
      \core\Loader::getInstance()->setClass($class_id, new mysqli\MysqliStorage($storage_def));
    }

    $this->storage = \core\Loader::getInstance()->getClass($class_id);

    $this->name = $name;
    $this->readRelation($name);
  }

  public function getRelation()
  {
    return $this->relation;
  }

  public function getRelationName($withPrefix = true)
  {
    $rn = $this->name;

    if ($withPrefix && $this->storage->getPrefix())
      $rn = $this->storage->getPrefix().'_'.$rn;

    return $rn;
  }

  private function readRelation($name)
  {
    $fn = sprintf('%s/%s.relation', PATHRELATIONS, $name);
    $json = \util\Json::readJson($fn);

    if ($json === false)
    {
      throw new \Exception('Wrong structured json data for config "' . $json . '" (' . json_last_error() . ': ' . json_last_error_msg() . ')');
    }

    $this->relation = $json;

    return true;
  }
}
