<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.list-style-position.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('LSP_OUTSIDE',0);
define('LSP_INSIDE',1);

class CSSListStylePosition extends CSSSubProperty {
  // CSS 2.1: default value for list-style-position is 'outside'
  function default_value() { return LSP_OUTSIDE; }

  function parse($value) {
    if (preg_match('/\binside\b/',$value))    { return LSP_INSIDE; };
    if (preg_match('/\boutside\b/',$value))    { return LSP_OUTSIDE; };

    return null;
  }

  function value2ps($value) {
    if ($value === LSP_INSIDE)  { return "/inside"; };
    if ($value === LSP_OUTSIDE) { return "/outside"; };

    return "/outside";
  }
}

?>