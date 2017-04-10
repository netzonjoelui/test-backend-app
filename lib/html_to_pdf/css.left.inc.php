<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.left.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSLeft extends CSSProperty {
  function CSSLeft() { $this->CSSProperty(false, false); }

  function default_value() { return 0; }

  function parse($value) {
    return units2pt($value);
  }

  function value2ps($value) {
    return $value;
  }
}

register_css_property('left', new CSSLeft);

?>