<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.visibility.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('VISIBILITY_VISIBLE',0);
define('VISIBILITY_HIDDEN',1);
define('VISIBILITY_COLLAPSE',2); // TODO: currently treated is hidden

class CSSVisibility extends CSSProperty {
  function CSSVisibility() { $this->CSSProperty(false, false); }

  function default_value() { return VISIBILITY_VISIBLE; }

  function parse($value) {
    if ($value === 'visible')  { return VISIBILITY_VISIBLE; };
    if ($value === 'hidden')   { return VISIBILITY_HIDDEN; };
    if ($value === 'collapse') { return VISIBILITY_COLLAPSE; };
    return VISIBILITY_VISIBLE;
  }

  function value2ps($value) {
    if ($value === VISIBILITY_VISIBLE)  { return "/visible"; }
    if ($value === VISIBILITY_HIDDEN)   { return "/hidden"; }
    if ($value === VISIBILITY_COLLAPSE) { return "/collapse"; }
    return "/visible";
  }
}

register_css_property('visibility', new CSSVisibility);

?>