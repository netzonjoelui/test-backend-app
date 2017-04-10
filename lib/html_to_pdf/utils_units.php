<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/utils_units.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

define('EM_KOEFF',1);
define('EX_KOEFF',0.55);

$g_pt_scale = 1;
$g_px_scale = 1;

function pt2pt($pt) { 
  global $g_pt_scale;
  return $pt * $g_pt_scale;
}

function px2pt($px) {
  global $g_px_scale;
  return $px * $g_px_scale;
}

function mm2pt($mm) {
  return $mm*2.834;
}

function units2pt($value, $font_size = null) {
  $units = substr($value, strlen($value)-2,2);
  switch ($units) {
  case "pt":
    return pt2pt((float)$value);
  case "px":
    return px2pt((float)$value);
  case "mm":
    return mm2pt((float)$value);
  case "cm":
    return mm2pt((float)$value*10);
    // FIXME: check if it will work correcty in all situations (order of css rule application may vary).
  case "em":
    if ($font_size === null) {
      $fs = get_font_size();
      
      $fs_parts = explode(" ", $fs);
      if (count($fs_parts) == 2) {
        return units2pt(((float)$value) * $fs_parts[0]*EM_KOEFF . $fs_parts[1]);
      } else {
        return pt2pt(((float)$value) * $fs_parts[0]*EM_KOEFF);
      };
    } else {
      return $font_size * (float)$value * EM_KOEFF;
    };
  case "ex":
    if ($font_size === null) {
      $fs = get_font_size();
      $fs_parts = explode(" ", $fs);
      if (count($fs_parts) == 2) {
        return units2pt(((float)$value) * $fs_parts[0]*EX_KOEFF . $fs_parts[1]);
      } else {
        return pt2pt(((float)$value) * $fs_parts[0]*EX_KOEFF);
      };
    } else {
      return $font_size * (float)$value * EX_KOEFF;
    };
  default:
    return px2pt((float)$value);
  };
}

function ps_units($value) {
  $units = substr($value, strlen($value)-2,2);
  switch ($units) {
  case "pt":
    return (float)$value . " pt ";
  case "px":
    return (float)$value . " px ";
  case "mm":
    return (float)$value . " mm ";
  case "cm":
    return (float)$value . " cm ";
    // FIXME: check if it will work correcty in all situations (order of css rule application may vary).
  case "em":
    $fs = get_font_size();
    $fs_parts = explode(" ", $fs);
    if (count($fs_parts) == 2) {
      return ((float)$value) * $fs_parts[0]*EM_KOEFF . " " . $fs_parts[1];
    } else {
      return ((float)$value) * $fs_parts[0]*EM_KOEFF . " pt ";
    };
  case "ex":
    $fs = get_font_size();
    $fs_parts = explode(" ", $fs);
    if (count($fs_parts) == 2) {
      return ((float)$value) * $fs_parts[0] . " " . $fs_parts[1];
    } else {
      return ((float)$value) * $fs_parts[0] . " pt ";
    };
  default:
    return (float)$value . " px ";
  };
}

?>