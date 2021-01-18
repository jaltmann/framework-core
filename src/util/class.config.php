<?php
  namespace util;

  class Config
  {
    public static function readConfig($name)
    {
      $fn = sprintf('%s/%s/%s.json', PATHCONFIGS, LOCALENV, $name);
      if (!file_exists($fn))
      {
        die('Configuration file does not exists '.$fn);
      }

      $json = \util\Json::readJson($fn);
      if ($json === false)
      {
        die('Wrong structured json data for config "' . $name . '" (' . json_last_error() . ': ' . json_last_error_msg() . ')');
      }

      return $json;
    }
  }
