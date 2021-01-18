<?php
namespace controller;

abstract class AbstractTextController extends AbstractBaseController
{
	abstract function init();

  public function __construct($responseHandler)
  {

  	parent::__construct($responseHandler);
  	$this->getResponseHandler()->setRenderer('Text');

  	$this->init();
  }
}
