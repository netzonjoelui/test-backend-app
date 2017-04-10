<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.select.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

define('SELECT_BUTTON_TRIANGLE_PADDING',1.5);

class SelectBoxPDF extends GenericContainerBoxPDF {
  function &create($viewport, &$root) {
    $box =& new SelectBoxPDF($viewport, $root);
    return $box;
  }

  function SelectBoxPDF(&$viewport, &$root) {
    // Call parent constructor
    $this->GenericContainerBoxPDF();

    // Determine the option to be shown
    $child = $root->first_child();
    $content = "";
    $size = 0;
    while ($child) {
      if ($child->node_type() == XML_ELEMENT_NODE) {
        $size = max($size, strlen($child->get_content()));
        if (empty($content) || $child->has_attribute("selected")) { $content = $child->get_content(); };
      };
      $child = $child->next_sibling();
    };

    $content = str_pad($content, $size*SIZE_SPACE_KOEFF, " ");
    
    // TODO: international symbols! need to use somewhat similar to 'process_word' in InlineBoxPDF
    push_css_text_defaults();
    $this->add_child(TextBoxPDF::create($viewport, 
                                        $content, 
                                        'iso-8859-1'));
    pop_css_defaults();
  }

  function reflow(&$parent, &$context) {  
    GenericBoxPDF::reflow($parent, $context);
    
    // append to parent line box
    $parent->append_line($this);

    // Determine coordinates of upper-left _margin_ corner
    $this->guess_corner($parent);

    // Determine the box width
    $this->put_full_width($this->get_max_width($context));

    $this->reflow_content($context);

    $context->pop_collapsed_margin();
    $context->push_collapsed_margin( 0 );
    
    $this->baseline = 
      $this->content[0]->baseline + 
      $this->get_extra_top();
    
    $this->default_baseline = $this->baseline;

//     // Vertical-align
//     $this->_apply_vertical_align($parent);

    // Offset parent current X coordinate
    $parent->_current_x += $this->get_full_width();

    // Extends parents height
    $parent->extend_height($this->get_bottom_margin());
  }

  function show(&$viewport) {   
    GenericContainerBoxPDF::show($viewport);

    $button_height = $this->get_height() + $this->padding->top->value + $this->padding->bottom->value;

    // Show arrow button box
    $viewport->setrgbcolor(0.93, 0.93, 0.93);
    $viewport->moveto($this->get_right_padding(), $this->get_top_padding());
    $viewport->lineto($this->get_right_padding() - $button_height, $this->get_top_padding());
    $viewport->lineto($this->get_right_padding() - $button_height, $this->get_bottom_padding());
    $viewport->lineto($this->get_right_padding(), $this->get_bottom_padding());
    $viewport->closepath();
    $viewport->fill();

    // Show box boundary
    $viewport->setrgbcolor(0,0,0);
    $viewport->moveto($this->get_right_padding(), $this->get_top_padding());
    $viewport->lineto($this->get_right_padding() - $button_height, $this->get_top_padding());
    $viewport->lineto($this->get_right_padding() - $button_height, $this->get_bottom_padding());
    $viewport->lineto($this->get_right_padding(), $this->get_bottom_padding());
    $viewport->closepath();
    $viewport->stroke();
  
    // Show arrow
    $viewport->setrgbcolor(0,0,0);
    $viewport->moveto($this->get_right_padding() - SELECT_BUTTON_TRIANGLE_PADDING,
                      $this->get_top_padding() - SELECT_BUTTON_TRIANGLE_PADDING);
    $viewport->lineto($this->get_right_padding() - $button_height + SELECT_BUTTON_TRIANGLE_PADDING, 
                      $this->get_top_padding() - SELECT_BUTTON_TRIANGLE_PADDING);
    $viewport->lineto($this->get_right_padding() - $button_height/2, $this->get_bottom_padding() + SELECT_BUTTON_TRIANGLE_PADDING);
    $viewport->closepath();
    $viewport->fill();
  }

  function to_ps(&$psdata) {
    $psdata->write("box-select-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");
  }
}
?>
