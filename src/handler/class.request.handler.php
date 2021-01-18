<?php
namespace handler;

class RequestHandler extends \core\Singleton
{
  private $conf = array('routes' => array());
  private $route = false;
  private $responseHandler;

  public function __construct()
  {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $short_url = trim($request_uri, '/');

    $this->responseHandler = ResponseHandler::getInstance();
  }

  private function register($name, $regexp, $controller, $verifier, $args = array())
  {
    $this->conf['routes'][$name] = array('regexp' => $regexp, 'controller' => $controller, 'verifier' => $verifier, 'args' => $args);
  }

  public function autoRegisterRoutes($configName)
  {
    $routes = \util\Config::readConfig($configName);
    foreach ($routes as $route_name => $route_describer)
    {
      $route_verifier = isset($route_describer['verify']) ? $route_describer['verify'] : false;
      $route_args = isset($route_describer['args']) ? $route_describer['args'] : false;
      $this->register($route_name, $route_describer['route'], $route_describer['controller'], $route_verifier, $route_args);
    }
  }

  public function handle()
  {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $parsed_url = parse_url($request_uri);
    $request_uri = $parsed_url['path'];

    $uri = '/'.trim($request_uri, '/');
    $uriHelper = new \util\UriHelper();
    $uri = $uriHelper->normalizePath($uri);

    foreach ($this->conf['routes'] as $rname => $rdef)
    {
      preg_match('~^'.$rdef['regexp'].'$~', $uri, $rargs);
      if (count($rargs) != 0)
      {
        $requestType = (php_sapi_name() == 'cli') ? 'cli' : (isset($_SERVER['REQUEST_METHOD']) ? mb_strtolower($_SERVER['REQUEST_METHOD']) : 'unk' );
        $argsHandler = new ArgsHandler($requestType);

        $argsHandler->setCustomArg('route');
        foreach ($rargs as $k => $v)
        {
          if (!is_numeric($k))
          {
            $argsHandler->setCustomArg('route', $k, $v);
          }
        }

        if (isset($parsed_url['query']))
        {
          $argsHandler->parseQuery($parsed_url['query']);
        }

        if (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], array('POST', 'PUT')))
        {
          $mime = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'text/plain';
          $length = isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : -1;
          //TODO handle length < 0
          $body = file_get_contents("php://input");

          $argsHandler->parseBody($mime, $body);
        }

        if ($rdef['args'])
        {
          foreach ($rdef['args'] as $k => $v)
          {
            $argsHandler->setCustomArg('default', $k, $v);
          }
        }

        if($rdef['verifier'])
        {
            foreach ($rdef['verifier'] as $verifier)
            {

                $arguments = array();

                $source = array();
                $method = 'arg';

                //TODO refactor!
                if($argsHandler->arg())
                {
                    $method = 'arg';
                    $source = $argsHandler->arg();
                }
                if($argsHandler->post())
                {
                    $method = 'post';
                    $source = $argsHandler->post();
                }

                foreach ($source as $value)
                {
                  $arguments[$value] = $argsHandler->$method($value);
                }

                $routes = array();
                $route_source = $argsHandler->route();
                foreach ($route_source as $value)
                {
                  $routes[$value] = $argsHandler->route($value);
                }

                $verifyObj = '\\verifier\\'.$verifier['class'].'Verifier';
                $verifyObj = new $verifyObj($this->responseHandler);
                if (isset($verifier['params']))
                {
                  $verifyObj->setParams($verifier['params']);
                }

                if(!$verifyObj->verify($arguments, $routes))
                {
                  $verifyObj->onVerificationFailed();
              }
            }
        }

        $clsController = $rdef['controller'];

        if (!class_exists($clsController, true))
        {
          throw new \Exception('Controller '.$clsController.' can not be found');
        }

        //Load Handler
        $cls = new $clsController($this->responseHandler);

        $cls->execute($argsHandler);

        return true;
      }
    }

    return false;
  }

  public function getPath()
  {
      return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  }

  public function respond($code = false, $message = false)
  {
    $this->responseHandler->end($code, $message);
  }
}

RequestHandler::getInstance();
