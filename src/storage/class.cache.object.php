<?php
namespace storage;

class CacheObject
{
  private $entities = false; //differ between real and virtual //TODO perhaps needs a lot of memory
  private $obj = false;
  private $objSource = false;
  private $changed = false;
  private $flagNames = array();
  private $followReadOnly = true;
  private $uid = false;

  public function __construct($object, $entities, $objSource = 'get')
  {
    $this->objSource = $objSource;
    $this->entities = $entities;

    $this->obj = array();
    foreach ($object as $k => $v)
    {
    	//TODO refactor!
      $isset = $v != NULL;
      $vtype = isset($entities[$k]['type']) ? $entities[$k]['type'] : 'string';
      switch ($vtype)
      {
      	case 'json':
      	case 'mediumjson':
      		if ($isset && !is_array($v) && !is_object($v)) $v = \util\Json::getJson(trim($v));
      		break;

      	default:
      		break;
      }

      $this->obj[$k] = array('isset' => $isset, 'value' => $v);
      if ($v == NULL)
      {
        $this->obj[$k]['isnull'] = true;
      }
    }

    //getting flagnames
    foreach ($entities as $entityName => $entity)
    {
    	if ($entity['type'] == 'flag')
      {
        $keys = array_keys($entity['flags']);
        foreach ($keys as $flag)
        {
          $this->flagNames[] = $entityName.'_'.$flag;
        }
      }
    }

    if ($objSource == 'set')
    {
      $this->followReadOnly = false;
    }
  }

  public function getObj()
  {
  	return $this->obj;
  }

  public function getObject()
  {
  	//TODO handle flag fields!
    $ret = array();
    foreach ($this->obj as $k => $v)
    {
      if ($v['isset'])
      {
      	$val = $v['value'];
      	//TODO check and decode json
        $ret[$k] = $val;
      }

      if (isset($v['isnull']) && $v['isnull'])
      {
        $ret[$k] = NULL;
      }
    }
    return $ret;
  }

  public function getFlag($flagId)
  {
    //TODO errorhandling
    $flagParts = explode('_', $flagId);
    $flag = array_pop($flagParts);
    $entityName = implode('_', $flagParts);

    if (!isset($this->entities[$entityName]) || $this->entities[$entityName]['type'] != 'flag' || !isset($this->entities[$entityName]['flags'][$flag]))
    {
      return null;
    }

    $bitIdx = $this->entities[$entityName]['flags'][$flag]['idx'];

    return ($this->obj[$entityName]['value'] & (1 << $bitIdx)) > 0;
  }

  public function setFlag($flagId, $activate = true)
  {
    if (!in_array($flagId, $this->flagNames))
    {
      return false;
    }

    $flagParts = explode('_', $flagId);
    $flag = array_pop($flagParts);
    $entityName = implode('_', $flagParts);

    if (!isset($this->entities[$entityName]) || $this->entities[$entityName]['type'] != 'flag' || !isset($this->entities[$entityName]['flags'][$flag]))
    {
      return false;
    }

    $bitIdx = $this->entities[$entityName]['flags'][$flag]['idx'];

    if ($activate)
    {
      $this->obj[$entityName]['value'] = ($this->obj[$entityName]['value'] | (1<<$bitIdx));
    }
    else
    {
      $this->obj[$entityName]['value'] = ($this->obj[$entityName]['value'] & (~(1<<$bitIdx)));
    }

    $this->obj[$entityName]['isset'] = true;
    if (isset($this->obj[$entityName]['isnull']))
    {
      unset($this->obj[$entityName]['isnull']);
    }

    $this->obj[$entityName]['set'] = true;
    $this->changed = true;
    return true;
  }

  public function unsetFlag($flagId)
  {
    return $this->setFlag($flagId, false);
  }


  public function getFlagNames()
  {
    return $this->flagNames;
  }

  public function flags()
  {
    $flags = array();
    foreach ($this->flagNames as $flagName)
    {
      if ($this->getFlag($flagName))
      {
        $flags[] = $flagName;
      }
    }
    return $flags;
  }

  public function setChanged($v = true)
  {
    $this->changed = $v;
  }

  public function isChanged()
  {
    return $this->changed;
  }

  public function getObjectSource()
  {
    return $this->objSource;
  }

  //TODO create cache / object state new/changed/defined
  private function _get($entityName)
  {
    if (isset($this->entities[$entityName]))
    {
      //TODO handle NULL
      return $this->obj[$entityName]['value'];
    }

    return NULL;
  }

  public function uid($uid = false)
  {
    if ($this->uid === false && $uid !== false)
    {
      $this->uid = $uid;
      if (isset($this->entities['uid']))
      {
        $this->obj['uid'] = array('isset' => true, 'value' => $uid, 'set' => true);
      }
    }

    return $this->uid;
  }

  private function _set($entityName, $value)
  {
    if (isset($this->entities[$entityName]))
    {
      //don't set readonly entities
      if ($this->followReadOnly && isset($this->entities[$entityName]['readonly']) && $this->entities[$entityName]['readonly'])
      {
        die('Entity '.$entityName.' can not be written (readonly)'."\n");
        return false;
      }

      if (isset($this->entities[$entityName]['nullable']) && !$this->entities[$entityName]['nullable'] && $value == NULL)
      {
        die('Entity '.$entityName.' can not be NULL'."\n");
        return false;
      }

      $this->obj[$entityName] = array('isset' => true, 'value' => $value);
      if ($value == NULL)
      {
        $this->obj[$entityName]['isnull'] = true;
      }

      $this->obj[$entityName]['set'] = true;
      $this->changed = true;
      return true;
    }
    return false;
  }

  //implementing dynamic getter and setter
  public function __call($methodName, $params = null)
  {
    $orgMethodName = $methodName;
    $methodName = strtolower($methodName);
    $prefix = substr($methodName, 0, 3);

    if (in_array($prefix, array('set', 'get')))
    {
      $suffix = substr($methodName, 3);

      if (isset($this->entities[$suffix]))
      {
        switch ($prefix)
        {
          case 'get':
            return $this->_get($suffix);
            break;

          case 'set':
            //TODO check if params[0] is set
            return $this->_set($suffix, $params[0]);
            break;
        }
      }
    }

    throw new \Exception('Method '.$methodName.' does not exist');
  }

}
