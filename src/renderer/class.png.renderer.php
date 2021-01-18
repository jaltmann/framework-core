<?php

namespace renderer;

class PngRenderer extends AbstractImageRenderer
{

  public function __construct()
  {
    parent::__construct('png');
  }

  public function render($args, $vars)
  {
    $this->renderImage();
  }
}
