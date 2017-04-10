<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/systemcheck.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

// Check the system requirements
//
function check_requirements() {
  // Check if GD is available
  //
  if (!function_exists('imagecreatetruecolor')) { 
    die("No GD2 extension found. Check your PHP configuration");
  };

  // Check if allow_url_fopen is available
  //  
  if (!ini_get('allow_url_fopen')) {
    readfile('templates/missing_url_fopen.html');
    error_log("'allow_url_fopen' is disabled");
    die();
  }

  // Check if image cache works.
  // if it doesn't, the check_cache_dir will not return, so we may not bother 
  // with checking result value
  //
  Image::check_cache_dir();
}
?>