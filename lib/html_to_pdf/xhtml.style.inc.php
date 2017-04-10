<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/xhtml.style.inc.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function process_style(&$html) {
  // Remove HTML comment bounds inside the <style>...</style> 
  $html = preg_replace("#(<style[^>]*>)\s*<!--#is","\\1",$html); 
  $html = preg_replace("#-->\s*(</style>)#is","\\1",$html);

  // Remove CSS comments
  while (preg_match("#(<style[^>]*>.*)/\*.*?\*/.*(</style>)#is",$html)) {
    $html = preg_replace("#(<style[^>]*>.*)/\*.*\*/(.*</style>)#is","\\1\\2",$html);
  };
}

?>
