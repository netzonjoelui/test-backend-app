<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.white-space.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('WHITESPACE_NORMAL',0);
define('WHITESPACE_PRE',1);
define('WHITESPACE_NOWRAP',2);

class CSSWhiteSpace extends CSSProperty {
  function CSSWhiteSpace() { $this->CSSProperty(true, true); }

  function default_value() { return WHITESPACE_NORMAL; }

  function parse($value) {
    switch ($value) {
    case "normal": 
      return WHITESPACE_NORMAL;
    case "pre":
      return WHITESPACE_PRE;
    case "nowrap":
      return WHITESPACE_NOWRAP;
    default:
      return WHITESPACE_NORMAL;
    }
  }      

  function value2ps($value) {
    switch ($value) {
    case WHITESPACE_NORMAL:
      return '/normal';
    case WHITESPACE_PRE:
      return "/pre";
    case WHITESPACE_NOWRAP:
      return "/nowrap";
    default:
      return "/normal";
    }
  }
}

register_css_property('white-space', new CSSWhiteSpace);
  
?>