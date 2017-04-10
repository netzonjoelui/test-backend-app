<?php
/* This is a simple http client; it does not pretends to be complete. Its only task - to 
 * fetch the html pages/images from the WEB via GET requests.
 * 
 * Unlike standard functions, it allows us to know which url we have been redirected to.
 */

define('MAX_REDIRECTS',10);

class Fetcher {
  var $protocol;
  var $host;
  var $port;
  var $path;

  var $headers;
  var $content;
  
  var $redirects;

  function Fetcher() {
    $this->redirects = 0;
    $this->port = 80;
  }
  
  function fetch($url) {
    $this->url = $url;
    $this->content = file_get_contents($url);
    return true;
  }
}
?>