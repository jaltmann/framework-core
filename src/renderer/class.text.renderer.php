<?php

namespace renderer;

class TextRenderer extends AbstractRenderer
{

  public function __construct()
  {
    if (php_sapi_name() != 'cli') //TODO global attribute see RequestHandler
    {
      header('Content-Type: text/plain');
    }
  }

  public function render($args, $vars)
  {
    //TODO printing vars

    if (defined('DEVMODE') && DEVMODE && isset($vars['messages']))
    {
      foreach ($vars['messages'] as $msg)
      {
        $msgType = isset($msg['type']) ? $msg['type'] : 'unknown';
        $msgBody = $msg['msg'];
        $line = date('Y-m-d H:i:s', $msg['ts']) . ' [' . strtoupper($msgType) . '] ' . $msgBody;
        if (isset($msg['file']))
        {
          $line .= ' { ' . $msg['file'];
          if (isset($msg['line']))
          {
            $line .= ' (line: ' . $msg['line'] . ')';
          }
          $line .= ' }';
        }
        echo $line . "\n";
      }
    }
  }
}
