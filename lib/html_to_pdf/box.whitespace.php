<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.whitespace.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class WhitespaceBoxPDF extends GenericBoxPDF {
  var $size;
  var $decoration;
  var $family;
  var $weight;
  var $style;

  function _get_font_name() {
    global $g_font_resolver_pdf;
    return $g_font_resolver_pdf->ps_font_family($this->family, $this->weight, $this->style, $this->encoding);
  }

//   function _pdf_findfont($viewport) {
//     // FIXME: font selection

//     // 0 - fonts are not embedded (TODO)
//     // Note standart PDFLIB fonts may not be embedded (error message will appear)
//     return pdf_findfont($viewport->pdf, $this->_get_font_name(), "winansi", 0);
//   }

  function &create($viewport) {
    $box =& new WhitespaceBoxPDF($viewport);
    return $box;
  }

  function WhitespaceBoxPDF($viewport) {
    $this->encoding = $viewport->encoding('iso-8859-1');

    // Call parent constructor
    $this->GenericBoxPDF();

    // Determine font metrics
    $ascender  = $viewport->font_ascender($this->_get_font_name(), $viewport->default_encoding()) * $this->font_size;
    $descender = $viewport->font_descender($this->_get_font_name(), $viewport->default_encoding()) * $this->font_size; 

    // Setup box size:
    $this->default_baseline = $ascender; 
    $this->height           = $ascender + $descender; 

    if ($this->font_size > 0) {
      $this->width = $viewport->stringwidth(" ", $this->_get_font_name(), $viewport->default_encoding(), $this->font_size);
    } else {
      $this->width = 0;
    };
  }

  // Inherited from GenericBoxPDF
  function get_min_width(&$context) {
    return $this->get_full_width();
  }

  function get_max_width(&$context) {
    return $this->get_full_width();
  }

  // (!) SIDE EFFECT: current whitespace box can be replaced by a null box during reflow.
  // callers of reflow should take this into account and possilby check for this 
  // after reflow returns. This can be detected by UID change.
  // 
  function reflow(&$parent, &$context) {  
    // Check if there are any boxes in parent's line box
    if ($parent->line_box_empty()) {
      // The very first whitespace in the line box should not affect neither height nor baseline of the line box;
      // because following boxes can be smaller that assumed whitespace height
      // Example: <br>[whitespace]<img height="2" width="2"><br>; whitespace can overextend this line

      $this->width = 0;
      $this->height = 0;
    } elseif (is_a($parent->_line[count($parent->_line)-1],"WhitespaceBoxPDF")) {
      // Duplicate whitespace boxes should not offset further content and affect the line box length

      $this->width = 0;
      $this->height = 0;
    };

    GenericBoxPDF::reflow($parent, $context);

    // Apply 'line-height'
    $this->_apply_line_height();

    // default baseline 
    $this->baseline = $this->default_baseline;

    // append to parent line box
    $parent->append_line($this);

    // Move box to the parent current point
    $this->guess_corner($parent);

    // Offset parent's current point
    $parent->_current_x += $this->width;
    
    // Extend parent height
    $parent->extend_height($this->get_bottom_margin());

    // Update the value of current collapsed margin; pure text (non-span)
    // boxes always have zero margin

    $context->pop_collapsed_margin();
    $context->push_collapsed_margin( 0 );
  }

  function show(&$viewport) {
    // Check if font-size have been set to 0; in this case we should not draw this box at all
    if ($this->font_size == 0) { return; }

    // draw generic box
    GenericBoxPDF::show($viewport);

    // Activate font
    $viewport->setfont($this->_get_font_name(), $viewport->default_encoding(), $this->font_size);

    // draw text decoration
    $viewport->decoration($this->decoration['U'],
                          $this->decoration['O'],
                          $this->decoration['T']);
    
    // Output text with the selected font
    // note that we're using $default_baseline; 
    // the alignment offset - the difference between baseline and default_baseline values
    // is taken into account inside the get_top/get_bottom functions
    //
    $viewport->show_xy(" ", $this->get_left(), $this->get_top() - $this->default_baseline);
  }

  function reflow_whitespace(&$linebox_started, &$previous_whitespace) {
    if (!$linebox_started || 
        ($linebox_started && $previous_whitespace)) {     
      if ($this->pseudo_link_destination == "") {
        $this->parent->remove($this);
      } else {
        $this->font_height = 0.001;
        $this->height = 0;
        $this->width = 0;
      };
    };

    $previous_whitespace = true;

    // Note that there can (in theory) several whitespaces in a row, so
    // we could not modify a flag until we met a real text box
  }

  function to_ps($psdata) {
    $psdata->write("box-whitespace-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $psdata->write("dup box-text-setup\n");
    $psdata->write("add-child\n");    
  }
}
?>
