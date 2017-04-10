<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.background.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class CSSBackground extends CSSProperty {
  var $default_value;

  function CSSBackground() {
    $this->default_value = new CSSBackgroundValue;
    $this->default_value->color    = CSSBackgroundColor::default_value();
    $this->default_value->image    = CSSBackgroundImage::default_value();
    $this->default_value->repeat   = CSSBackgroundRepeat::default_value();
    $this->default_value->position = CSSBackgroundPosition::default_value();

    $this->CSSProperty(false, false);
  }

  function inherit() { 
    // Determine parent 'display' value
    $handler =& get_css_handler('display');
    // note that as css handlers are evaluated in alphabetic order, parent display value still will be on the top of the stack
    $parent_display = $handler->get();

    // If parent is a table row, inherit the background settings
    $this->push(($parent_display == 'table-row') ? $this->get() : $this->default_value());
  }

  function default_value() {
    return $this->default_value->copy();
  }

  function parse($value) {
    $background = new CSSBackgroundValue;
    $background->color    = CSSBackgroundColor::parse($value);
    $background->image    = CSSBackgroundImage::parse($value);
    $background->repeat   = CSSBackgroundRepeat::parse($value);
    $background->position = CSSBackgroundPosition::parse($value);
    return $background;
  }
}

$bg = new CSSBackground;

register_css_property('background', $bg);
register_css_property('background-color'      ,new CSSBackgroundColor($bg, 'color'));
register_css_property('background-image'      ,new CSSBackgroundImage($bg, 'image'));
register_css_property('background-repeat'     ,new CSSBackgroundRepeat($bg, 'repeat'));
register_css_property('background-position'   ,new CSSBackgroundPosition($bg, 'position'));

?>