<?php
namespace util;

/**
 * A php library for converting relative urls to absolute.
 * Website: https://github.com/monkeysuffrage/phpuri
 *
 * <pre>
 * echo phpUri::parse('https://www.google.com/')->join('foo');
 * //==> https://www.google.com/foo
 * </pre>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author P Guardiario <pguardiario@gmail.com>
 * @version 1.0
 */

/**
 * phpUri
 */
 class Uri{

  /**
   * http(s)://
   * @var string
   */
  public $scheme;

  /**
   * www.example.com
   * @var string
   */
  public $authority;

  /**
   * /search
   * @var string
   */
  public $path;

  /**
   * ?q=foo
   * @var string
   */
  public $query;

  /**
   * #bar
   * @var string
   */
  public $fragment;

  private function __construct($string){
    preg_match_all('/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/', $string ,$m);
    $this->scheme = $m[2][0];
    $this->authority = $m[4][0];
    $this->path = (empty($m[5][0]))?'/':$m[5][0];
    $this->query = $m[7][0];
    $this->fragment = $m[9][0];
  }

  private function to_str(){
    $ret = "";
    if(!empty($this->scheme)) $ret .= "$this->scheme:";
    if(!empty($this->authority)) $ret .= "//$this->authority";
    $ret .= $this->normalize_path($this->path);
    if(!empty($this->query)) $ret .= "?$this->query";
    if(!empty($this->fragment)) $ret .= "#$this->fragment";
    return $ret;
  }

  private function normalize_path($path){
    if(empty($path)) return '';
    $normalized_path = $path;
    $normalized_path = preg_replace('`//+`', '/' , $normalized_path, -1, $c0);
    $normalized_path = preg_replace('`^/\\.\\.?/`', '/' , $normalized_path, -1, $c1);
    $normalized_path = preg_replace('`/\\.(/|$)`', '/' , $normalized_path, -1, $c2);
    $normalized_path = preg_replace('`/[^/]*?/\\.\\.(/|$)`', '/' , $normalized_path, -1, $c3);
    $num_matches = $c0 + $c1 + $c2 + $c3;
    return ($num_matches > 0) ? $this->normalize_path($normalized_path) : $normalized_path;
  }

  /**
   * Parse an url string
   * @param string $url the url to parse
   * @return URI
   */
  public static function parse($url){
    return new URI($url);
  }

  /**
   * Join with a relative url
   * @param string $relative the relative url to join
   * @return string
   */
  public function join($relative){
    $uri = new URI($relative);
    switch(true){
      case !empty($uri->scheme): break;
      case !empty($uri->authority): break;
      case empty($uri->path):
        $uri->path = $this->path;
        if(empty($uri->query)) $uri->query = $this->query;
      case strpos($uri->path, '/') === 0: break;
      default:
        $base_path = $this->path;
        if(strpos($base_path, '/') === false){
          $base_path = '';
        } else {
          $base_path = preg_replace ('/\/[^\/]+$/' ,'/' , $base_path);
        }
        if(empty($base_path) && empty($this->authority)) $base_path = '/';
        $uri->path = $base_path . $uri->path;
    }
    if(empty($uri->scheme)){
      $uri->scheme = $this->scheme;
      if(empty($uri->authority)) $uri->authority = $this->authority;
    }
    return $uri->to_str();
  }

  public static function normalizeURL($url, $auto_fix = true)
  {
    //sort query parameter alphabetiv
    //remove anchor elements
    if (substr($url, 0 , 2) == '//')
      $url = 'http:'.$url;

    $default_ports = array('http' => 80, 'https' => 443);
    $url_parts = parse_url($url);

    //fixing url parts withour scheme
    if ($auto_fix && empty($url_parts['scheme']) && empty($url_parts['host']) && !empty($url_parts['path']))
    {
      $url_parts['host'] = $url_parts['path'];
      unset($url_parts['path']);
    }

    $scheme = empty($url_parts['scheme']) ? 'http' : $url_parts['scheme'];
    $host = empty($url_parts['host']) ? false : $url_parts['host'];
    $port = empty($url_parts['port']) ? false : $url_parts['port'];
    $user = empty($url_parts['user']) ? false : $url_parts['user'];
    $pass = empty($url_parts['pass']) ? false : $url_parts['pass'];
    $path = empty($url_parts['path']) ? false : $url_parts['path'];
    $query = empty($url_parts['query']) ? false : $url_parts['query'];

    if ($path)
      $path = trim($path, '/');

    if ($port && $port == $default_ports[$scheme])
      $port = false;

    $normalized_url_parts = array();

    //schema
    if ($scheme)
      $normalized_url_parts[] = $scheme.':';
    $normalized_url_parts[] = '//';

    //user und passwort
    if ($user && $pass)
      $normalized_url_parts[] = $user.':'.$pass.'@';

    //host
    if ($host)
      $normalized_url_parts[] = strtolower($host);

    //TODO lower für path und query
    //port
    if ($port)
      $normalized_url_parts[] = ':'.$port;

    //path
    $normalized_url_parts[] = '/';
    if ($path)
      $normalized_url_parts[] = $path;

    //query
    if ($query)
    {
      parse_str($query, $query_parts);
      //sort alphabetic
      ksort($query_parts);
      $normalized_url_parts[] = '?';
      $normalized_url_parts[] = http_build_query($query_parts);
    }

    return implode('', $normalized_url_parts);
  }

  public static function sanitizeURL($url, $defaultScheme = 'http')
  {
    $url = mb_strtolower(filter_var($url, FILTER_SANITIZE_URL));
    $url = ltrim($url, '/');

    if (stripos($url, '://') === false)
    {
      $url = $defaultScheme.'://'.$url;
    }

    return $url;
  }

  public static function getHostByString($url, $defaultScheme = 'dummy')
  {
    $url = URI::sanitizeURL($url, $defaultScheme);
    $host = parse_url($url, PHP_URL_HOST);
    return $host;
  }

   /**
    * Checks if url is valid
    *
    * @param string $url
    * @param bool $simple
    * @param array $allowedSchemes
    * @return array|bool
    */
  public static function test($url, $simple = false, $allowedSchemes = array('http', 'https'))
  {
    $result = array();
    $result['url'] = $url;

    if (!isset($url) || $url === false) {
      $result['error'] = 'url is not set';
      return $simple ? false : $result;
    }

    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
    if ($allowedSchemes !== false && !in_array($scheme, $allowedSchemes)) {
      $result['error'] = 'url scheme is not allowed';
      return $simple ? false : $result;
    }

    $host = strtolower(parse_url($url, PHP_URL_HOST));
    $result['host'] = $host;
    $result['ip'] = gethostbyname($host);

    $requester = new Requester();
    $responses = $requester->head($url);

    if ($responses === false || !is_array($responses) || count($responses) == 0) {
      $result['error'] = 'host is not reachable';
      return $simple ? false : $result;
    }

    $last_response = array_pop($responses);

    $result['redirects'] = count($responses);
    $result['effective_url'] = $last_response['request']['url'];

    if ($last_response['response']['is_binary'] || $last_response['response']['is_image'] || !$last_response['response']['is_html']) {
      $result['error'] = 'url is not a html document';
      return $simple ? false : $result;
    }

    if ($last_response['response']['status_code'] < 200 || $last_response['response']['status_code'] >= 600) {
      $result['status_code'] = $last_response['response']['status_code'];
      $result['error'] = isset($last_response['response']['error_msg']) ? $last_response['response']['error_msg'] : 'unexpected response from url';
      return $simple ? false : $result;
    }

    $result['status_code'] = $last_response['response']['status_code'];

    return $simple ? $url : $result;
  }
}
