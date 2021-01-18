<?php
namespace renderer;

abstract class AbstractImageRenderer extends AbstractRenderer
{
  private $img = false;

  public function __construct($imageType)
  {
    header('Content-Type: image/' . $imageType);

    $width = 800;
    $height = 800;
    $this->img = @imagecreatetruecolor($width, $height);
    imageantialias($this->img, true);
    imagecolorallocate($this->img, 255, 255, 255);

  }

  public function write($text, $x, $y, $color)
  {
    imagestring($this->img, 3, $x, $y, $text, $color);
  }

  public function getWidth()
  {
    return imagesx($this->img);
  }

  public function getHeight()
  {
    return imagesy($this->img);
  }

  public function getColor($r, $g, $b)
  {
    return imagecolorallocate($this->img, $r, $g, $b );
  }

  protected function renderImage()
  {
    imagepng($this->img);
    imagedestroy($this->img);
  }
}
