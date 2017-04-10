<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.top.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

// Format of 'top' value:
// array( float, is_percentage )

class CSSTop extends CSSProperty {
  function CSSTop() { $this->CSSProperty(false, false); }

  function default_value() { return array(0, false); }

  function parse($value) {
    $value = trim($value);

    // Check if current value is percentage
    if (substr($value, strlen($value)-1, 1) === "%") {
      return array((float)$value, true);
    } else {
      return array(units2pt($value), false);
    }
  }

  function value2ps($value) {
    return "<< /value ".$value[0]." /percentage ".($value[1] ? "true" : "false")." >>";
  }
}

register_css_property('top', new CSSTop);

?>