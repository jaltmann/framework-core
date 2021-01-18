<?php
namespace handler;

class ArgsHandler extends \core\Singleton
{
  private $args = false;
  private $type = false;

  public function __construct($requestType)
  {
    $this->args = array(
      'arg' => array(),
      'post' => false,
      'put' => false,
      'default' => false,
    );

    $this->type = $requestType;
  }

  public function getType()
  {
    return $this->type;
  }

  public function parseQuery($query_str)
  {
    $get = array();
    parse_str($query_str, $query);
    foreach ($query as $k => $v)
    {
      $get[$k] = $v;
    }

    if (count($get) > 0)
    {
      $this->args['arg'] = $get;
    }

    return true;
  }

  public function parseBody($mime, $body)
  {
    $arg = array();
    $mime = explode(';', $mime);
    $mime = array_shift($mime);
    switch ($mime)
    {
      case 'application/json':
        $json = \util\Json::getJson(trim($body));
        if ($json !== false)
        {
          $arg = $json;
        }
        break;

      case 'application/x-www-form-urlencoded':
        parse_str($body, $query);
        foreach ($query as $k => $v)
        {
          $arg[$k] = $v;
        }
        break;

      default:
        die('Unsorpported mime type of body '.$mime);
        return false;
    }

    if (count($arg) > 0)
    {
      $this->args[$this->type] = $arg;
    }
    return true;
  }

  public function setCustomArg($type, $key = false, $value = false)
  {
    if (!isset($this->args[$type]))
    {
      $this->args[$type] = array();
    }

    if ($key === false)
    {
      return true;
    }

    $this->args[$type][$key] = $value;
    return true;
  }

  public function exists($methodName, $argName)
  {
    return isset($this->args[$methodName]) && isset($this->args[$methodName][$argName]);
  }

  public function __call($methodName, $params = null)
  {
    $methodName = strtolower($methodName);

    if (isset($this->args[$methodName]))
    {
      if ($this->args[$methodName] === false)
      {
        return false;
      }

      if (count($params) == 0)
      {
        return array_keys($this->args[$methodName]);
      }

      $argName = $params[0];
      $default = NULL;
      if (isset($params[1]))
      {
        $default = $params[1];
      }

      if ($this->exists($methodName, $argName))
      {
        return $this->args[$methodName][$argName];
      }

      return $default;
    }

    throw new \Exception('Method '.$methodName.' does not exist');
  }
}
