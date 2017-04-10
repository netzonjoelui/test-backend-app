<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.bottom.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSBottom extends CSSProperty {
  function CSSBottom() { $this->CSSProperty(false, false); }
  function default_value() { return null; }
  function parse($value) { return units2pt($value); }
}

register_css_property('bottom', new CSSBottom);

?>