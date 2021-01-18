<?php
  namespace util;

  class Requester
  {
    private $curl = NULL;
    private $configs = NULL;
    private $page_header = NULL;
    private $page_content = NULL;
    private $is_image = false;
    private $is_html = false;
    private $modified = false;
    private $content_type = false;
    private $page_size = -1;
    private $conf =
      array(
        'max_size' => false,
        'accept' => array('.*/.*'),
      );

    private function getDefaults()
    {
      $config =
      array(
        'curl' => array(
          'user-agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0',
        ),
        'timeout_connect' => 3,
        'timeout_response' => 5,
        'max_redirects' => 10,
      );
      return $config;
    }

    public function __construct($args = array())
    {
      $defaults = $this->getDefaults();
      $this->configs = array_merge($defaults, $args);

      $options =
      array
      (
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_HEADER         => false,
        CURLINFO_HEADER_OUT    => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_ENCODING       => "",
        CURLOPT_USERAGENT      => $this->configs['curl']['user-agent'],
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => $this->configs['timeout_connect'],
        CURLOPT_TIMEOUT        => $this->configs['timeout_response'],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE        => 0
      );

      $this->curl      = curl_init();
      curl_setopt_array($this->curl, $options);
    }

    public function __destruct()
    {

    }

    public function setContentTypes($content_types)
    {
      $this->conf['accept'] = array();
      $cts = explode(',', $content_types);
      foreach ($cts as $ct)
      {
        $ct = trim($ct);
        $this->conf['accept'][] = str_replace(array('*'), array('.*'), $ct);
      }
    }

    public function setOption($key, $value)
    {
      curl_setopt($this->curl, $key, $value);
      return true;
    }

    public function setUserAgent($ua)
    {
      return $this->setOption(CURLOPT_USERAGENT, $ua);
    }

    private function on_header($ch, $header)
    {
      $this->page_header .= $header;

      $trimmed = rtrim($header);

      if (preg_match('/^Content-Length: (\d+)$/', $trimmed, $matches))
      {
        $this->page_size = $matches[1];
      }

      if (preg_match('/^Last-Modified: (.+)$/', $trimmed, $matches))
      {
        $this->modified = strtotime(trim($matches[1]));
      }

      $accepted = true;
      if (preg_match('/^Content-Type: ([\w\/]+)(;|$)/i', $trimmed, $matches))
      {
        $accepted = false;
        $content_type = trim($matches[1]);
        $this->content_type = $content_type;
        $this->is_html = false;
        foreach ($this->conf['accept'] as $rule)
        {
          if (preg_match('#^'.$rule.'$#i', $content_type))
          {
            $accepted = true;
            break;
          }
        }

        list($media_type, $media_description) = explode('/', $content_type, 2);
        if ($media_type == 'image')
        {
          $this->is_image = true;
        }

        if ($media_type == 'html' || $media_description == 'html')
        {
          $this->is_html = true;
        }
      }

      if (!$accepted)
        return false;

      return strlen($header);
    }

    private function on_write($ch, $data)
    {
      if ($this->conf['max_size'] === false || $this->page_size < $this->conf['max_size']) //max file size - default:  4 MB
      {
        if ($this->page_content === false)
          $this->page_content = '';

        $this->page_content .= $data;
      }
      return strlen($data);
    }

    private function analysePageHeader($header, $isResponseHeader = true)
    {
      $fields = http_parse_headers($header);

      $ret =
      array
      (
          'fields' => $fields,
          'header_size' => strlen($header),
      );

      if ($isResponseHeader)
      {
      	list($ret['version'], $ret['status_code'], $ret['status_phrase']) = explode(' ', $fields[0], 3);
      }
      else
     {
     	  list($ret['method'], $ret['path'], $ret['version']) = explode(' ', $fields[0], 3);
      }

      return $ret;
    }

    public function easyGet($url, $data = false, array $args = array())
    {
      $args['simple_result'] = true;

      return $this->get($url, $data, $args);
    }

    public function get($url, $data = false, array $args = array())
    {
    	if ($data)
    	{
    		if (!is_array($data))
    			$data = array($data);

    		//TODO check for anchor, remove anchor, url quick parser

    		$query = http_build_query($data);
    		$concator = '&';
    		if (strpos($url, '?') === false)
    			$concator = '?';

    		$url .= $concator.$query;
    	}

      if (!isset($args['auto_follow'])) {
        $args['auto_follow'] = true;
      }

      return $this->_fetch($url, 'GET', $args);
    }

    public function head($url, $data = false, array $args = array())
    {
      if ($data)
      {
        if (!is_array($data))
          $data = array($data);

        $query = http_build_query($data);
        $concator = '&';
        if (strpos($url, '?') === false)
          $concator = '?';

        $url .= $concator.$query;
      }

      if (!isset($args['auto_follow'])) {
        $args['auto_follow'] = true;
      }

      return $this->_fetch($url, 'HEAD', $args);
    }

    public function easyPost($url, $data = false, array $args = array())
    {
      $args['simple_result'] = true;

      return $this->post($url, $data, $args);
    }

    public function post($url, $data = false, array $args = array())
    {
      if ($data && is_array($data)) {
        $data = http_build_query($data);
    	}
      $args['data'] = $data;

      return $this->_fetch($url, 'POST', $args);
    }

    //TODO check Etag and last modified values
    private function _fetch($url, $method = 'GET', $args = false)
    {
    	if (!in_array($method, array('GET', 'POST', 'HEAD', 'DELETE')))
    		die('Method '.$method.' currently not supported');

      if (!isset($args['no_check_url']) && filter_var($url, FILTER_VALIDATE_URL) === false)
    	{
          return false;
    	}

    	//no file: includes!
    	$scheme = parse_url($url, PHP_URL_SCHEME);
    	if (!$scheme)
    	{
    	  $url = 'http://'.$url;
    	}
    	else
    	{
    	  if (!in_array($scheme, array('http', 'https')))
    	  {
            return false;
    	  }
    	}

    	curl_setopt($this->curl, CURLOPT_VERBOSE, true);

    	curl_setopt($this->curl, CURLOPT_POST, false);
    	curl_setopt($this->curl, CURLOPT_NOBODY, false);


      curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
      if (isset($args['header']) && $args['header'] !== false)
    	{
    	  curl_setopt($this->curl, CURLOPT_HTTPHEADER, $args['header']);
  	  }

    	switch ($method)
    	{
    		case 'POST':
    		  curl_setopt($this->curl, CURLOPT_POST, true);
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $args['data']);
    		  break;

    		case 'HEAD':
    		  curl_setopt($this->curl, CURLOPT_NOBODY, true);
    		  break;

        case 'DELETE':
          curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
          break;
    	}

      $auto_follow = true;
      if ($args && isset($args['auto_follow']))
      {
        $auto_follow = $args['auto_follow'] === true;
      }


      unset($this->page_header);
      unset($this->page_content);

      //TODO reset function
      $this->is_image = false;
      $this->content_type = false;
      $this->modified = time();
      $this->page_header = '';
      $this->page_content = false;
      $this->page_size = -1;
      curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'on_header'));
      curl_setopt($this->curl, CURLOPT_WRITEFUNCTION, array($this, 'on_write'));
      curl_setopt($this->curl, CURLOPT_URL, $url);
      curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

      // Overwrite default configuration for this request
      if (!empty($args['timeout'])) {
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $args['timeout']);
      }
      if (!empty($args['timeout_ms'])) {
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $args['timeout_ms']);
      }
      if (!empty($args['connection_timeout'])) {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $args['connection_timeout']);
      }
      if (!empty($args['connection_timeout_ms'])) {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $args['connection_timeout_ms']);
      }

      $request_time = time();
      curl_exec($this->curl);
      $err     = curl_errno($this->curl);
      $errmsg  = curl_error($this->curl);
      $info = curl_getinfo($this->curl);

      if (isset($args['simple_result']))
      {
        return array('url' => $info['url'], 'status_code' => $info['http_code'], 'content' => $this->page_content);
      }

      $result = array('request' => array(), 'response' => array());
      $result['request'] =
      array
      (
      		'url' => $info['url'],
      		'times' =>
      		array
      		(
      				'request' => $request_time,
      				'namelookup' => $info['namelookup_time'],
      				'connect' => $info['connect_time'],
      				'pretransfer' => $info['redirect_time'],
      				'redirect' => isset($info['redirect_time']) ? $info['redirect_time'] : 0,
      				'starttransfer' => $info['starttransfer_time'],
      				'total' => $info['total_time'],
      		),
      		'sizes' =>
      		array
      		(
      				'upload' => $info['size_upload'],
      				'download' => $info['size_download'],
      				'request_header' => $info['request_size'],
      		),
          'header' => array(),
      );

      if (isset($info['request_header']))
        $result['request']['header'] = $this->analysePageHeader($info['request_header'], false);


      if ($err)
      {
        $result['response'] = array(
          'url' => $info['url'],
      		'error_code' => intval($err),
      		'status_code' => 1000 + intval($err),
      		'error_msg' => $errmsg,
      	  'is_binary' => false,
      	  'is_image' => $this->is_image,
          'is_html' => $this->is_html,
          'content_type' => $this->content_type,
      	);

        if (strlen($this->page_header) > 0)
        {
          $result['response'] = array_merge($result['response'], $this->analysePageHeader(trim($this->page_header)));
          $result['response']['crawled'] = false;
        }

      	if ($auto_follow)
          return array($result);
      	else
      	  return $result;
      }


      //analysing if is binary file
      $is_binary = false;
      $info['content_size'] = ($this->page_content === false) ? -1 : strlen($this->page_content);

      $to_val = min($info['content_size'], 128);
      for ($i = 0 ; $i < $to_val ; $i++)
      {
        $c = substr($this->page_content, $i, 1);
        $o = ord($c);
        if ($o < 32 && !in_array($o, array(9, 10, 13))) //9 - Tab, 10 - LF, 13 - CR
        {
          $is_binary = true;
          break;
        }
      }

      $result['response'] =
        array
        (
          'url' => $info['url'],
          'size_total' => $info['content_size'],
          'size_compressed' => $info['size_download'],
          'is_binary' =>  $is_binary,
          'is_image' => $this->is_image,
          'is_html' => $this->is_html,
          'content_type' => $this->content_type,
          'crawled' => true,
          'modified' => $this->modified,
          'content' => $is_binary ? base64_encode($this->page_content) : $this->page_content,
        );

      $result['response'] = array_merge($result['response'], $this->analysePageHeader(trim($this->page_header)));

      if ($this->page_content === false && $result['response']['status_code'] > 510)
        $result['response']['status_code'] += 2000;

      $pages = array();

      if (isset($result['response']['fields']['Location']) && $result['response']['status_code'] != 200)
      {
        $location = trim($result['response']['fields']['Location']);

        $location = \util\Uri::parse($url)->join($location);

        //TODO hash url entfernen!
        $result['response']['redirect'] = array('url' => $location/*, 'id' => hashURL($location)*/);


        if ($auto_follow)
        {
          $pages[] = $result;

      	  if (!isset($args['redirect_count']))
      	    $args['redirect_count'] = 0;
      	  $args['redirect_count']++;

      	  if ($args['redirect_count'] < $this->configs['max_redirects'])
      	  {
      	    $page = $this->_fetch($location, $method, $args);
      	    if ($page === false)
      	      return $pages;

        	  $pages = array_merge($pages, $page);
      	  }
        }
        else
       {
          return $result;
        }
      }
      else
      {
        if ($auto_follow)
        {
          $pages[] = $result;
        }
        else
       {
          return $result;
       }
      }
      return $pages;
    }
  }

  if (!function_exists('http_parse_headers'))
  {
    function http_parse_headers($raw_headers)
    {
      $raw_headers = trim($raw_headers);

      //DOUBLE HEADER remove
      $tmp = explode("\r\n\r\n", $raw_headers);
      $raw_headers = array_pop($tmp);

      $headers = array();
      $last_key = '';
      $line_id = 0;


      foreach(explode("\r\n", $raw_headers) as $i => $h)
      {
        if (strlen(trim($h)) == 0)
          continue;

        if ($line_id == 0)
        {
          $headers[0] = trim($h);
        }
        else
        {
          list($key, $value) = explode(':', $h, 2);
          if (isset($value) )
          {
            if (!isset($headers[$key]))
              $headers[$key] = trim($value);
            elseif (is_array($headers[$key]))
            {
              $headers[$key] = array_merge($headers[$key], array(trim($value)));
            }
            else
            {
              $headers[$key] = array_merge(array($headers[$key]), array(trim($value)));
            }

            $last_key = $key;
          }
          else
          {
            if (substr($key, 0, 1) == "\t")
              $headers[$last_key] .= "\r\n\t".trim($key);
            elseif (!$last_key)
            {
              $headers['_unknown'] = trim($key.'-'.$value);
            }
          } 
        }
        $line_id++;
      }

      return $headers;
    }
  }
