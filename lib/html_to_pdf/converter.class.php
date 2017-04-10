<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/converter.class.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class Converter {
  function create() {
    if (function_exists('iconv')) {
      return new IconvConverter;
    } else {
      return new PurePHPConverter;
    }
  }
}

class IconvConverter {
  function to_utf8($html, $encoding) {
    return iconv(strtoupper($encoding), "UTF-8", $html);
  }
}

class PurePHPConverter {
  function to_utf8($html, $encoding) {
    global $g_utf8_converters;
    
    if ($encoding === 'iso-8859-1') {
      return utf8_encode($html);
    } elseif ($encoding === 'utf-8') {
      return $html;
    } elseif(isset($g_utf8_converters[$encoding])) {
      return something_to_utf8($html, $g_utf8_converters[$encoding][0]);
    } else {
      die("Unsupported encoding detected: '$encoding'");
    };
  }
}
?>