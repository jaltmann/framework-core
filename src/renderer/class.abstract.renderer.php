<?php
namespace renderer;

abstract class AbstractRenderer
{
  protected $response = array();

  abstract public function render($args, $vars);

  public function __construct()
  {

  }

  public function registerRenderer($name, $func, $args)
  {
      throw new \Exception('function registerRenderer not implemented for this renderer');
  }
}
