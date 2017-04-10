<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.border.collapse.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

define('BORDER_COLLAPSE', 1);
define('BORDER_SEPARATE', 2);

class CSSBorderCollapse extends CSSProperty {
  function CSSBorderCollapse() { $this->CSSProperty(true, true); }

  function default_value() { return BORDER_SEPARATE; }

  function parse($value) {
    if ($value === 'collapse') { return BORDER_COLLAPSE; };
    if ($value === 'separate') { return BORDER_SEPARATE; };
    return $this->default_value();
  }

  function value2ps($value) { 
    // Do nothing
  }
}

register_css_property('border-collapse', new CSSBorderCollapse);

?>