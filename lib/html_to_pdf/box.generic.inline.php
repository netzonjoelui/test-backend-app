<?php
class GenericInlineBoxPDF extends GenericContainerBoxPDF {

  // Checks if current inline box should cause a line break inside the parent box
  //
  // @param $parent reference to a parent box
  // @param $content flow context
  // @return true if line break occurred; false otherwise
  //
  function maybe_line_break(&$parent, &$context) {
    if (!$parent->line_break_allowed()) { return false; };

    // Calculate the x-coordinate of this box right edge 
    $right_x = $this->get_full_width() + $parent->_current_x;

    $need_break = false;

    // Check for right-floating boxes
    // If upper-right corner of this inline box is inside of some float, wrap the line
    if ($context->point_in_floats($right_x, $parent->_current_y)) {
      $need_break = true;
    };

    // No floats; check if we had run out the right edge of container
    // TODO: nobr-before, nobr-after
    if (($right_x > $parent->get_right()+EPSILON)) {
      // Now check if parent line box contains any other boxes;
      // if not, we should draw this box unless we have a floating box to the left

      $first = $parent->get_first();

      // FIXME: what's this? This condition is invariant!
      $indent_offset = ($first->uid == $this->uid || 1) ? $parent->text_indent->calculate($parent) : 0;

      if ($parent->_current_x > $parent->get_left() + $indent_offset + EPSILON) {
        $need_break = true;
      };
    }

    // As close-line will not change the current-Y parent coordinate if no 
    // items were in the line box, we need to offset this explicitly in this case
    //
    if (count($parent->_line) == 0 && $need_break) {
      $parent->_current_y -= $this->get_height();
    };

    if ($need_break) { $parent->close_line($context); };

    return $need_break;
  }
}
?>
