<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.button.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

define('DEFAULT_SUBMIT_TEXT','Submit');
define('DEFAULT_RESET_TEXT' ,'Reset');
define('DEFAULT_BUTTON_TEXT',' ');

class ButtonBoxPDF extends InlineBoxPDF {
  function ButtonBoxPDF(&$viewport, &$root) {
    // Call parent constructor
    $this->InlineBoxPDF($viewport);

    // Button height includes vertical extra space; adjust the height constraint
    $hc = $this->get_height_constraint();
    if ($hc->constant !== null) {
      $hc->constant[0] -= $this->_get_vert_extra();
    };
    $this->put_height_constraint($hc);
    
    // Determine the button text 
    if ($root->has_attribute("value")) {
      $text = $root->get_attribute("value");
    } else {
      switch ($root->tagname()) {
      case "submit":
        $text = DEFAULT_SUBMIT_TEXT;
        break;
      case "reset":
        $text = DEFAULT_RESET_TEXT;
        break;
      case "button":
        $text = DEFAULT_BUTTON_TEXT;
        break;
      default:
        $text = DEFAULT_BUTTON_TEXT;
        break;
      }
    };

    // If button width is not constrained, then we'll add some space around the button text
    $text = " ".$text." ";

    $ibox = InlineBoxPDF::create_from_text($viewport, $text);
    for ($i=0; $i<count($ibox->content); $i++) {
      $this->add_child($ibox->content[$i]);
    };
  }

  function &create($viewport, &$root) {
    $box =& new ButtonBoxPDF($viewport, $root);
    return $box;
  }

  // get_max_width is inherited from GenericContainerBox
  function get_min_width(&$context) { 
    return $this->get_max_width($context);
  }
  
  function get_max_width(&$context) { 
    return GenericContainerBoxPDF::get_max_width($context); 
  }

  function show(&$viewport) {   
    GenericContainerBoxPDF::show($viewport);
  }

  function line_break_allowed() { return false; }

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

    // center the button text vertically inside the button
    $text =& $this->content[0];
    $delta = ($text->get_top() - $text->get_height()/2) - ($this->get_top() - $this->get_height()/2);
    $text->offset(0,-$delta);

    // Now set the baseline of a button box to align it vertically when flowing isude the 
    // text line
    $this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
    $this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

    // Offset parent current X coordinate
    $parent->_current_x += $this->get_full_width();

    // Extends parents height
    $parent->extend_height($this->get_bottom_margin());
  }

  function to_ps(&$psdata) {
    $psdata->write("box-button-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");    
  }
}
?>
