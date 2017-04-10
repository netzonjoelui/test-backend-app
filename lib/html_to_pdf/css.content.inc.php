<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.content.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSContent extends CSSProperty {
  function CSSContent() { $this->CSSProperty(false, false); }

  function default_value() { return ""; }

  // CSS 2.1 p 12.2: 
  // Value: [ <string> | <uri> | <counter> | attr(X) | open-quote | close-quote | no-open-quote | no-close-quote ]+ | inherit
  //
  // TODO: process values other than <string>
  //
  function parse($value) {
    return css_remove_value_quotes($value);
  }

  function value2ps($value) {}
}

register_css_property('content', new CSSContent);

?>