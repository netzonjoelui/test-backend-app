<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/viewport.fpdf.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

class ViewportFPDF extends ViewportGeneric {
  var $pdf;

  var $locallinks;

  var $cx;
  var $cy;

  var $path;

  function add_link($x, $y, $w, $h, $target) {
    $this->_coords2pdf($x, $y);
    $this->pdf->Link($x, $y, $w, $h, $target);
  }

  function add_local_link($left, $top, $width, $height, $anchor) {
    if (!isset($this->locallinks[$anchor->name])) {
      $x = 0;
      $y = $anchor->y;
      $this->_coords2pdf($x, $y);

      $this->locallinks[$anchor->name] = $this->pdf->AddLink();
      $this->pdf->SetLink($this->locallinks[$anchor->name],
                          $y - 20,
                          $anchor->page);
    };

    $x = $left;
    $y = $top - $this->offset;
    $this->_coords2pdf($x, $y);
    
    $this->pdf->Link($x, 
                     $y, 
                     $width, 
                     $height, 
                     $this->locallinks[$anchor->name]);
  }

  function _add_path($x, $y, $draw) {
    $this->_path[] = array('x' => $x, 'y' => $y, 'draw' => $draw);
  }

  // UNfortunately, FPDF do not provide any coordinate-space transformation routines
  // so we need to reverse the Y-axis manually
  function _coords2pdf(&$x, &$y) {
    $y = mm2pt($this->media->height()) - $y;
  }

  function decoration($underline, $overline, $strikeout) {
    // underline
    $this->pdf->SetDecoration($underline, $overline, $strikeout);
  }

  function show_xy($text, $x, $y) {
    $this->_coords2pdf($x, $y);
    $this->pdf->Text($x, $y, $text);
  }
 
  function ViewportFPDF(&$pdf, &$media) {
    $this->ViewportGeneric($media);

    $this->pdf =& $pdf;
    $this->pdf->AddPage();

    $this->cx = 0;
    $this->cy = 0;

    $this->locallinks = array();
  }

  // Viewport interface functions
  function next_page() {
    $this->pdf->AddPage();
    
    // Calculate coordinate of the next page bottom edge
    $this->offset -= $this->height - $this->offset_delta;

    // Reset the "correction" offset to it normal value
    // Note: "correction" offset is an offset value required to avoid page breaking 
    // in the middle of text boxes 
    $this->offset_delta = 0;

    $this->pdf->Translate(0, -$this->offset);
  }

  function _clear_path() {
    $this->_path = array();
  }

  function clip() { 
    $this->pdf->ClipPath($this->_path);
    $this->_clear_path();
  }

  function get_bottom() {
    return $this->bottom + $this->offset;
  }

  // Converts common encoding names to their PDFLIB equivalents 
  // (for example, PDFLIB does not understand iso-8859-1 encoding name,
  // but have its equivalent names winansi..)
  //
  function encoding($encoding) {
    $encoding = trim(strtolower($encoding));

    $translations = array(
                          'windows-1250' => 'cp1250',
                          'windows-1251' => 'cp1251',
                          'windows-1252' => 'cp1252'
                          );

    if (isset($translations[$encoding])) { return $translations[$encoding]; };
    return $encoding;
  }

  function findfont($name, $encoding) { 
    // Todo: encodings handling
    return $name;
  }

  function font_ascender($name, $encoding) { 
    return $this->pdf->GetFontAscender($name, $encoding);
  }

  function font_descender($name, $encoding) { 
    return $this->pdf->GetFontDescender($name, $encoding);
  }

  function setfont($name, $encoding, $size) {
    $this->pdf->SetFont($this->findfont($name, $encoding), $encoding, '', $size);
  }

  // PDFLIB wrapper functions
  function setrgbcolor($r, $g, $b)  { 
    $this->pdf->SetDrawColor($r*255, $g*255, $b*255);
    $this->pdf->SetFillColor($r*255, $g*255, $b*255);
    $this->pdf->SetTextColor($r*255, $g*255, $b*255);
  }

  function moveto($x, $y) {  
    $this->cx = $x;
    $this->cy = $y;

    $this->_add_path($x, $y, false);
  }

  function lineto($x, $y) { 
    $this->cx = $x;
    $this->cy = $y;

    $this->_add_path($x, $y, true);
  }

  function closepath() {
    $item = $this->_path[0];
    $item['draw'] = true;
    $this->_path[] = $item;
  }

  function circle($x, $y, $r) { 
    $this->pdf->circle($x, $y, $r);
  }

  function dash($x, $y) { 
    $this->pdf->SetDash($x, $y); 
  }

  function fill() { 
    if (count($this->_path) > 0) {
      $this->pdf->FillPath($this->_path);
      $this->_clear_path();
    } else {
      $this->pdf->Fill();
    };
  }

  function restore() { 
    $this->pdf->Restore();
  }

  function save() { 
    $this->pdf->Save();
  }

  function setlinewidth($x) { 
    $this->pdf->SetLineWidth($x); 
  }

  function stroke() { 
    for ($i=0; $i<count($this->_path)-1; $i++) {
      $item1 = $this->_path[$i];
      $item2 = $this->_path[$i+1];

      if ($item2['draw']) {
        $x1 = $item1['x'];
        $y1 = $item1['y'];
        $x2 = $item2['x'];
        $y2 = $item2['y'];

        $this->_coords2pdf($x1, $y1);
        $this->_coords2pdf($x2, $y2);
        $this->pdf->Line($x1, $y1, 
                         $x2, $y2);
      };
    };
    $this->_clear_path();
  }

  function stringwidth($string, $name, $encoding, $size) { 
    $this->setfont($name, $encoding, $size);
    return $this->pdf->GetStringWidth($string);
  }

  function image($image, $x, $y, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    $this->_coords2pdf($x, $y);
    $this->pdf->Image($tmpname, $x, $y - imagesy($image) * $scale, imagesx($image) * $scale, imagesy($image) * $scale, "png");
    unlink($tmpname);
  }

  function image_scaled($image, $x, $y, $scale_x, $scale_y) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    $this->_coords2pdf($x, $y);
    $this->pdf->Image($tmpname, $x, $y - imagesy($image) * $scale_y, imagesx($image) * $scale_x, imagesy($image) * $scale_y, "png");
    unlink($tmpname);
  }

  function image_ry($image, $x, $y, $height, $bottom, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    // Fill part to the bottom
    $cy = $y;
    while ($cy+$height > $bottom) {
      $tx = $x;
      $ty = $cy + units2pt(imagesy($image)); 
      $this->_coords2pdf($tx, $ty);
      $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
      $cy -= $height;
    };

    // Fill part to the top
    $cy = $y;
    while ($cy-$height < $y + $oy) {
      $tx = $x;
      $ty = $cy + units2pt(imagesy($image)); 
      $this->_coords2pdf($tx, $ty);
      $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
      $cy += $height;
    };

    unlink($tmpname);
  }

  function image_rx($image, $x, $y, $width, $right, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    // Fill part to the right 
    $cx = $x;
    while ($cx < $right) {
      $tx = $cx;
      $ty = $y + units2pt(imagesy($image)); 
      $this->_coords2pdf($tx, $ty);
      $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
      $cx += $width;
    };

    // Fill part to the left
    $cx = $x;
    while ($cx+$width >= $x - $ox) {
      $tx = $cx-$width;
      $ty = $y + units2pt(imagesy($image)); 
      $this->_coords2pdf($tx, $ty);
      $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
      $cx -= $width;
    };

    unlink($tmpname);
  }

  function image_rx_ry($image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale) {
    $tmpname = tempnam("/tmp","IMG");
    imagepng($image, $tmpname);

    // Fill bottom-right quadrant
    $cy = $y;
    while ($cy+$height > $bottom) {
      $cx = $x;
      while ($cx < $right) {
        $tx = $cx;
        $ty = $cy;
        $this->_coords2pdf($tx, $ty);
        $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
        $cx += $width;
      };
      $cy -= $height;
    }

    // Fill bottom-left quadrant
    $cy = $y;
    while ($cy+$height > $bottom) {
      $cx = $x;
      while ($cx+$width > $x - $ox) {
        $tx = $cx;
        $ty = $cy;
        $this->_coords2pdf($tx, $ty);
        $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
        $cx -= $width;
      };
      $cy -= $height;
    }

    // Fill top-right quadrant
    $cy = $y;
    while ($cy < $y + $oy) {
      $cx = $x;
      while ($cx < $right) {
        $tx = $cx;
        $ty = $cy;
        $this->_coords2pdf($tx, $ty);
        $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
        $cx += $width;
      };
      $cy += $height;
    }

    // Fill top-left quadrant
    $cy = $y;
    while ($cy < $y + $oy) {
      $cx = $x;
      while ($cx+$width > $x - $ox) {
        $tx = $cx;
        $ty = $cy;
        $this->_coords2pdf($tx, $ty);
        $this->pdf->Image($tmpname, $tx, $ty, imagesx($image) * $scale, imagesy($image) * $scale, "png");
        $cx -= $width;
      };
      $cy += $height;
    }

    unlink($tmpname);
  }
}
?>
