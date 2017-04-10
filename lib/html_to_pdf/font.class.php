<?php

// Note that ALL font dimensions are measured in 1/1000 of font size units;
//
class Font {
  var $underline_position;
  var $underline_thickness;

  function Font() {
    $this->underline_position = 0;
    $this->underline_thickness = 0;
  }

  function points($fontsize, $dimension) {
    return $dimension * $fontsize / 1000;
  }

  function underline_position() {
    return $this->underline_position;
  }

  function underline_thickness() {
    return $this->underline_thickness;
  }
}
?>