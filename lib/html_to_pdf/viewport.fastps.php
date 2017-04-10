<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/viewport.fastps.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

class ViewportFastPS extends ViewportGeneric {
  function add_link($x, $y, $w, $h, $target) { 
    $this->_out(sprintf("[ /Rect [ %.2f %.2f %.2f %.2f ] /Action << /Subtype /URI /URI (%s) /value get >> /Border [0 0 0] /Subtype /Link /ANN pdfmark",
                        $x, $y, $x+$w, $y+$h, quote_ps($target)));
  }

  function add_local_link($left, $top, $width, $height, $anchor) { 
  }

  function circle($x, $y, $r) { 
    $this->_out(sprintf("%.2f %.2f %.2f 0 360 arc", $x, $y, $r));
  }

  function clip() {
    $this->_out("clip");
  }

  function closepath() {
    $this->_out("closepath");
  }

  function dash($x, $y) { 
    $this->_out(sprintf("[%.2f %.2f] 0 setdash", $x, $y));
  }
  
  function decoration($underline, $overline, $linethrough) {
    $this->underline   = $underline;
    $this->overline    = $overline;
    $this->linethrough = $linethrough;
  }
  
  function encoding($encoding) {
    $encoding = trim(strtolower($encoding));

    $translations = array(
                          'iso-8859-1'   => "ISOLatin1Encoding",
                          'iso-8859-2'   => "ISO-8859-2-Encoding",
                          'iso-8859-3'   => "ISO-8859-3-Encoding",
                          'iso-8859-4'   => "ISO-8859-4-Encoding",
                          'iso-8859-5'   => "ISO-8859-5-Encoding",
                          'iso-8859-7'   => "ISO-8859-7-Encoding",
                          'iso-8859-9'   => "ISO-8859-9-Encoding",
                          'iso-8859-10'  => "ISO-8859-10-Encoding",
                          'iso-8859-11'  => "ISO-8859-11-Encoding",
                          'iso-8859-13'  => "ISO-8859-13-Encoding",
                          'iso-8859-14'  => "ISO-8859-14-Encoding",
                          'iso-8859-15'  => "ISO-8859-15-Encoding",
                          'dingbats'     => "Dingbats-Encoding",
                          'symbol'       => "Symbol-Encoding",
                          'koi8-r'       => "KOI8-R-Encoding",
                          'cp1250'       => "Windows-1250-Encoding",
                          'cp1251'       => "Windows-1251-Encoding",
                          'windows-1250' => "Windows-1250-Encoding",
                          'windows-1251' => "Windows-1251-Encoding",
                          'windows-1252' => "Windows-1252-Encoding"
                          );

    if (isset($translations[$encoding])) { return $translations[$encoding]; };
    return $encoding;
  }

  function fill() { 
    $this->_out("fill");
  }
  
  function font_ascender($name, $encoding) {}

  function font_descender($name, $encoding) {}

  function get_bottom() {}
  function image($image, $x, $y, $scale) {}
  function image_scaled($image, $x, $y, $scale_x, $scale_y) { }
  function image_ry($image, $x, $y, $height, $bottom, $ox, $oy, $scale) { }
  function image_rx($image, $x, $y, $width, $right, $ox, $oy, $scale) { }
  function image_rx_ry($image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale) { }

  function lineto($x, $y) { 
    $this->_out("lineto");
  }

  function moveto($x, $y) { 
    $this->_out("moveto");
  }

  function next_page() {}

  function restore() { 
    $this->_out("grestore");
  }

  function save() { 
    $this->_out("gsave");    
  }

  function setfont($name, $encoding, $size) {
    $this->fontsize    = $size;
    $this->currentfont = $this->_findfont($name, $encoding);
  }

  function setlinewidth($x) { 
    $this->_out(sprintf("%.2f setlinewidth", $x));
  }

  function setrgbcolor($r, $g, $b)  { 
    $this->_out(sprintf("%.2f %.2f %.2f setrgbcolor", $r, $g, $b));
  }

  function show_xy($text, $x, $y) {
    $this->moveto($x, $y);
    $this->_out("(".quote_ps($text).") show");

    if ($this->overline)    { $this->_show_overline($text, $x, $y);  };
    if ($this->underline)   { $this->_show_underline($text, $x, $y); };
    if ($this->linethrough) { $this->_show_underline($text, $x, $y); };
  }

  function stringwidth($string, $name, $encoding, $size) { }
  
  function stroke() { 
    $this->_out("stroke");
  }

  function ViewportFastPS(&$data, &$media) { 
    $this->ViewportGeneric($media);

    $this->data = $data;
  }

  // --- viewport-specific functions
  function _findfont($name, $encoding) {
    return new Font();
  }

  function _out($string) {
    fputs($this->data, $string."\n");
  }

  function _show_underline() {
    $up = Font::points($this->fontsize, $this->currentfont->underline_position());
    $ut = Font::points($this->fontsize, $this->currentfont->underline_thickness());

    
  }
}
?>
