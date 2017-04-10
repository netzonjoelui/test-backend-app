<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.position.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('POSITION_STATIC',0);
define('POSITION_RELATIVE',1);
define('POSITION_ABSOLUTE',2);
define('POSITION_FIXED',3);

class CSSPosition extends CSSProperty {
  function CSSPosition() { $this->CSSProperty(false, false); }

  function default_value() { return POSITION_STATIC; }

  function parse($value) {
    // As usual, though standards say that CSS properties should be lowercase, 
    // some people make them uppercase. As we're pretending to be tolerant,
    // we need to convert it to lower case

    switch (strtolower($value)) {
    case "absolute":
      return POSITION_ABSOLUTE;
    case "relative":
      return POSITION_RELATIVE;
    case "fixed":
      return POSITION_FIXED;
    case "static":
      return POSITION_STATIC;
    default:
      return POSITION_STATIC;
    }
  }

  function value2ps($value) {
    switch ($value) {
    case POSITION_STATIC:
      return "/static";
    case POSITION_RELATIVE:
      return "/relative";
    case POSITION_ABSOLUTE:
      return "/absolute";
    case POSITION_FIXED:
      return "/fixed";
    default:
      return "/static";
    };
  }
}

register_css_property('position', new CSSPosition);

?>