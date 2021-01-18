<?php

namespace renderer;

class JsonRenderer extends AbstractRenderer
{

  public function __construct()
  {
    header('Content-Type: application/json');
    $this->response["success"] = false;

    if (defined('DEVMODE') && DEVMODE)
    {
      $this->response["debug"] = array();
    }
  }

  public function render($args, $vars)
  {
    $this->response["success"] = isset($args['status_code']) && $args['status_code'] == 200;

    if (defined('DEVMODE') && DEVMODE)
    {
      if (isset($vars['messages']))
      {
        $type = 'debug';
        foreach ($vars['messages'] as $msg)
        {
          $this->response[$type][] = $msg;
        }

      }
    }

    if (isset($vars['messages']))
      unset($vars['messages']);

    if (isset($vars['error_responses']))
    {
      $this->response['error'] = $vars['error_responses'];
      unset($vars['error_responses']);
    }

    $this->response['data'] = $vars;

    return json_encode($this->response);
  }
}
