<?php
namespace controller;

abstract class AbstractJsonController extends AbstractBaseController
{
	abstract function init();

  public function __construct($responseHandler)
  {

  	parent::__construct($responseHandler);
  	$this->getResponseHandler()->setRenderer('Json');

  	$this->init();
  }
}
