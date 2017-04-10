<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.block.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class BlockBoxPDF extends GenericContainerBoxPDF {
  function &create(&$viewport, &$root) {
    $box = new BlockBoxPDF($viewport);
    $box->create_content($viewport, $root);
    return $box;
  }

  function &create_from_text(&$viewport, $content) {
    $box = new BlockBoxPDF($viewport);
    $box->add_child(InlineBoxPDF::create_from_text($viewport, $content));
    return $box;
  }

  function BlockBoxPDF($pdf) {
    // Call parent constructor
    $this->GenericContainerBoxPDF();

    $this->_current_x = 0;
    $this->_current_y = 0;
  }

  function _get_hor_extra() {
    return GenericBoxPDF::_get_hor_extra();
  }

  // Flow-control

  function reflow(&$parent, &$context) {
    switch ($this->position) {
    case POSITION_STATIC:
      return $this->reflow_static($parent, $context);
    case POSITION_RELATIVE:
      // CSS 2.1:
      // Once a box has been laid out according to the normal flow or floated, it may be shifted relative 
      // to this position. This is called relative positioning. Offsetting a box (B1) in this way has no
      // effect on the box (B2) that follows: B2 is given a position as if B1 were not offset and B2 is 
      // not re-positioned after B1's offset is applied. This implies that relative positioning may cause boxes
      // to overlap. However, if relative positioning causes an 'overflow:auto' box to have overflow, the UA must
      // allow the user to access this content, which, through the creation of scrollbars, may affect layout.

      $this->reflow_static($parent, $context);

      // Note that percentage positioning values are ignored for relative positioning

      // Check if 'top' value is percentage
      if ($this->top[1]) { 
        $top = 0;
      } else {
        $top = $this->top[0];
      }

      $this->offset($this->left,-$top);
      return;
      
    case POSITION_ABSOLUTE:
      return $context->add_absolute_positioned($this);
    case POSITION_FIXED:
      return $context->add_fixed_positioned($this);
    }
  }

  function reflow_absolute(&$context) {
    GenericBoxPDF::reflow($this->parent, $context);

    // Calculate actual box position relative to the containing block
    $containing_block = $this->_get_containing_block();

    $this->put_left($this->left + $containing_block['left']);

    // TODO: percentage values of 'top'
    $this->_top  = $containing_block['top'] - $this->top[0] - $this->get_extra_top();

    // As sometimes left/right values may not be set, we need to use the "fit" width here
    // if no width constraints been set, it will not be modified by the code below
    // 
    $this->put_full_width($this->get_max_width($context));
    
    // Update the width, as it should be calculated based upon containing block width, not real parent
    //
    $this->put_full_width($this->_width_constraint->apply($this->get_width(), 
                                                          $containing_block['right'] - $containing_block['left']));

    // And remove any width constraint after this, as they could refer to parent widths
    $this->put_width_constraint(new WCNone());
   
    // Reflow contents
    $this->reflow_content($context);
  }

  function reflow_fixed(&$context) {
    GenericBoxPDF::reflow($this->parent, $context);

    $this->put_left(0);
    $this->put_top(0);

    // As sometimes left/right values may not be set, we need to use the "fit" width here
    // if no width constraints been set, it will not be modified by the code below
    // 
    $this->put_full_width($this->get_max_width($context));
    
    // Update the width, as it should be calculated based upon containing block width, not real parent
    //
    $this->put_full_width($this->_width_constraint->apply($this->get_width(), $this->get_width()));

    // And remove any width constraint after this, as they could refer to parent widths
    $this->put_width_constraint(new WCNone());
   
    // Reflow contents
    $this->reflow_content($context);
  }

  function reflow_static(&$parent, &$context) {   
    if ($this->float === FLOAT_NONE) {
      $this->reflow_static_normal($parent, $context);
    } else {
      $this->reflow_static_float($parent, $context);
    }
  }

  function reflow_static_normal(&$parent, &$context) {
    GenericBoxPDF::reflow($parent, $context);

    if ($parent) { 
      // By default, child block box will fill all available parent width;
      // note that actual width will be smaller because of non-zero padding, border and margins
      $this->put_full_width($parent->get_width());

      // Calculate margin values if they have been set as a percentage
      $this->_calc_percentage_margins($parent);
      // Calculate width value if it had been set as a percentage
      $this->_calc_percentage_width($parent, $context);

      // Calculate 'auto' values of width and margins
      // Unlike tables, DIV width is either constrained by some CSS rules or 
      // expanded to the parent width; thus, we can calculate 'auto' margin 
      // values immediately
      $this->_calc_auto_width_margins($parent); 
      
      $y = $this->collapse_margin($parent, $context);

      // At this moment we have top parent/child collapsed margin at the top of context object
      // margin stack

      // Apply 'clear' property
      $y = $this->apply_clear($y, $context);

      // Store calculated Y coordinate as current Y in the parent box
      $parent->_current_y = $y;
      // Terminate current parent line-box 
      $parent->close_line($context);
      // And add current box to the parent's line-box (alone)
      $parent->append_line($this);

      // Note that top margin already used above during margin collapsing
      $this->moveto( $parent->get_left() + $this->get_extra_left(),
                     $parent->_current_y - $this->border->top->get_width()  - $this->padding->top->value );
    }

//     print("IN:".count($context->collapsed_margins)."<br>");
//     print($context->get_collapsed_margin()."<br>");

    // Reflow contents
    $this->reflow_content($context);

//     print("OUT:".count($context->collapsed_margins)."<br>");
//     print($context->get_collapsed_margin()."<br>");

    // After reflow_content we should have the top stack value replaced by the value
    // of last child bottom collapsed margin; if no children contained, then this value should be reset to 0
    if ($this->get_first() === null) {
      $cm = 0;
    } else {
      $cm = $context->get_collapsed_margin();
    };

    // Update the value, collapsing this with current box bottom margin

    $context->pop_collapsed_margin();

    $context->pop_collapsed_margin();
    $context->push_collapsed_margin( max($cm, $this->margin->bottom->value) );
    // $context->push_collapsed_margin( $this->margin->bottom->value );
   
    if ($parent) {
      // Extend parent's height to fit current box
      if ($parent->uid == $context->container_uid()) {
        $parent->extend_height($this->get_bottom_margin());
      } else {
        $parent->extend_height($this->get_bottom_border());
      }

      // Terminate parent's line box
      $parent->close_line($context);

      // Then shift current flow position to the current box margin edge
      // UNLESS parent is a table cell; in this case margin->bottom have been reset
      // to 0 at the beginning of this function
      $parent->_current_y = $this->get_bottom_border() - $context->get_collapsed_margin();
    };

    $this->check_page_break_after($parent, $context);
  }

  function show_fixed(&$viewport) {
    $this->moveto($viewport->left + $this->left,
                  $viewport->bottom + $viewport->height + $viewport->offset - $this->top[0]);

    // draw generic box
    GenericContainerBoxPDF::show($viewport);
  }

  function to_ps(&$psdata) {
    $psdata->write("box-block-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");
  }
}
?>
