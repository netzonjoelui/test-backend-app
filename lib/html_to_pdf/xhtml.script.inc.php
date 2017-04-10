<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/xhtml.script.inc.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function process_script($sample_html) {
  return preg_replace("#<script.*?</script>#is","",$sample_html);
}

?>