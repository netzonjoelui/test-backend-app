<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/_viewport.interface.php,v 1.2 2006/01/30 00:39:18 administrator Exp $

class ViewportFastPS extends ViewportGeneric {
  function add_link($x, $y, $w, $h, $target) { }
  function add_local_link($left, $top, $width, $height, $anchor) { }
  function circle($x, $y, $r) { }
  function clip() {}
  function closepath() {}
  function dash($x, $y) { }
  function decoration($underline, $overline, $strikeout) { }
  function encoding($encoding) {}
  function fill() { }
  function font_ascender($name, $encoding) {}
  function font_descender($name, $encoding) {}
  function get_bottom() {}
  function image($image, $x, $y, $scale) {}
  function image_scaled($image, $x, $y, $scale_x, $scale_y) { }
  function image_ry($image, $x, $y, $height, $bottom, $ox, $oy, $scale) { }
  function image_rx($image, $x, $y, $width, $right, $ox, $oy, $scale) { }
  function image_rx_ry($image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale) { }
  function lineto($x, $y) { }
  function moveto($x, $y) { }
  function next_page() {}
  function restore() { }
  function save() { }

  // Note that there's no functions for setting bold/italic style of the fonts;
  // you must keep in mind that roman/bold/italic font variations are, in fact, different 
  // fonts stored in different files and it is the business of the font resolver object to
  // find the appropriate font name. Here we just re-use it.
  //
  function setfont($name, $encoding, $size) {}

  function setlinewidth($x) { }
  function setrgbcolor($r, $g, $b)  { }
  function show_xy($text, $x, $y) {}
  function stringwidth($string, $name, $encoding, $size) { }
  function stroke() { }
  function ViewportFastPS(&$pdf, &$media) { }
}
?>
