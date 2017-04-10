<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.radiobutton.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

define('RADIOBUTTON_SIZE','15px');

class RadioBoxPDF extends GenericBoxPDF {
  var $checked;

  function &create($viewport, &$root) {
    $box =& new RadioBoxPDF($viewport, $root);
    return $box;
  }

  function RadioBoxPDF(&$viewport, &$root) {
    // Call parent constructor
    $this->GenericBoxPDF();

    // Check the box state
    $this->checked = $root->has_attribute('checked');

    // Setup box size:
    $this->default_baseline = units2pt(CHECKBOX_SIZE);
    $this->height           = units2pt(CHECKBOX_SIZE);
    $this->width            = units2pt(CHECKBOX_SIZE);
  }

  // Inherited from GenericBoxPDF
  function get_min_width(&$context) { return $this->get_full_width($context); }
  function get_max_width(&$context) { return $this->get_full_width($context); }

  function reflow(&$parent, &$context) {  
    GenericBoxPDF::reflow($parent, $context);
    
    // set default baseline
    $this->baseline = $this->default_baseline;
    
//     // Vertical-align
//     $this->_apply_vertical_align($parent);

    // append to parent line box
    $parent->append_line($this);

    // Determine coordinates of upper-left _margin_ corner
    $this->guess_corner($parent);

    // Offset parent current X coordinate
    $parent->_current_x += $this->get_full_width();

    // Extends parents height
    $parent->extend_height($this->get_bottom_margin());
  }

  function show(&$viewport) {   
    // Cet check center
    $x = ($this->get_left() + $this->get_right()) / 2;
    $y = ($this->get_top() + $this->get_bottom()) / 2;

    // Calculate checkbox size
    $size = $this->get_width() / 3;

    // Draw checkbox
    $viewport->setlinewidth(0.25);
    $viewport->circle($x, $y, $size);
    $viewport->stroke();

    // Draw checkmark if needed
    if ($this->checked) { 
      $check_size = $this->get_width() / 6;

      $viewport->circle($x, $y, $check_size);
      $viewport->fill();
    }
  }

  function to_ps(&$psdata) {
    $psdata->write("box-radiobutton-create\n");
    $psdata->write(($this->checked ? "true" : "false")." 1 index box-radiobutton-put-checked\n");
    $psdata->write("add-child\n");    
  }
}
?>
