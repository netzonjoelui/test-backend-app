<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.input.text.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

define('DEFAULT_TEXT_SIZE',20);
/// define('SIZE_SPACE_KOEFF',1.65); (defined in tag.input.inc.php)

class TextInputBoxPDF extends InlineBoxPDF {
  function &create($viewport, &$root) {
    $box =& new TextInputBoxPDF($viewport, $root);
    return $box;
  }

  function TextInputBoxPDF(&$viewport, &$root) {
    // Call parent constructor
    $this->InlineBoxPDF($viewport);

    // Control size
    $size = (int)$root->get_attribute("size"); 
    if (!$size) { $size = DEFAULT_TEXT_SIZE; };
    
    // Text to be displayed
    if ($root->has_attribute('value')) {
      $text = str_pad($root->get_attribute("value"), $size, " ");
    } else {
      $text = str_repeat(" ",$size*SIZE_SPACE_KOEFF);
    };

    // TODO: international symbols! neet to use somewhat similar to 'process_word' in InlineBoxPDF
    push_css_text_defaults();
    $this->add_child(TextBoxPDF::create($viewport, $text, 'iso-8859-1'));
    pop_css_defaults();
  }

  // get_max_width is inherited from GenericContainerBox
  function get_min_width(&$context) { 
    return $this->get_max_width($context);
  }

  function reflow(&$parent, &$context) {  
    GenericBoxPDF::reflow($parent, $context);
    
    // Check if we need a line break here
    $this->maybe_line_break($parent, $context);

    // append to parent line box
    $parent->append_line($this);

    // Determine coordinates of upper-left _margin_ corner
    $this->guess_corner($parent);

    // Determine the box width
    $this->put_full_width($this->get_min_width($context));

    $this->reflow_content($context);

    // Vertical-align
    $this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
    $this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

    // Offset parent current X coordinate
    $parent->_current_x += $this->get_full_width();
    // Extends parents height
    $parent->extend_height($this->get_bottom_margin());
  }

  function show(&$viewport) {   
    GenericContainerBoxPDF::show($viewport);
  }

  function line_break_allowed() { return false; }

  function to_ps(&$psdata) {
    $psdata->write("box-input-text-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");
  }
}
?>
