<?php
namespace util;


class StringHelper
{
 
  public static function random($length = 16, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
  {
    $charLen = strlen($chars);
    $str = '';
    for ($i = 0; $i < $length; $i++) 
    {
      $str .= $chars[rand(0, $charLen - 1)];
    }
    return $str;
  }
  
}
