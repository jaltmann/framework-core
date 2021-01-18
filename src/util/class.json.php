<?php
  namespace util;

  class Json
  {

    public static function getJson($string, $returnAsArray = true, $default = false)
    {
      $json = json_decode($string, $returnAsArray);

      //TODO logging
      switch(json_last_error())
      {
        case JSON_ERROR_NONE:
          return $json; //echo ' - Keine Fehler';
          break;
        case JSON_ERROR_DEPTH:  //  echo ' - Maximale Stacktiefe überschritten';
        case JSON_ERROR_STATE_MISMATCH:  //  echo ' - Unterlauf oder Nichtübereinstimmung der Modi';
        case JSON_ERROR_CTRL_CHAR:  //  echo ' - Unerwartetes Steuerzeichen gefunden';
        case JSON_ERROR_SYNTAX:  //  echo ' - Syntaxfehler, ungültiges JSON';
        case JSON_ERROR_UTF8:  //    echo ' - Missgestaltete UTF-8 Zeichen, möglicherweise fehlerhaft kodiert';
        default:
          return $default;
        break;
      }

      return $default;

    }

    public static function readJson($fn)
    {
      $string = file_get_contents($fn);
      return Json::getJson($string);
    }
  }
