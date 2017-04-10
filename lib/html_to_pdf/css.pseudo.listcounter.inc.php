<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.pseudo.listcounter.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

class CSSPseudoListCounter extends CSSProperty {
  function CSSPseudoListCounter() { $this->CSSProperty(true, false); }
  function default_value() { return 1; }
}

register_css_property('-list-counter', new CSSPseudoListCounter);

?>