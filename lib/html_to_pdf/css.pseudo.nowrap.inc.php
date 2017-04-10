<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.pseudo.nowrap.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

define('NOWRAP_NORMAL',0);
define('NOWRAP_NOWRAP',1);

class CSSPseudoNoWrap extends CSSProperty {
  function CSSPseudoNoWrap() { $this->CSSProperty(false, false); }
  function default_value() { return NOWRAP_NORMAL; }

  function value2ps($value) {
    switch ($value) {
    case NOWRAP_NORMAL:
      return '/normal';
    case NOWRAP_NOWRAP:
      return "/nowrap";
    default:
      return "/normal";
    }
  }

  function ps($writer) {
    if ($this->get() == NOWRAP_NOWRAP) {
      $writer->write("dup get-box-dict /WhiteSpace ".$this->value2ps($this->get())." put\n");
    }
  }

  function pdf(){ return $this->get(); }
}

register_css_property('-nowrap', new CSSPseudoNoWrap);
  
?>