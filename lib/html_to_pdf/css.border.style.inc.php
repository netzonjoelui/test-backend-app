<?php
define("BS_SOLID","/bs-solid");
define("BS_DASHED","/bs-dashed");
define("BS_DOTTED","/bs-dotted");
define("BS_DOUBLE","/bs-double");
define("BS_INSET","/bs-inset");
define("BS_OUTSET","/bs-outset");
define("BS_GROOVE","/bs-groove");
define("BS_RIDGE","/bs-ridge");
define("BS_NONE","/bs-none");

class CSSBorderStyle {
  function value2ps($value) {
    switch ($value) {
    case BS_SOLID:
      return "/solid";
    case BS_DASHED:
      return "/dashed";
    case BS_DOTTED:
      return "/dotted";
    case BS_DOUBLE:
      return "/double";
    case BS_INSET:
      return "/inset";
    case BS_OUTSET:
      return "/outset";
    case BS_GROOVE:
      return "/groove";
    case BS_RIDGE:
      return "/ridge";
    case BS_NONE:
    default:
      return "/none";
    };
  }
}
?>