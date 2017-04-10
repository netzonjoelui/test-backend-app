<?php 
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/xhtml.lists.inc.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function process_li(&$sample_html, $offset) {
  return autoclose_tag($sample_html, $offset, "(ul|ol|li|/li|/ul|/ol)", 
                       array("ul" => "process_ul",
                             "ol" => "process_ol"),
                       "/li");
};

function process_ol(&$sample_html, $offset) {
  return autoclose_tag($sample_html, $offset, "(li|/ol)",
                       array("li" => "process_li"),
                       "/ol");
};

function process_ul(&$sample_html, $offset) {
  return autoclose_tag($sample_html, $offset, "(li|/ul)",
                       array("li" => "process_li"),
                       "/ul");
};

function process_lists(&$sample_html, $offset) {
  return autoclose_tag($sample_html, $offset, "(ul|ol)",
                       array("ul" => "process_ul",
                             "ol" => "process_ol"),
                       "");
};

?>
