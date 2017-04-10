<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.br.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class BRBoxPDF extends GenericBoxPDF {
  function &create($viewport) {
    $box =& new BRBoxPDF($viewport);
    return $box;
  }

  function BRBoxPDF($viewport) {
    // Call parent constructor
    push_css_defaults();
    $this->GenericBoxPDF();
    pop_css_defaults();

    // As we've pushed default values of CSS properties, we need to restore correct 'block' value of BR tag
    $this->display = 'block';

    // the folowing properties may be applied to BR tags
    // (note that they have NOT been applied in the GenericBoxPDF construtor, as we used push_css_defaults() before 
    // calling it)
    //
    // 'clear'
    $handler =& get_css_handler('clear');
    $this->clear = $handler->get();
  }

  // Inherited from GenericBoxPDF
  function get_min_width(&$context) {
    return 0;
  }

  function get_max_width(&$context) {
    return 0;
  }

  function reflow(&$parent, &$context) {  
    GenericBoxPDF::reflow($parent, $context);

    $y = $parent->_current_y;

    // CSS 'clear' property may be applied to BR tags!
    $y = $this->apply_clear($y, $context);
    
    // Move current "box" to parent current coordinates. It is REQUIRED, 
    // as some other routines uses box coordinates.
    $this->put_left($parent->_current_x);
    $this->put_top($y);

    // If we have a sequence of BR tags (like <BR><BR>), we'll have an only one item in the parent's
    // line box - whitespace; in this case we'll need to additionally offset current y coordinate by the font size
    if (count($parent->_line) == 0) {
      $parent->close_line($context, true);
      $parent->_current_y = min($this->get_bottom(), $parent->_current_y - $this->font_size);
    } elseif ((count($parent->_line) > 1) || 
              (!is_whitespace($parent->_line[0]))) {
      $parent->close_line($context, true);
    } elseif (count($parent->_line)>0) {
      // Restore height of whitespace (it had been reset in /flow-whitespace as a first whitespace in a line box)
      $dh = $parent->_line[0]->font_size;
      $parent->close_line($context, true);
      $parent->_current_y = min($this->get_bottom(), $parent->_current_y - $dh);
    }

    // We need to explicitly extend the parent's height, as we don't know if 
    // it have any children _after_ this BR box.
    $parent->extend_height($parent->_current_y);
  }

  function show(&$viewport) {}

  function to_ps($psdata) {
    $psdata->write("box-br-create\n");
    $this->to_ps_common($psdata);
    $psdata->write("dup /clear ".CSSClear::value2ps($this->clear)." put-css-value\n");
    $psdata->write("dup /font-size ".$this->font_size." put-css-value\n");
    $psdata->write("add-child\n");
  }

  function reflow_whitespace(&$linebox_started, &$previous_whitespace) {
    if (!is_inline($this)) { $linebox_started = false; };
  }
}
?>
