<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/xhtml.comments.inc.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function remove_comments(&$html) {
  $html = preg_replace("#<!--.*?-->#is","",$html);
  $html = preg_replace("#<!.*?>#is","",$html);
}

?>
