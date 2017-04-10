<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.color.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSColor extends CSSProperty {
  function CSSColor() { $this->CSSProperty(true, true); }

  function default_value() { return new ColorPDF(array(0,0,0),false); }

  function parse($value) {
    $color = parse_color_declaration($value, $this->get());
    return new ColorPDF($color, is_transparent($color));
  }

  function value2ps($value) {
    return 
      $value[0]/255 . " " . 
      $value[1]/255 . " " . 
      $value[2]/255 . " " .
      (is_transparent($value) ? "0" : "1").
      " color-create";
  }
}

register_css_property('color', new CSSColor);

?>