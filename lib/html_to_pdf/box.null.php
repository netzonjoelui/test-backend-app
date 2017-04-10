<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.null.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class NullBoxPDF extends GenericBoxPDF {
  function get_min_width(&$context) { return 0; }
  function get_max_width(&$context) { return 0; }
  function get_height() { return 0; }

  function NullBoxPDF() {
    // No CSS rules should be applied to null box
    push_css_defaults();
    $this->GenericBoxPDF();
    pop_css_defaults();
  }
  
  function &create($pdf, &$root) { 
    $box =& new NullBoxPDF;
    return $box; 
  }
  function show(&$pdf) {}

  function reflow(&$parent, &$context) {
    // Move current "box" to parent current coordinates. It is REQUIRED, 
    // as some other routines uses box coordinates.
    $this->put_left($parent->get_left());
    $this->put_top($parent->get_top());
  }

  function is_null() { return true; }

  function to_ps(&$psdata) { 
    // Just do nothing
  }
}
?>
