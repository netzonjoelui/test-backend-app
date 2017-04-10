<?php
/* This is a simple http client; it does not pretends to be complete. Its only task - to 
 * fetch the html pages/images from the WEB via GET requests.
 * 
 * Unlike standard functions, it allows us to know which url we have been redirected to.
 */

define('MAX_REDIRECTS',10);

define('HTTP_OK',200);

class Fetcher {
  var $protocol;
  var $host;
  var $port;
  var $path;

  var $headers;
  var $content;
  var $code;
  
  var $redirects;

  function Fetcher() {
    $this->redirects = 0;
    $this->port = 80;

    // Default encoding
    $this->encoding = "iso-8859-1";
  }

  function detect_encoding_using_meta($html) {

    // Note: some "designers" do not place the meta content into quotes.
    //
    if (preg_match("#<\s*meta[^>]+content=(['\"])?text/html;\s*charset=([\w\d-]+)#is",$html,$matches)) {
      return strtolower($matches[2]);
    } else {
      return "";
    };
  }

  function detect_encoding() {
    // First, try to get encoding from META http-equiv tag
    //
    $this->encoding = $this->detect_encoding_using_meta($this->content);

    // If no META encoding specified, try to use encoding from HTTP response
    //
    if ($this->encoding === "") {
      if (preg_match("/Content-Type: .*charset=\s*(\S+)/i",$this->headers,$matches)) {
        $this->encoding = strtolower($matches[1]);
      };
    }

    // At last, fall back to default encoding
    //
    if ($this->encoding === "") { $this->encoding = "iso-8859-1";  }
  }

  function _fix_location($location) {
    if (preg_match("#^https?://#",$location)) {
      return $location;
    };

    if ($location{0} == "/") {
      return $this->protocol."://".$this->host.$location;
    };

    return $this->protocol."://".$this->host.$this->path.$location;
  }

  function fetch($url) {
    error_log("Fetching: $url");

    $this->url = $url;

    $parts = parse_url($url);

    if (isset($parts['scheme']))   { $this->protocol  = $parts['scheme'];    };
    if (isset($parts['host']))     { $this->host      = $parts['host'];      };
    if (isset($parts['port']))     { $this->port      = $parts['port'];      };
    if (isset($parts['path']))     { $this->path      = $parts['path'];      } else { $this->path = "/"; };
    if (isset($parts['query']))    { $this->path     .= '?'.$parts['query']; };

    if ($this->protocol <> 'http' && $this->protocol <> 'https') { 
      die("Unsupported protocol: ".$this->protocol);
    };

    if ($this->protocol == "https" && !isset($parts['port']))
    {
       $this->port = 443;
    }

    // Connect to the targt host 
    if ($this->protocol == "https") {
       $fp = @fsockopen("ssl://$this->host", $this->port, $errno, $errstr, 5);
    } else {
       $fp = @fsockopen($this->host,$this->port,$errno,$errstr,5);
    }
    if (!$fp) {
      error_log("Cannot connect to ".$this->host.":".$this->port." - (".$errno.")".$errstr);
      return false;
    };
    // Build the HEAD request header (we're saying we're just a browser as some pages don't like non-standard user-agents)
    $header  = "HEAD ".$this->path." HTTP/1.1\r\n";
    $header .= "Host:".$this->host."\r\n";
    $header .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7) Gecko/20040803 Firefox/0.9.3\r\n";
    $header .= "Connection: close\r\n";
    $header .= "Referer: ".$this->protocol."://".$this->host.$this->path."\r\n\r\n";
    // Send the header 
    fputs ($fp, $header);
    // Get the responce
    $res = "";

    // The PHP-recommended construction 
    //    while (!feof($fp)) { $res .= fread($fp, 4096); };
    // hangs indefinitely on www.searchscout.com, for example.
    // seems that they do not close conection on their side or somewhat similar;

    // let's assume that there will be no HTML pages greater than 1 Mb

    $res = fread($fp, 1024*1024);

    // Close connection handle, we do not need it anymore
    fclose($fp);

    // Check return code
    // Note the return code will always be contained in the responce, so 
    // the we may not check the result of 'preg_match' - it matches always.
    preg_match('/\s(\d+)\s/',$res,$matches);
    $result = $matches[1];

//     print($res);
//     print("\n");
//     print($result);
//     print("\n");

    $this->code = $result;

    switch ($result) {
    case '200': // OK
//       if (!preg_match('/(.*?)\r\n\r\n(.*)/s',$res,$matches)) {
//         error_log("Unrecognized HTTP response");
//         return false;
//       };
      $this->headers = $matches[1];
      $this->content = @file_get_contents($url);

      // Now the entire page have been fetched; let's detect encoding this page is in
      $this->detect_encoding();

      return true;
      break;
    case '301': // Moved Permanently
      $this->redirects++;
      if ($this->redirects > MAX_REDIRECTS) { return false; };
      preg_match('/Location: ([\S]+)/i',$res,$matches);
      return $this->fetch($this->_fix_location($matches[1]));
    case '302': // Found
      $this->redirects++;
      if ($this->redirects > MAX_REDIRECTS) { return false; };
      preg_match('/Location: ([\S]+)/i',$res,$matches);
      return $this->fetch($this->_fix_location($matches[1]));
    case '400': // Bad request
    case '401': // Unauthorized
    case '402': // Payment required
    case '403': // Forbidden
    case '404': // Not found - but should return some html content - error page
    case '405': // Method not allowed
      if (!preg_match('/(.*?)\r\n\r\n(.*)/s',$res,$matches)) {
        error_log("Unrecognized HTTP response");
        return false;
      };
      $this->headers = $matches[1];
      $this->content = @file_get_contents($url);
      return true;
    default:
      error_log("Unrecognized HTTP result code:".$result);
      return false;
    };
  }
}
?>