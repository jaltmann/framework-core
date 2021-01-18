<?php
namespace verifier;

use handler\ResponseHandler;

abstract class AbstractVerifier
{
    private $defaultRedirectionPath = '/';
    private $responseHandler;
    protected $params = false;

    public function __construct($responseHandler)
    {
        $this->responseHandler = $responseHandler;
    }

    abstract public function verify($arguments = array(), $route = array());

    public function onVerificationFailed()
    {
      $this->responseHandler->redirect($this->defaultRedirectionPath);
    }

    public function setParams($params)
    {
      $this->params = $params;
    }

    protected function getResponseHandler()
    {
      return $this->responseHandler;
    }
}
