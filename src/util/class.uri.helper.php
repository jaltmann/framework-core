<?php
namespace util;

class UriHelper
{
    public function normalizePath($path)
    {
        while(strpos($path, '//') !== false)
        {
            $path = str_replace('//', '/', $path);
        }
        
        return $path;
    }
    
    public static function getUrlByHost($host)
    {
      $clsRequester = new \util\Requester();
      
      $url = 'http://'.$host; //TODO handle https
      
      $redirects = $clsRequester->head($url);
      
      if ($redirects === false || count($redirects) == 0)
      {
        return false;
      }
      
      $result = array_pop($redirects);
      if ($result['response']['is_html'] === false)
      {
        return false;
      }
      
      return $result['response']['url'];
    }
    
    public static function verifyHost($host)
    {
      $host = mb_strtolower($host);
      $url = '//'.mb_strtolower(filter_var($host, FILTER_SANITIZE_URL));
    
      
      if ($host !== parse_url($url, PHP_URL_HOST))
      {
        return false;
      }
    
      $ip = gethostbyname($host);
      if ($ip == $host)
      {
        return false;
      }
    
      return $host;
    }
}
