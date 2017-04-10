<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/stubs.file_get_contents.inc.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

function file_get_contents($file) {
  $lines = file($file);
  if ($lines) {
    return implode('',$lines);
  } else {
    return "";
  };
}
?>