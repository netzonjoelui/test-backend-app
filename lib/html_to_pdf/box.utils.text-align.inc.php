<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.utils.text-align.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

define('MAX_JUSTIFY_FRACTION',0.33);

function ta_left(&$box, $lastline) {
  // Do nothing; text is left-aligned by default
}

function ta_center(&$box, $lastline) {
  $delta = $box->_line_length_delta() / 2;

  for ($i=0; $i< count($box->_line); $i++) {
    $box->_line[$i]->offset($delta, 0);
  }
}

function ta_right(&$box, $lastline) {
  $delta = $box->_line_length_delta();

  for ($i=0; $i< count($box->_line); $i++) {
    $box->_line[$i]->offset($delta, 0);
  }
}

function ta_justify(&$box, $lastline) {
  // last line is never justified
  if ($lastline) { return; }

  // If line box contains less that two items, no justification can be done, just return
  if (count($box->_line) < 2) { return; }

  // Calculate extra space to be filled by this line
  $delta = $box->_line_length_delta();

  // note that if it is the very first line inside the container, 'text-indent' value
  // should not be taken into account while calculating delta value
  if ($box->content[0]->uid === $box->_line[0]->uid) {
    $delta -= $box->text_indent->calculate($box);
  };

  // if line takes less that MAX_JUSTIFY_FRACTION of available space, no justtification should be done
  if ($delta > $box->_line_length() * MAX_JUSTIFY_FRACTION) {
    return;
  };

  // Calculate offset for each box
  $offset = $delta / (count($box->_line) - 1);

  // Offset all boxes in current line box
  for ($i=0; $i < count($box->_line); $i++) {
    $box->_line[$i]->offset($offset*$i, 0);
  };
}
?>
