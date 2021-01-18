<?php
  namespace handler;

  class ResponseHandler extends \core\Singleton
  {
    private $messages = array();
    private $error_responses = array();
    private $render_arguments = array();
    private $vars = array();
    private $renderer = false;
    private $clsRenderer = false;
    private $previousErrorHandler = null;
    private $bufferOutput = false;
    private $disableRendering = false;

    public function __construct()
    {
      $this->renderer = 'Text';
      register_shutdown_function(array($this, 'shutdown_handler'));
      $this->previousErrorHandler = set_error_handler(array($this, 'error_handler'));
      if ($this->useOutputBuffer()) ob_start(array($this, 'output_handler'));
    }

    public function setArg($name, $value)
    {
      $this->render_arguments[$name] = $value;
    }

    public function assign($key, $value)
    {
      $this->vars[$key] = $value;
    }

    public function useOutputBuffer()
    {
      return $this->bufferOutput;
    }

    public function output_handler($msg)
    {
      if (strlen(trim($msg)) == 0 )
      {
        return false;
      }
      $this->addMessage('out', $msg);
      return true;
    }

    private function addMessage($type, $msg, $args = array())
    {
      $args['type'] = $type;
      $args['msg'] = $msg;
      $args['ts'] = time();
      $this->messages[] = $args;

      return true;
    }

    public function error_handler($error_code, $error_msg, $error_file, $error_line, $error_ctx = false, $type = 'syserror')
    {
      $this->addMessage($type, $error_msg, array('code' => $error_code, 'file' => $error_file, 'line' => $error_line));

      // log to PHPs default error output. Usually servers error.log or STD:ERR
      error_log("[code: ".$error_code."] ".$error_file.":".$error_line." | ".$error_msg);

      //TODO $this->end(550, $error_msg);
      return true;
    }

    public function getMessages($type = false)
    {
    	//TODO handle parameter $type
      if ($type !== false)
      {
        $messages = array();
        foreach ($this->messages as $message)
        {
          if ($message['type'] == $type)
          {
            $messages[] = $message;
          }
        }
        return $messages;
      }
    	return $this->messages;
    }

    public function shutdown_handler()
    {
      $error = error_get_last();

      if( $error !== NULL)
      {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];

        $this->setStatusCode(500);
        $this->error_handler($errno, $errstr, $errfile, $errline, false, 'fatal');
      }

      if ($this->useOutputBuffer()) ob_end_flush();

      echo $this->render();
    }

    public function getRenderer()
    {
      if ($this->clsRenderer !== false)
      {
        return $this->clsRenderer;
      }

      //init renderer
      $clsNameRenderer = '\\renderer\\'.$this->renderer.'Renderer';

      if (!class_exists($clsNameRenderer, true))
      {
        throw new \Exception('Renderer '.$clsNameRenderer.' can not be found');
      }
      $this->clsRenderer = new $clsNameRenderer();
      return $this->clsRenderer;
    }

    public function render()
    {
      if ($this->disableRendering) return '';

      $clsRenderer = $this->getRenderer();

      if (count($this->messages) > 0)
      {
        $this->vars['messages'] = $this->messages;
      }

      if (count($this->error_responses) > 0)
      {
        $this->vars['error_responses'] = $this->error_responses;
      }

      $this->render_arguments['status_code'] = http_response_code();
      $body = $clsRenderer->render($this->render_arguments, $this->vars);
      return $body;
    }

    public function setStatusCode($statusCode)
    {
      http_response_code($statusCode);
    }

    public function setRenderer($renderer)
    {
      if ($this->clsRenderer !== false)
      {
        throw new \Exception('Renderer is already initialized ( '.$renderer.' )');
      }

      $this->renderer = $renderer;
    }

    public function debug($msg)
    {
      $this->addMessage('debug', $msg);
    }

    public function error($msg, $public = false)
    {
      $this->addMessage('error', $msg);
      if ($public === true)
      {
        $this->error_responses[] = $msg;
      }
    }

    public function addErrorResponse($msg)
    {
      $this->error($msg, true);
    }

    public function end($exitCode = false, $endMessage = false)
    {
      if ($exitCode !== false)
      {
        $this->setStatusCode($exitCode);
      }

      if ($endMessage !== false)
      {
      	$this->error($endMessage);
      }

      exit;
    }

    public function redirect($url, $permanent = false, $allowMethodChange = true)
    {
      $this->disableRendering = true;
      $statusCode = $permanent ? ($allowMethodChange ? 301 : 308) : ($allowMethodChange ? 302 : 307); //handling also 307 and 308

      //sanitizing CRLF and other control characters
      $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);
      header('Location: '.$url);

      $this->setStatusCode($statusCode);
      exit;
    }

  }
