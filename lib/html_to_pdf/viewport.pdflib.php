<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/viewport.pdflib.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

class ViewportPdflib extends ViewportGeneric {
  var $pdf;

  function add_link($left, $top, $width, $height, $url) {
    pdf_add_weblink($this->pdf, $left, $top-$height, $left+$width, $top, $url);
  }

  function add_local_link($left, $top, $width, $height, $anchor) {
    pdf_add_locallink($this->pdf, 
                      $left, 
                      $top-$height - $this->offset , 
                      $left+$width, 
                      $top - $this->offset, 
                      $anchor->page, 
                      "fitwidth");
  }

  function show_xy($text, $x, $y) {
    pdf_show_xy($this->pdf, $text, $x, $y);
  }
  
  function ViewportPdflib($pdf, &$media) {
    $this->ViewportGeneric($media);

    $this->pdf = $pdf;

    // No borders around links in the generated PDF
    pdf_set_border_style($this->pdf, "solid", 0);

    pdf_begin_page($this->pdf, mm2pt($this->media->width()), mm2pt($this->media->height()));
  }

  // Viewport interface functions
  function next_page() {
    pdf_end_page($this->pdf);
    pdf_begin_page($this->pdf, mm2pt($this->media->width()), mm2pt($this->media->height()));
    
    // Calculate coordinate of the next page bottom edge
    $this->offset -= $this->height - $this->offset_delta;

    // Reset the "correction" offset to it normal value
    // Note: "correction" offset is an offset value required to avoid page breaking 
    // in the middle of text boxes 
    $this->offset_delta = 0;

    pdf_translate($this->pdf, 0, -$this->offset);
  }

  function get_bottom() {
    return $this->bottom + $this->offset;
  }

  function clip() {
    pdf_clip($this->pdf);
  }

  function decoration($underline, $overline, $strikeout) {
    // underline
    pdf_set_parameter($this->pdf, "underline", $underline ? "true" : "false");
    // overline
    pdf_set_parameter($this->pdf, "overline",  $overline  ? "true" : "false");
    // line through
    pdf_set_parameter($this->pdf, "strikeout", $strikeout ? "true" : "false");
  }

  // Converts common encoding names to their PDFLIB equivalents 
  // (for example, PDFLIB does not understand iso-8859-1 encoding name,
  // but have its equivalent names winansi..)
  //
  function encoding($encoding) {
    $encoding = trim(strtolower($encoding));

    $translations = array(
                          'iso-8859-1'   => 'winansi',
                          'iso-8859-2'   => 'iso8859-2',
                          'iso-8859-3'   => 'iso8859-3',
                          'iso-8859-4'   => 'iso8859-4',
                          'iso-8859-5'   => 'iso8859-5',
                          'iso-8859-6'   => 'iso8859-6',
                          'iso-8859-7'   => 'iso8859-7',
                          'iso-8859-8'   => 'iso8859-8',
                          'iso-8859-9'   => 'iso8859-9',
                          'iso-8859-10'  => 'iso8859-10',
                          'iso-8859-13'  => 'iso8859-13',
                          'iso-8859-14'  => 'iso8859-14',
                          'iso-8859-15'  => 'iso8859-15',
                          'iso-8859-16'  => 'iso8859-16',
                          'windows-1250' => 'cp1250',
                          'windows-1251' => 'cp1251',
                          'windows-1252' => 'cp1252',
                          'symbol'       => 'winansi'
                          );

    if (isset($translations[$encoding])) { return $translations[$encoding]; };
    return $encoding;
  }

  function findfont($name, $encoding) { 
    // PDFLIB is limited by 'builtin' encoding for "Symbol" font
    if ($name == 'Symbol') { $encoding = 'builtin'; };
    return pdf_findfont($this->pdf, $name, $encoding, 1); 
  }
  function font_ascender($name, $encoding) { 
    return pdf_get_value($this->pdf, "ascender", $this->findfont($name, $encoding));
  }
  function font_descender($name, $encoding) { 
    return -pdf_get_value($this->pdf, "descender", $this->findfont($name, $encoding));
  }

  function setfont($name, $encoding, $size) {
    pdf_setfont($this->pdf, $this->findfont($name, $encoding), $size);
  }

  // PDFLIB wrapper functions
  function setrgbcolor($r, $g, $b)  { pdf_setcolor($this->pdf, "both", "rgb", $r, $g, $b, 0); }
  function moveto($x, $y)           { pdf_moveto($this->pdf, $x, $y); }
  function lineto($x, $y)           { pdf_lineto($this->pdf, $x, $y); }
  function closepath()              { pdf_closepath($this->pdf); }
  function stroke()                 { pdf_stroke($this->pdf); }
  function fill()                   { pdf_fill($this->pdf); }
  function circle($x, $y, $r)       { pdf_circle($this->pdf, $x, $y, $r); }
  function rect($x, $y, $w, $h)     { pdf_rect($this->pdf, $x, $y, $w, $h); }
  function setlinewidth($x)         { pdf_setlinewidth($this->pdf, $x); }
  function dash($x, $y)             { pdf_setdash($this->pdf, $x, $y); }

  function save() { pdf_save($this->pdf); }
  function restore() { pdf_restore($this->pdf); }

  function stringwidth($string, $name, $encoding, $size) { 
    return pdf_stringwidth($this->pdf, $string, $this->findfont($name, $encoding), $size); 
  }

  function image($image, $x, $y, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);
    $pim = pdf_open_image_file($this->pdf, "png", $tmpname, "", 0);
    pdf_place_image($this->pdf, $pim, $x, $y, $scale);
    pdf_close_image($this->pdf, $pim);
    unlink($tmpname);
  }

  function image_scaled($image, $x, $y, $scale_x, $scale_y) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    $pim = pdf_open_image_file($this->pdf, "png", $tmpname, "", 0);

    $this->save();
    pdf_translate($this->pdf, $x, $y);
    pdf_scale($this->pdf, $scale_x, $scale_y);
    pdf_place_image($this->pdf, $pim, 0, 0, 1);
    $this->restore();

    pdf_close_image($this->pdf, $pim);
    unlink($tmpname);
  }

  function image_ry($image, $x, $y, $height, $bottom, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);
    $pim = pdf_open_image_file($this->pdf, "png", $tmpname, "", 0);

    // Fill part to the bottom
    $cy = $y;
    while ($cy+$height > $bottom) {
      pdf_place_image($this->pdf, $pim, $x, $cy, $scale);
      $cy -= $height;
    };

    // Fill part to the top
    $cy = $y;
    while ($cy-$height < $y + $oy) {
      pdf_place_image($this->pdf, $pim, $x, $cy, $scale);
      $cy += $height;
    };

    pdf_close_image($this->pdf, $pim);
    unlink($tmpname);
  }

  function image_rx($image, $x, $y, $width, $right, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);
    $pim = pdf_open_image_file($this->pdf, "png", $tmpname, "", 0);

    // Fill part to the right 
    $cx = $x;
    while ($cx < $right) {
      pdf_place_image($this->pdf, $pim, $cx, $y, $scale);
      $cx += $width;
    };

    // Fill part to the left
    $cx = $x;
    while ($cx+$width >= $x - $ox) {
      pdf_place_image($this->pdf, $pim, $cx-$width, $y, $scale);
      $cx -= $width;
    };

    pdf_close_image($this->pdf, $pim);
    unlink($tmpname);
  }

  function image_rx_ry($image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);
    $pim = pdf_open_image_file($this->pdf, "png", $tmpname, "", 0);

    // Fill bottom-right quadrant
    $cy = $y;
    while ($cy+$height > $bottom) {
      $cx = $x;
      while ($cx < $right) {
        pdf_place_image($this->pdf, $pim, $cx, $cy, $scale);
        $cx += $width;
      };
      $cy -= $height;
    }

    // Fill bottom-left quadrant
    $cy = $y;
    while ($cy+$height > $bottom) {
      $cx = $x;
      while ($cx+$width > $x - $ox) {
        pdf_place_image($this->pdf, $pim, $cx, $cy, $scale);
        $cx -= $width;
      };
      $cy -= $height;
    }

    // Fill top-right quadrant
    $cy = $y;
    while ($cy < $y + $oy) {
      $cx = $x;
      while ($cx < $right) {
        pdf_place_image($this->pdf, $pim, $cx, $cy, $scale);
        $cx += $width;
      };
      $cy += $height;
    }

    // Fill top-left quadrant
    $cy = $y;
    while ($cy < $y + $oy) {
      $cx = $x;
      while ($cx+$width > $x - $ox) {
        pdf_place_image($this->pdf, $pim, $cx, $cy, $scale);
        $cx -= $width;
      };
      $cy += $height;
    }

    pdf_close_image($this->pdf, $pim);
    unlink($tmpname);
  }
}
?>
