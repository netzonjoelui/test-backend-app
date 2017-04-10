<?php
function merge_width_constraint($wc1, $wc2) {
  if (is_a($wc1, "WCNone")) { return $wc2; }
  if (is_a($wc1, "WCConstant") && !is_a($wc2, "WCNone")) {
    return $wc2;
  };
  if (is_a($wc1, "WCFraction") && is_a($wc2, "WCFraction")) {
    return $wc2;
  };
  return $wc1;
}

class WCNone {
  function apply($w, $pw) { return $w; }
  function apply_inverse($w, $pw) { return $pw; }

  function to_ps() { return "wc-create-none"; }
}

class WCConstant {
  var $width;

  function WCConstant($width) {
    $this->width = $width;
  }

  function apply($w, $pw) {
    return $this->width;
  }

  function apply_inverse($w, $pw) { return $pw; }

  function to_ps() { return $this->width." wc-create-constant"; }
}

class WCFraction {
  var $fraction;

  function WCFraction($fraction) { 
    $this->fraction = $fraction;
  } 

  function apply($w, $pw) {
    return $pw * $this->fraction;
  }

  function apply_inverse($w, $pw) { if ($this->fraction > 0) { return $w / $this->fraction; } else { return 0; }; }

  function to_ps() { return $this->fraction." wc-create-fraction"; }
}
?>