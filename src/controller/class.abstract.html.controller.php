<?php
namespace controller;

abstract class AbstractHtmlController extends AbstractBaseController
{
	abstract function init();

  public function __construct($responseHandler)
  {

  	parent::__construct($responseHandler);
  	$this->getResponseHandler()->setRenderer('Html');

  	$this->init();
  }

}
