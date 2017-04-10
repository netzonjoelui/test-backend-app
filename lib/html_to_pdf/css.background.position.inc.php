<?php
// The background-position value is an array containing two array for X and Y position correspondingly
// each coordinate-position array, in its turn containes two values:
// first, the numeric value of percentage or units
// second, flag indication that this value is a percentage (true) or plain unit value (false)

define('LENGTH_REGEXP',"[+-]?\d*\.?\d*(?:em|ex|px|in|cm|mm|pt|pc)");
define('PERCENTAGE_REGEXP',"\d+%");

class CSSBackgroundPosition extends CSSSubProperty {
  function default_value() {
    return new BackgroundPositionValue(array(array(0,true),
                                             array(0,true)));
  }

  function build_subvalue($value) {
    if (substr($value, strlen($value)-1,1) === "%") {
      return array((int)$value, true);
    } else {
      return array($value, false);
    };
  }

  function build_value($x, $y) {
    return array(CSSBackgroundPosition::build_subvalue($x),
                 CSSBackgroundPosition::build_subvalue($y));
  }

  // See CSS 2.1 'background-position' for description of possible values
  //
  function parse_in($value) {
    if (preg_match("/\b(top\s+left|left\s+top)\b/",$value))           { return array(array(0, true),   array(0, true)); };
    if (preg_match("/\b(top\s+center|center\s+top)\b/",$value))       { return array(array(50, true),  array(0, true)); };
    if (preg_match("/\b(top\s+right|right\s+top)\b/",$value))         { return array(array(100, true), array(0, true)); };

    if (preg_match("/\b(left\s+center|center\s+left)\b/",$value))     { return array(array(0, true),   array(50, true)); };
    if (preg_match("/\b(center\s+center)\b/",$value))                 { return array(array(50, true),  array(50, true)); };
    if (preg_match("/\b(right\s+center|center\s+right)\b/",$value))   { return array(array(100, true), array(50, true)); };

    if (preg_match("/\b(bottom\s+left|left\s+bottom)\b/",$value))     { return array(array(0, true),   array(100, true)); };
    if (preg_match("/\b(bottom\s+center|center\s+bottom)\b/",$value)) { return array(array(50, true),  array(100, true)); };
    if (preg_match("/\b(bottom\s+right|right\s+bottom)\b/",$value))   { return array(array(100, true), array(100, true)); };

    // These values should be processed separately at lastt
    if (preg_match("/\b(top)\b/",$value))    { return array(array(50, true),  array(0, true)); };
    if (preg_match("/\b(center)\b/",$value)) { return array(array(50, true),  array(50, true)); };
    if (preg_match("/\b(bottom)\b/",$value)) { return array(array(50, true),  array(100, true)); };
    if (preg_match("/\b(left)\b/",$value))   { return array(array(0, true),   array(50, true)); };
    if (preg_match("/\b(right)\b/",$value))  { return array(array(100, true), array(50, true)); };
   
    if (preg_match("/(".LENGTH_REGEXP."|".PERCENTAGE_REGEXP."|\b0\b)\s+(".LENGTH_REGEXP."|".PERCENTAGE_REGEXP."|\b0\b)/", $value, $matches)) {
      $x = $matches[1];
      $y = $matches[2];

      //      print_r(CSSBackgroundPosition::build_value($x,$y));

      return CSSBackgroundPosition::build_value($x,$y);
    };

    if (preg_match("/".LENGTH_REGEXP."|".PERCENTAGE_REGEXP."/", $value, $matches)) {
      $x = $matches[0];
      return CSSBackgroundPosition::build_value($x,"50%");
    };
    
    return null;
  }

  function parse($value) {
    return new BackgroundPositionValue(CSSBackgroundPosition::parse_in($value));
  }
}
?>