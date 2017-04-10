<?php

class CSSBackgroundValue {
  var $color;
  var $image;
  var $repeat;
  var $position;

  function copy() {
    $value = new CSSBackgroundValue;
    $value->color = $this->color;
    $value->image = $this->image;
    $value->repeat = $this->repeat;
    $value->position = $this->position;
    return $value;
  }

  function is_default() {
    return 
      $this->color == CSSBackgroundColor::default_value() &&
      $this->image->is_default() &&
      $this->repeat == CSSBackgroundRepeat::default_value() &&
      $this->position->is_default();
  }

  function show(&$viewport, &$box) {
    // Fill box with background color
    if (!$this->color->transparent) {
      $this->color->apply($viewport);
      $viewport->moveto($box->get_left_padding(), $box->get_top_padding());
      $viewport->lineto($box->get_right_padding(), $box->get_top_padding());
      $viewport->lineto($box->get_right_padding(), $box->get_bottom_padding());
      $viewport->lineto($box->get_left_padding(), $box->get_bottom_padding());
      $viewport->closepath();
      $viewport->fill();
    };

    // Render background image, if any
    $this->image->show($viewport, $box, $this->repeat, $this->position);   
  }

  function to_ps(&$psdata) {  
    $this->image->to_ps_init($psdata);

    return
      "<< ".
      "/color ".$this->color->to_ps()." ".
      "/image  ".$this->image->to_ps()." ".
      "/position ".$this->position->to_ps()." ".
      "/repeat ".CSSBackgroundRepeat::value2ps($this->repeat)." ".
      ">>";
  }
}

?>
