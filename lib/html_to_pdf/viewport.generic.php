<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/viewport.generic.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

class ViewportGeneric {
  var $media;
  var $bottom;
  var $left;
  var $width;
  var $height;

  // Offset (in defice points) of the current page from the first page.
  // Can be treated as coordinate of the bottom page edge (as first page 
  // will have zero Y value at its bottom).
  // Note that ir is PAGE edge coordinate, NOT PRINTABLE AREA! If you want to get
  // the position of the lowest pixel on the page which won't be cut-off, use
  // $offset+$bottom expression, as $bottom contains bottom white margin size
  var $offset;

  function default_encoding() {
    return $this->encoding('iso-8859-1');
  }

  function draw_page_border() {
    $this->setlinewidth(1);
    $this->setrgbcolor(0,0,0);

    $this->moveto($this->left, $this->bottom + $this->offset);
    $this->lineto($this->left, $this->bottom + $this->height + $this->offset);
    $this->lineto($this->left + $this->width, $this->bottom + $this->height + $this->offset);
    $this->lineto($this->left + $this->width, $this->bottom + $this->offset);
    $this->closepath();
    $this->stroke();
  }

  function rect($x, $y, $w, $h) { 
    $this->moveto($x, $y);
    $this->lineto($x + $w, $y);
    $this->lineto($x + $w, $y + $h);
    $this->lineto($x, $y + $h);
    $this->closepath();
  }

  function setup_clip() {
    $this->moveto($this->left, $this->bottom + $this->offset);
    $this->lineto($this->left, $this->bottom + $this->height + $this->offset);
    $this->lineto($this->left + $this->width, $this->bottom + $this->height + $this->offset);
    $this->lineto($this->left + $this->width, $this->bottom + $this->offset);
    $this->clip();
  }

  function ViewportGeneric(&$media) {
    $this->media  = $media;
    $this->width  = mm2pt($media->width() - $media->margins['left'] - $media->margins['right']);
    $this->height = mm2pt($media->height() - $media->margins['top'] - $media->margins['bottom']);
    $this->left   = mm2pt($media->margins['left']);
    $this->bottom = mm2pt($media->margins['bottom']);
    $this->offset = 0;
    $this->offset_delta = 0;
  }
}
?>
