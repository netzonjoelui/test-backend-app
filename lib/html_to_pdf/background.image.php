<?php

class BackgroundImagePDF {
  var $url;

  function BackgroundImagePDF($url) {
    $this->url = $url;

    $this->mask = null;
    $this->image = null;
    $this->init = null;
    $this->sx = null;
    $this->sy = null;
  }

  function is_default() { return $this->url === null; }

  function show(&$viewport, $box, $repeat, $position) {
    // If no image have been specified, just return
    if ($this->url == null) { return; }

    // Draw the image
    // $image = do_image_open($this->url);
    $image = Image::get($this->url);

    // Unable to open background image - do nothing
    if (!$image) { return; }

    $image_height = imagesy($image);
    $image_width  = imagesx($image);

    // Setup clipping region for padding area
    $viewport->save();
    $viewport->moveto($box->get_left_padding(), $box->get_top_padding());
    $viewport->lineto($box->get_right_padding(), $box->get_top_padding());
    $viewport->lineto($box->get_right_padding(), $box->get_bottom_padding());
    $viewport->lineto($box->get_left_padding(), $box->get_bottom_padding());
    $viewport->closepath();
    $viewport->clip();

    // Determine the vertical an horizontal offset for the image
    //
    $padding_width = $box->get_width() + $box->get_padding_left() + $box->get_padding_right();
    $padding_height = $box->get_height() + $box->get_padding_top() + $box->get_padding_bottom();
    $x_offset = $position->x_percentage ? ($padding_width  - $image_width*px2pt(1))  * $position->x / 100 : $position->x;
    $y_offset = $position->y_percentage ? ($padding_height - $image_height*px2pt(1)) * $position->y / 100 : $position->y;

    // NOTE: px2pt(1) make a scaing factor for PDF output
    // NOTE: background-image is positioned relative to PADDING corner and drawn in the PADDING area!
    //
    switch ($repeat) {
    case BR_NO_REPEAT:
      $viewport->image($image, 
                       $box->get_left_padding() + $x_offset, 
                       $box->get_top_padding() - px2pt($image_height) - $y_offset, 
                       px2pt(1));
      break;
    case BR_REPEAT_X:
      $viewport->image_rx($image, 
                          $box->get_left_padding() + $x_offset, 
                          $box->get_top_padding() - px2pt($image_height) - $y_offset, 
                          px2pt($image_width),
                          $box->get_right_padding(),
                          $x_offset, $y_offset,
                          px2pt(1));
      break;
    case BR_REPEAT_Y:
      $viewport->image_ry($image, 
                          $box->get_left_padding() + $x_offset, 
                          $box->get_top_padding() - px2pt($image_height) - $y_offset, 
                          px2pt($image_height), 
                          $box->get_bottom_padding(), 
                          $x_offset, $y_offset,
                          px2pt(1));
      break;
    case BR_REPEAT:
      $viewport->image_rx_ry($image, 
                             $box->get_left_padding() + $x_offset, 
                             $box->get_top_padding() - px2pt($image_height) + $y_offset, 
                             px2pt($image_width),
                             px2pt($image_height),
                             $box->get_right_padding(),
                             $box->get_bottom_padding(),
                             $x_offset, 
                             $y_offset, 
                             px2pt(1));
      break;
    };

    // release background image
    imagedestroy($image);

    // return to the previous clipping area
    $viewport->restore();
  }

  function to_ps() {
    if ($this->init) {
      return " ".$this->mask." ".$this->image." {".$this->init."} ".$this->sy." ".$this->sx." background-image-create";
    } else {
      return " /null background-image-create";
    };
  }

  function to_ps_init(&$psdata) {
    $image_res = Image::get($this->url);

    // Unable to open background image - do nothing
    if (!$image_res) { return; }

    // Write the image 
    global $g_image_encoder;
    $id = $g_image_encoder->auto($psdata, $image_res, $size_x, $size_y, $tcolor, $image, $mask);
    $init = "image-".$id."-init";

    if ($mask === "") {
      $mask = "/null";
    };

    $this->sx = $size_x;
    $this->sy = $size_y;
    $this->image = $image;
    $this->init = $init;
    $this->mask = $mask;
  }
}

?>
