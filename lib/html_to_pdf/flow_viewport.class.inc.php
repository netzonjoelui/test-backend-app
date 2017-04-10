<?php
class FlowViewport {
  var $left;
  var $top;
  var $width;
  var $height;

  function FlowViewport(&$box) {
    $this->left = $box->get_left_padding();
    $this->top  = $box->get_top_padding();
    $this->width = $box->get_width() + $box->padding->left->value + $box->padding->right->value;
    $this->height = $box->get_height() + $box->padding->top->value + $box->padding->bottom->value;
  }

  function get_left() { return $this->left; }
  function get_top() { return $this->top; }
  function get_height() { return $this->height; }
  function get_width() { return $this->width; }
}
?>
