<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.block.inline.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class InlineBlockBoxPDF extends GenericContainerBoxPDF {
  function &create($viewport, &$root) {
    $box = new InlineBlockBoxPDF($viewport, $root);
    $box->create_content($viewport, $root);
    return $box;
  }

  function InlineBlockBoxPDF($pdf, &$root) {
    // Call parent constructor
    $this->GenericContainerBoxPDF();

    $this->_current_x = 0;
    $this->_current_y = 0;
  }

  // Flow-control

  function reflow(&$parent, &$context) {
    // We may not worry about position: absolute and position: fixed, 
    // as, according to CSS 2.1 paragraph 9.7, these values of 'position' 
    // will cause 'display' value to change to either 'block' or 'table';
    // thus, we'll never get here in that case.

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
    }
  }

  function reflow_static(&$parent, &$context) {
//     // Get the last element flown in parent (candidate to collapse margins with)
//     // NOTE the line box could be empty, if this element was block box.
//     $last = $parent ? $parent->_get_last_box() : null;

    GenericBoxPDF::reflow($parent, $context);

    if ($parent) { 
      // Calculate margin values if they have been set as a percentage
      $this->_calc_percentage_margins($parent);
      // Calculate width value if it had been set as a percentage
      $this->_calc_percentage_width($parent, $context);
      // Calculate 'auto' values of width and margins
      $this->_calc_auto_width_margins($parent); 

      // And add current box to the parent's line-box (alone)
      $parent->append_line($this);

      $this->guess_corner($parent);

      // By default, child block box will fill all available parent width;
      // note that actual width will be smaller because of non-zero padding, border and margins
      $this->put_full_width($parent->get_width());
    }

    // Reflow contents
    $this->reflow_content($context);
    
    if ($parent) {
      // Extend parent's height to fit current box
      $parent->extend_height($this->get_bottom_margin());

      // Offset current x coordinate of parent box
      $parent->_current_x = $this->get_right_margin();
    };
  }

//   function show(&$pdf) {
//     // draw generic box
//     GenericContainerBoxPDF::show($pdf);
//   }

  function to_ps(&$psdata) {
    $psdata->write("box-inline-block-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");        
  }

}
?>
