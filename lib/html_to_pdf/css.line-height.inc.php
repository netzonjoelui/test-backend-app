<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.line-height.inc.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class LineHeight_Absolute {
  var $length;
  function LineHeight_Absolute($value) { $this->length = $value; }

  function to_ps() { return "<< /Percentage false /Value ".$this->length." >>"; }

  function apply($value) { return units2pt($this->length); }

  function is_default() { return false; }
}

class LineHeight_Relative {
  var $fraction;
  function LineHeight_Relative($value) { $this->fraction = $value; }

  function to_ps() { return "<< /Percentage true /Value ".($this->fraction*100)." >>"; }

  function apply($value) { return $this->fraction * $value; }

  function is_default() { return $this->fraction == 1.1; }
}

function is_default_line_height($value) { 
  return $value == default_line_height(); 
};
function default_line_height() { return new LineHeight_Relative(1.1); };
function get_line_height() { global $g_line_height; return $g_line_height[0]; }
function push_line_height($align) { global $g_line_height; array_unshift($g_line_height, $align); }
function pop_line_height() { global $g_line_height; array_shift($g_line_height); }
function ps_line_height($value) { return $value->to_ps(); };
function css_line_height($value, $root) { 
  pop_line_height(); 
  if (preg_match("/^\d+(\.\d+)?$/",$value)) { push_line_height(new LineHeight_Relative((float)$value)); return; };
  if (preg_match("/^\d+%$/",$value)) { push_line_height(new LineHeight_Relative(((float)$value)/100)); return; };
  push_line_height(new LineHeight_Absolute(ps_units($value)));
};

$g_line_height = array(default_line_height());
?>
