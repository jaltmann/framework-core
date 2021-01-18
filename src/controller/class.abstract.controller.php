<?php
namespace controller;

use handler\ResponseHandler;

abstract class AbstractController
{
  private $responseHandler = false;

	abstract public function execute($argsHandler);

  public function __construct($responseHandler)
  {
  	$this->responseHandler = $responseHandler;
  }

  protected function getResponseHandler()
  {
    return $this->responseHandler;
  }

}
