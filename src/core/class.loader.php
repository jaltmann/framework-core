<?php
  namespace core;

  class Loader extends Singleton
  {
    private $classes = array();
    private $global = array();

    public function __construct()
    {
      spl_autoload_register(array($this, 'autoload_controller'));
      spl_autoload_register(array($this, 'autoload_objects'));
      spl_autoload_register(array($this, 'autoload_views'));
      spl_autoload_register(array($this, 'autoload_classes'));
      spl_autoload_register(array($this, 'autoload_api_handler'));
    }

    private function autoload($type, $type_path, $class_name, $file_prefix = false)
    {
      if ($file_prefix === false) $file_prefix = strtolower($type);

      $type_length = strlen($type);
      $suffix = strlen($class_name) >= $type_length ? substr($class_name, 0, $type_length) : false;
      if ($suffix !== $type)
      {
        return true;
      }

      $elems = explode('\\', substr($class_name, $type_length));
      $class = array_pop($elems);

      $path = '/'.trim($type_path.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $elems), '/');
      $class_file = $file_prefix.'.'.strtolower(preg_replace('/\B([A-Z])/', '.$1', $class)).'.php';

      $class_filename = $path.DIRECTORY_SEPARATOR.$class_file;
      if (!file_exists($class_filename))
      {
        throw new \Exception('error getting '.$type.' '.$class_filename);
      }

      require_once($class_filename);
      return false;

    }

    public function autoload_classes($class_name)
    {
      $isLoaded = $this->autoload('', PATHFRAMEWORK, $class_name, 'class');

      if (!$isLoaded && defined('PATHCLASSESPROJECT'))
      {
        $isLoaded = $this->autoload('', PATHCLASSESPROJECT, $class_name, 'class');
      }

      return $isLoaded;
    }

    public function autoload_objects($class_name)
    {
      if (!defined('PATHOBJECTS')) return true;
      return $this->autoload('Object', PATHOBJECTS, $class_name);
    }

    public function autoload_views($class_name)
    {
      if (!defined('PATHOBJECTS')) return true;
      return $this->autoload('View', PATHOBJECTS, $class_name);
    }

    public function autoload_controller($class_name)
    {
      if (!defined('PATHCONTROLLER')) return true;
      return $this->autoload('Controller', PATHCONTROLLER, $class_name);
    }

    public function autoload_api_handler($class_name)
    {
      if (!defined('PATHHANDLER')) return true;
      return $this->autoload('ApiHandler', PATHHANDLER, $class_name, 'handler.api');
    }

    public function getClass($name)
    {
      if (!isset($this->classes[$name]))
      {
        return false;
      }

      return $this->classes[$name];
    }

    public function setClass($name, $cls)
    {
      $this->classes[$name] = $cls;
      return true;
    }

    public function store($key, $value)
    {
      $this->global[$key] = $value;
      return true;
    }

    public function get($key, $default = false)
    {
      return isset($this->global[$key]) ? $this->global[$key] : $default;
    }

  }

  Loader::getInstance();
