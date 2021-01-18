<?php
namespace renderer;

class HtmlRenderer extends AbstractRenderer
{
  public function __construct()
  {
    header('Content-Type: text/html; charset=UTF-8');
  }

  private function handleArguments($args)
  {
  }

  public function render($args, $vars)
  {
    $this->handleArguments($args);

    //inject controller specific scripts, styles etc.
    $dom = new \DOMDocument();
    $dom->loadHTML($html);

    $head = $dom->getElementsByTagName('head');
    if ($head->length > 0)
    {
      $head = $head->item(0);
      //TODO handle injects
    }

    $body = $dom->getElementsByTagName('body');
    if ($body->length > 0)
    {
      $body = $body->item(0);
      //TODO handle injects
    }

    //removing comments from html
    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//comment()') as $comment) {
      $comment->parentNode->removeChild($comment);
    }

    return $dom->saveHTML();
  }
}
