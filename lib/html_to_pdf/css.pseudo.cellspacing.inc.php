<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.pseudo.cellspacing.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

class CSSCellSpacing extends CSSProperty {
  function CSSCellSpacing() { $this->CSSProperty(false, false); }

  // this pseudo value should be inherited only by the table cells/rows; nested tables 
  // should get a default value
  //
  function inherit() { 
    // Determine parent 'display' value
    $handler =& get_css_handler('display');

    // 'display' CSS property processed AFTER this; so parent display value will be
    // on the top of the stack
    //
    $parent_display = $handler->get();

    // Inherit vertical-align from table-rows 
    if ($parent_display === "table-row" || $parent_display === "table") {
      $this->push($this->get());
      return;
    }

    $this->push(is_inline_element($parent_display) ? $this->get() : $this->default_value());
  }

  function default_value() { return "1px"; }
  function parse($value) { return $value; }
}

register_css_property('-cellspacing', new CSSCellSpacing);

?>