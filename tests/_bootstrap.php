<?php
  error_reporting(E_ALL);
  ini_set('display_errors', true);
  define('LOCALENV', 'test');

  $root = realpath(dirname(__FILE__).'/..');

  define('PATHFRAMEWORK', $root.'/src');
  include_once(PATHFRAMEWORK.'/core/class.singleton.php');
  include_once(PATHFRAMEWORK.'/core/class.loader.php');

  define('PATHTESTS', $root.'/tests');

  define('PATHOBJECTS', PATHTESTS.'/classes/objects');
  define('PATHRELATIONS', PATHTESTS.'/structures/relations');
  define('PATHCONFIGS', PATHTESTS.'/structures/configs');
  //define('PATHCONTROLLER', PATHPRIVATE.'/projects/'.PROJECT.'/controller');
  //define('PATHHANDLER', PATHPRIVATE.'/projects/'.PROJECT.'/handler');
