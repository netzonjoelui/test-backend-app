<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/tag.ulol.inc.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

$g_list_item_nums = array();

function do_ulol_special(&$root) {
  global $g_list_item_nums;

  // Use 'start' attribute value
  $start = 1;
  if ($root->has_attribute('start')) {
    $start = $root->get_attribute('start');
  }

  array_unshift($g_list_item_nums,$start);

  return;
}

function do_ulol_special_post(&$root) {
  global $g_list_item_nums;
  array_shift($g_list_item_nums);

  return;
}

function get_list_item_num() {
  global $g_list_item_nums;
  return $g_list_item_nums[0];
}

function list_item_end() {
  global $g_list_item_nums;
  $g_list_item_nums[0]++;
}
?>
