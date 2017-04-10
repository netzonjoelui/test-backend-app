<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/ps.utils.inc.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

define('MAX_IMAGE_ROW_LEN',8);
define('MAX_TRANSPARENT_IMAGE_ROW_LEN',8);

function trim_ps_comments($data) {
  $data = preg_replace("/(?<!\\\\)%.*/","",$data);
  return preg_replace("/ +$/","",$data);
}

function format_ps_color($color) {
  return sprintf("%.3f %.3f %.3f",$color[0]/255,$color[1]/255,$color[2]/255);
}
?>