<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.text.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

// TODO: from my POV, it wll be better to pass the font- or CSS-controlling object to the constructor
// instead of using globally visible functions in 'show'.

class TextBoxPDF extends GenericInlineBoxPDF {
  var $word;
  var $encoding;
  var $size;
  var $decoration;

  var $family;
  var $weight;
  var $style;

  function _get_font_name() {
    global $g_font_resolver_pdf;
    return $g_font_resolver_pdf->ps_font_family($this->family, $this->weight, $this->style, $this->src_encoding);
  }

  function &create(&$viewport, $text, $encoding) {
    $box =& new TextBoxPDF($viewport, $text, $encoding);
    return $box;
  }

  function TextBoxPDF(&$viewport, $word, $encoding) {
    // Call parent constructor
    $this->GenericBoxPDF();

    $this->word         = $word;
    $this->encoding     = $viewport->encoding($encoding);
    $this->src_encoding = $encoding;

    // Determine font metrics
    $ascender  = $viewport->font_ascender($this->_get_font_name(), $this->encoding) * $this->font_size;
    $descender = $viewport->font_descender($this->_get_font_name(), $this->encoding) * $this->font_size; 

    // Setup box size:
    $this->default_baseline = $ascender; 
    $this->height           = $ascender + $descender; 

    if ($this->font_size > 0) {
      $this->width = $viewport->stringwidth($this->word, $this->_get_font_name(), $this->encoding, $this->font_size);
    } else {
      $this->width = 0;
    };
  }

  // Inherited from GenericBoxPDF
  function get_min_width(&$context) {
    // TODO: width constraint     
    return $this->get_full_width();
  }

  function get_max_width(&$context) {
    // TODO: width constraint     
    return $this->get_full_width();
  }

  function reflow(&$parent, &$context) {  
    GenericBoxPDF::reflow($parent, $context);

    // Check if we need a line break here (possilble several times in a row, if we
    // have a long word and a floating box intersecting with this word
    // 
    // To prevent infinite loop, we'll use a limit of 100 sequental line feeds
    $i=0;
    do { $i++; } while ($this->maybe_line_break($parent, $context) && $i < 100);
   
    // Determine the baseline position and height of the text-box using line-height CSS property
    $this->_apply_line_height();
    
    // set default baseline
    $this->baseline = $this->default_baseline;

    // append current box to parent line box
    $parent->append_line($this);

    // Determine coordinates of upper-left _margin_ corner
    $this->guess_corner($parent);

    // Offset parent current X coordinate
    $parent->_current_x += $this->get_full_width();

    // Extends parents height
    $parent->extend_height($this->get_bottom_margin());

    // Update the value of current collapsed margin; pure text (non-span)
    // boxes always have zero margin

    $context->pop_collapsed_margin();
    $context->push_collapsed_margin( 0 );
  }

  function show(&$viewport) {
    // Check if font-size have been set to 0; in this case we should not draw this box at all
    if ($this->font_size == 0) { return; }

    // Check if current text box will be cut-off by the page edge
    // Get Y coordinate of the top edge of the box
    $top    = $this->get_top_margin();
    // Get Y coordinate of the bottom edge of the box
    $bottom = $this->get_bottom_margin();

    $top_inside    = $top > $viewport->get_bottom();
    $bottom_inside = $bottom > $viewport->get_bottom();

    if ($top_inside && !$bottom_inside) {
      // If yes, do not draw current text box at all; add an required value
      // to the viewport page offset to make the text box fully visible on the next page
      $viewport->offset_delta = max($viewport->offset_delta, $top - $viewport->get_bottom());
      return;
    };

    if (!$top_inside && !$bottom_inside) { return; }

    // draw generic box
    GenericBoxPDF::show($viewport);

    // Activate font
    $viewport->setfont($this->_get_font_name(), $this->encoding, $this->font_size);

    // draw text decoration
    $viewport->decoration($this->decoration['U'],
                          $this->decoration['O'],
                          $this->decoration['T']);
    
    // Output text with the selected font
    // note that we're using $default_baseline; 
    // the alignment offset - the difference between baseline and default_baseline values
    // is taken into account inside the get_top/get_bottom functions
    //
    $viewport->show_xy($this->word, $this->get_left(), $this->get_top() - $this->default_baseline);
  }

  function reflow_whitespace(&$linebox_started, &$previous_whitespace) {
    $linebox_started = true;
    $previous_whitespace = false;
    return;
  }

  function is_null() { return false; }

  function offset($dx, $dy) {
    GenericInlineBoxPDF::offset($dx, $dy);
  }

  function to_ps(&$psdata) {
    $psdata->write("box-text-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);

    global $g_font_resolver;
    if (!$g_font_resolver->font_resolved($this->family, $this->weight, $this->style, $this->encoding)) {
      $font = $g_font_resolver->resolve_font($this->family, $this->weight, $this->style, $this->encoding);
      $family = $g_font_resolver->ps_font_family($this->family, $this->weight, $this->style, $this->src_encoding);
      $psdata->write("/".$font." ".$family." ".$this->encoding." findfont-enc def\n");
    } else {
      $font = $g_font_resolver->resolve_font($this->family, $this->weight, $this->style, $this->encoding);
    };

    $psdata->write("dup /font-family $font put-css-value\n");

    $psdata->write("(".quote_ps($this->word).") 1 index put-text\n");

    if ($this->encoding != "ISOLatin1Encoding") {
      $psdata->write($this->encoding." 1 index put-encoding\n");
    };

    $psdata->write("dup box-text-setup add-child\n");    
  }
}
?>
