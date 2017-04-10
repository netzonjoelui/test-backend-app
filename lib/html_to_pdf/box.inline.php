<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.inline.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class LineBox {
  var $top;
  var $right;
  var $bottom;
  var $left;

  function offset($dx, $dy) {
    $this->top    += $dy;
    $this->bottom += $dy;
    $this->left   += $dx;
    $this->right  += $dx;
  }

  function LineBox(&$box) {
    $this->right = $box->get_right();
    $this->bottom = $box->get_bottom();
    $this->left = $box->get_left();
    $this->top = $box->get_top();
  }

  function extend(&$box) {
    $this->top = max($this->top, $box->get_top());
    $this->right = max($this->right, $box->get_right());
    $this->bottom = min($this->bottom, $box->get_bottom());

    // Left edge of the line box should never be modified
  }

  function fake_box(&$box) {
    // Create the fake box object
    push_css_defaults();
    $fake_box = new BlockBoxPDF(null);
    pop_css_defaults();

    // Setup fake box size
    $fake_box->put_left($this->left);
    $fake_box->put_width($this->right - $this->left);
    $fake_box->put_top($this->top - $box->baseline);
    $fake_box->put_height($this->top - $this->bottom);

    // Setup padding value
    $fake_box->padding = $box->padding;

    // Setup fake box border and background
    $fake_box->background = $box->background;
    $fake_box->border = $box->border;
    
    return $fake_box;
  }
}

class InlineBoxPDF extends GenericInlineBoxPDF {
  function &create(&$pdf, &$root) {
    // Create contents of this inline box
    if ($root->node_type() == XML_TEXT_NODE) {
      return InlineBoxPDF::create_from_text($pdf, $root->content);

    } else {
      $box =& new InlineBoxPDF($pdf);

      // Initialize content
      $child = $root->first_child();
      while ($child) {
        $child_box =& create_pdf_box($pdf, $child);
        $box->add_child($child_box);
        $child = $child->next_sibling();
      };

      // Add fake whitespace box with zero size for the anchor spans 
      // We need this, as "reflow" functions will automatically remove empty inline boxes from the 
      // document tree
      //
      if ($box->is_null()) {
        push_css_defaults();
        pop_font_size();
        push_font_size('0.01pt');

        $whitespace = new WhitespaceBoxPDF($pdf);

        $box->add_child($whitespace);        
        pop_css_defaults();
      };
    }

    return $box;
  }

  function &create_from_text(&$pdf, $text) {
    $box =& new InlineBoxPDF($pdf);

    // Apply/inherit text-related CSS properties 
    push_css_text_defaults();
    
    if ($box->white_space == WHITESPACE_PRE) {
      $box->init_white_space_pre($pdf, $text);
    } else {
      $box->init_white_space_normal($pdf, $text);
    };
    
    // Clear the CSS stack
    pop_css_defaults();

    return $box;
  }

  function InlineBoxPDF(&$pdf) {
    // Clear the content
    //    $this->content = array();

    // Clear the list of line boxes inside this box
    $this->_lines = array();

    // Call parent's constructor
    $this->GenericInlineBoxPDF();

//     print("CONSTRUCTOR ".$this->uid.":");
//     print_r($this->background);
  }

  function init_white_space_pre(&$pdf, $raw_content) {
    // Convert text content to series of lines
    $lines = preg_split("/[\r\n]/",$raw_content);

    for ($i=0; $i<count($lines); $i++) {
      $line = $lines[$i];
      $this->process_word($pdf, $line);
      $this->add_child(new BRBoxPDF($pdf));
    };
  }

  // Note: as we're trying to use unicode, we must beware that a part of unicode character can match generic \s 
  // declaration; we'll limit ourselves by [\r\n\t ] set!
  //
  function init_white_space_normal(&$pdf, $raw_content) {
    $content = preg_replace("/[\r\n\t ]/",' ',$raw_content);

    // Whitespace-only text nodes sill result on only one whitespace box
    if (trim($content) === "") {
      $this->add_child(WhitespaceBoxPDF::create($pdf));
      return;
    }

    if (preg_match("# #",substr($content,0,1))) {
      $this->add_child(WhitespaceBoxPDF::create($pdf));
    }

    $words = preg_split("/ /",$content);     
    $prefix = "";
    for ($i=0; $i<count($words); $i++) {
      $word = $prefix.$words[$i];

      // Skip zero-length words
      if (strlen($word) == 0) { continue; }

      // Check if this word is terminated by a partially-completed 
      // unicode symbol; in this case we've made a break here incorrectly on
      // the non-breaking space
      // 
      // So, we'll concatenate whis with with the next word
      // dropping partially parsed unicode symbol and replacing it by a space
      //
      if ($word{strlen($word)-1} == chr(0xC2)) {
        $prefix = substr($word,0,strlen($word)-1)." ";
        continue;
      };
      $prefix = "";
      
      if ($word !== "") {
        $this->process_word($pdf, $word);
        
        // we need to make space between words in 2 cases: 
        // 1. if there will be another words in the same text node
        // 2. if it is the last words AND there's space(s) at the end of the text content.
        //    e.g.: text<b>xxx </font>some more text
        if ($i < count($words)-1 || preg_match("#\s#",substr($content,strlen($content)-1,1))) { 
          $this->add_child(WhitespaceBoxPDF::create($pdf));
        };
      };
    };
  }

  // Inherited from GenericBoxPDF

  function process_word(&$pdf, $raw_content) {
    if ($raw_content === "") { return false; }

    global $g_utf8_to_encodings_mapping_pdf;

    $ptr      = 0;
    $word     = "";
    $encoding = "iso-8859-1";

    while ($ptr < strlen($raw_content)) {
      if ((ord($raw_content{$ptr}) & 0xF0) == 0xF0) {
        $charlen = 4;
      } elseif ((ord($raw_content{$ptr}) & 0xE0) == 0xE0) {
        $charlen = 3;
      } elseif ((ord($raw_content{$ptr}) & 0xC0) == 0xC0) {
        $charlen = 2;
      } else {
        $charlen = 1;
      };

      $char = substr($raw_content,$ptr,$charlen);

      if (!isset($g_utf8_to_encodings_mapping_pdf[$char])) {
        $ch_hex = "";
        for ($i=0; $i<strlen($char); $i++) {
          $ch_hex .= sprintf("%x",ord($char[$i]));
        };
        error_log("Unknown utf8 character:".$ch_hex);
        $char = "?";
      };

      $mapping = $g_utf8_to_encodings_mapping_pdf[$char];

      if (isset($mapping[$encoding])) {
        $add = $mapping[$encoding];
        $word .= $add;
      } else {
        // This condition prevents empty text boxes from appearing; say, if word starts with a national 
        // character, an () - text box with no letters will be generated, in rare case causing a random line 
        // wraps, if container is narrow
        if ($word !== "") {
          $this->add_child(TextBoxPDF::create($pdf, $word, $encoding));
        };

        $encodings = array_keys($mapping);
        $encoding = $encodings[0];

        $add = $mapping[$encoding];

        $word = $add;
      };

      $ptr += $charlen;
    };

    if ($word !== "") {
      $this->add_child(TextBoxPDF::create($pdf, $word, $encoding));
      return true;
    };

    return false;
  }

  function show(&$pdf) {
    // Show line boxes background and borders
    for ($i=0; $i<count($this->_lines); $i++) {
      $fake_box = $this->_lines[$i]->fake_box($this);

      $this->background->show($pdf, $fake_box);
      $this->border->show($pdf, $fake_box);
    };

    // Show content
    for ($i=0; $i < count($this->content); $i++) {
      $this->content[$i]->show($pdf);
    }
  }

  // Initialize next line box inside this inline 
  //
  // Adds the next element to _lines array inside the current object and initializes it with the 
  // $box parameters
  // 
  // @param $box child box which will be first in this line box
  // @param $line_no number of line box
  //
  function init_line(&$box, &$line_no) {
    $line_box = new LineBox($box);
    $this->_lines[$line_no] = $line_box;
  }

  // Extends the existing line box to include the given child 
  // OR starts new line box, if current child is to the left of the box right edge 
  // (which should not happen white the line box is filled)
  //
  // @param $box child box which will be first in this line box
  // @param $line_no number of line box
  //
  function extend_line(&$box, $line_no) {
    if (!isset($this->_lines[$line_no])) {
      // New line box started
      $this->init_line($box, $line_no);
      
      return $line_no;
    };

    // Check if this box starts a new line
    if ($box->get_left() < $this->_lines[$line_no]->right) {
      $line_no++;
      $this->init_line($box, $line_no);
      return $line_no;
    };

    $this->_lines[$line_no]->extend($box);

    return $line_no;
  }

  function merge_line(&$box, $line_no) {
    foreach ($box->_lines as $line) {
      $this->_lines[] = $line;
    };

    return count($this->_lines);
  }
  
  function reflow(&$parent, &$context) {
    GenericBoxPDF::reflow($parent, $context);

    // Note that inline boxes (actually SPANS)
    // are never added to the parent's line boxes

    // Move current box to the parent's current coordinates
    // Note that span box will start at the far left of the parent, NOT on its current X!
    // Also, note that inline box can have margins, padding and borders!

    $this->put_left($parent->get_left());
    $this->put_top($parent->get_top() - $this->get_extra_top());

    // first line of the SPAN will be offset to its parent current-x
    // PLUS the left padding of current span!
    $parent->_current_x += $this->get_extra_left();
    $this->_current_x = $parent->_current_x;

    // Note that the same operation IS NOT applied to parent current-y!
    // The padding space is just extended to the top possibly OVERLAPPING the above boxes.

    // inline boxes can fill the whole width of its parent
    // $this->put_full_width($parent->get_width());
    $this->width = 0;

    // Reflow contents
    for ($i=0; $i<count($this->content); $i++) {
      $child =& $this->content[$i];

      // Add current element into _parent_ line box and reflow it
      $child->reflow($parent, $context);

      // Note that whitespace child could be replaced by a null box in its 
      // reflow function; thus, we'll need to re-start reflow in 
      // we've got another box in this place
//       if ($this->content[$i]->uid != $old_uid) {
//         $nullbox =& $this->content[$i];
//         $nullbox->reflow($parent, $context);
//         print("!!!<br>");
//       };
      
      // In general, if inline box centained whitespace box only, 
      // it could be removed during reflow function call;
      // let's check it and skip to next child
      // 
      // if no children left AT ALL (so this box is empty), just exit
      
      // Track the real height of the inline box; it will be used by other functions 
      // (say, functions calculating content height)

      $this->extend_height($child->get_bottom_margin());
    };

    //    $parent->extend_height($this->get_bottom_margin());

    // Apply right extra space value (padding + border + margin)
    $parent->_current_x += $this->get_extra_right();

    // Margins of inline boxes are not collapsed

    if ($this->get_first_data()) {
      $context->pop_collapsed_margin();
      $context->push_collapsed_margin( 0 );
    };
  }

  function reflow_inline() {
    $line_no = 0;
    for ($i=0; $i<count($this->content); $i++) {
      $child = $this->content[$i];

      $child->reflow_inline();

      if (is_a($child,"InlineBoxPDF")) {
        $line_no = $this->merge_line($child, $line_no);
      } else {
        $line_no = $this->extend_line($child, $line_no);        
      };
    };
  }

  function reflow_whitespace(&$linebox_started, &$previous_whitespace) {
    for ($i=0; $i<count($this->content); $i++) {
      $child =& $this->content[$i];

      $child->reflow_whitespace($linebox_started, $previous_whitespace);      
    };

    if ($this->is_null()) {
      $this->parent->remove($this);
    };
  }

  function get_extra_line_left() { 
    return $this->get_extra_left() + ($this->parent ? $this->parent->get_extra_line_left() : 0);
  }

  function get_extra_line_right() { 
    return $this->get_extra_right() + ($this->parent ? $this->parent->get_extra_line_right() : 0);
  }

  function offset($dx, $dy) {
    GenericInlineBoxPDF::offset($dx, $dy);

    for ($i=0; $i<count($this->_lines); $i++) {
      $this->_lines[$i]->offset($dx, $dy);
    };
  }

  function to_ps(&$psdata) {
    $psdata->write("box-inline-create\n");
    $this->to_ps_common($psdata);
    $this->to_ps_css($psdata);
    $this->to_ps_content($psdata);
    $psdata->write("add-child\n");    
  }
}
?>
