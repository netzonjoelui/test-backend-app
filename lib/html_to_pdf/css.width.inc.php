<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.width.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('WIDTH_AUTO',-1);
define('WIDTH_INHERIT',-2);

class CSSWidth extends CSSProperty {
  function CSSWidth() { $this->CSSProperty(false, false); }

  function default_value() { return new WCNone; }

  function parse($value) { 
    // Check if this value is 'auto' - default value of this property
    if ($value === 'auto') {
      return new WCNone;
    };

    if ($value === 'inherit') {
      return new WCFraction(1);
    };

    if (substr($value,strlen($value)-1,1) == "%") {
      // Percentage 
      return new WCFraction(((float)$value)/100);
    } else {
      // Constant
      return new WCConstant(units2pt($value));
    }
  }
}

register_css_property('width', new CSSWidth);

?>