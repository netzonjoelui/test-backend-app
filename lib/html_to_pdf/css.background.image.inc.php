<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.background.image.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSBackgroundImage extends CSSSubProperty {
  function default_value() { 
    return new BackgroundImagePDF(null); 
  }

  function parse($value) {
    // 'url' value
    if (preg_match("/url\((.*[^\\\\]?)\)/is",$value,$matches)) {
      $url = $matches[1];

      global $g_baseurl;
      return new BackgroundImagePDF(guess_url(css_remove_value_quotes($url), $g_baseurl));
    }

    // 'none' and unrecognzed values
    return new BackgroundImagePDF(null);
  }
}

?>