<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/ps.text.inc.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

// Initialize utf-8 symbols to different encodings mappings
$g_utf8_to_encodings_mapping = array();
foreach (array_keys($g_utf8_converters) as $encoding) {
  $flipped = array_flip($g_utf8_converters[$encoding][0]);
  foreach ($flipped as $utf => $code) {
    if (ord($code)>=32 && ord($code)<128) {
      $g_utf8_to_encodings_mapping[$utf][$encoding] = $code;
    } else {
      $g_utf8_to_encodings_mapping[$utf][$encoding] = sprintf("\\%03o",ord($code));
    };
  };
};

// FIXME: just a workaround for now; these arrays should be joined
$g_utf8_to_encodings_mapping_pdf = array();
foreach (array_keys($g_utf8_converters) as $encoding) {
  $flipped = array_flip($g_utf8_converters[$encoding][0]);
  foreach ($flipped as $utf => $code) {
    $g_utf8_to_encodings_mapping_pdf[$utf][$encoding] = $code;
  };
};

function quote_ps($psdata) {
  $str = str_replace(array("(",")","%"), array("\\(","\\)","\\%"), $psdata);
  
  return $str;
}

?>