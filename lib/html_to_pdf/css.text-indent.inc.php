<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/css.text-indent.inc.php,v 1.2 2006/01/30 00:39:20 administrator Exp $

// Stack value is an array containing two values:
// string value of text-indent proprty and 
// boolean flag indication of this value is relative (true) or absolute (false)

class TextIndentValuePDF {
  var $raw_value;

  function calculate(&$box) {
    if ($this->raw_value[1]) {
      // Is a percentage
      return $box->get_width() * $this->raw_value[0] / 100;
    } else {
      return $this->raw_value[0];
    };
  }

  function copy() {
    return new TextIndentValuePDF($this->raw_value);
  }

  function is_default() {
    return $this->raw_value[0] == 0;
  }

  function TextIndentValuePDF($value) {
    $this->raw_value = $value;
  }

  function to_ps() {
    return 
      "<< ".
      "/Percentage ".($this->raw_value[1] ? "true" : "false")." ".
      "/Value ".$this->raw_value[0]." ".
      ">>";
  }

  function units2pt(&$box) {
    $this->raw_value[0] = units2pt($this->raw_value[0], $box->font_size);
  }
}

class CSSTextIndent extends CSSProperty {
  function CSSTextIndent() { $this->CSSProperty(true, true); }

  function default_value() { return new TextIndentValuePDF(array(0,false)); }

  function parse($value) {
    if (is_percentage($value)) { 
      return new TextIndentValuePDF(array((int)$value, true));
    } else {
      return new TextIndentValuePDF(array($value, false));
    };
  }
}

register_css_property('text-indent', new CSSTextIndent());

?>
