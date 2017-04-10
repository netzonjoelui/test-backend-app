<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/utils_text.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function squeeze($string) {
  return preg_replace("![ \n\t]+!"," ",trim($string));
}

?>