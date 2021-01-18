<?php
include_once(dirname(__FILE__).'/_bootstrap.php');
//\handler\RequestHandler::getInstance();

$debug = true;
$purge = true;

$relations = array('sample_relation');
foreach ($relations as $relation)
{
  $tbl = new storage\mysqli\MysqliBuilder($relation, 'default');
  $tbl->setDebug($debug);
  if ($purge) $tbl->deleteTable();
  $tbl->prepareTable(false, false);
}
