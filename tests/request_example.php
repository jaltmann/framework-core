<?php
function analyseXpath($query, $domXpath)
{
  echo 'Test mit query '.$query."\n";
  $xpathTitles = $domXpath->query($query);
  var_dump($xpathTitles);
}

include_once(dirname(__FILE__).'/_bootstrap.php');

$requester = new \util\Requester;

$response = $requester->get('https://jens-altmann.de');
//echo var_dump($response)."\n";
//$final_response = array_pop($response);
$html = $response[0]['response']['content'];


$domDocument = new DOMDocument();

@$domDocument->loadHTML($html);

$titleNodes = $domDocument->getElementsByTagName('title');

$noOfTitleTags = $titleNodes->length;

if ($noOfTitleTags != 1) {
  echo 'Anzahl der Title Tags ist unerwartet'."\n";
}

if ($noOfTitleTags == 1) {
  echo 'Der Titel der URL lautet: '.$titleNodes->item(0)->textContent."\n";
}

echo 'Ab hier beginnt die Analyse mit XPath'."\n";
/*--------XPATH-----------*/
$domXpath = new DOMXPath($domDocument);

$xpathTitles = $domXpath->query('//title');
//var_dump($xpathTitles);
$noOfTitleTags = $xpathTitles->length;

if ($noOfTitleTags != 1) {
  echo 'Anzahl der Title Tags ist unerwartet'."\n";
}

if ($noOfTitleTags == 1) {
  echo 'Der Titel der URL lautet: '.$xpathTitles->item(0)->textContent."\n";
}

$xpathSkills = $domXpath->query('//*[@id="menu-item-19"]/div/a/span');
//var_dump($xpathTitles);

foreach ($xpathSkills as $xpathSkill)
{
  echo 'Wert des Spans ist: '.$xpathSkill->textContent."\n";
}

/*-----------------------*/

$h1Nodes = $domDocument->getElementsByTagName('h1');
$noOfH1Tags = $h1Nodes->length;
if ($noOfH1Tags != 1) {
  echo 'Es existiert keine Überschrift!'."\n";
}

$socialGridWidget = $domDocument->getElementById('gridus_social_widget-1');

if (is_null($socialGridWidget))
{
  echo 'Element gridus_social_widget wurde nicht gefunden'."\n";
}
else {
  $className = $socialGridWidget->getAttribute('class');
  echo 'Folgende Classes wurden für das Element definiert: '.$className."\n";
}
