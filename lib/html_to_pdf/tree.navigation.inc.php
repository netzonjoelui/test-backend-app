<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/tree.navigation.inc.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

function traverse_head(&$psdata, $root) {
  $child = $root->first_child();
  while ($child) {
    if ($child->node_type() == XML_ELEMENT_NODE) {
      switch (strtolower($child->tagname())) {
      case "title":
        $psdata->output->set_filename($child->get_content());
      };
    };

    $child = $child->next_sibling();
  };
}

function dump_tree(&$box, $level) {
  print(str_repeat(" ", $level));
  print(get_class($box).":".$box->uid."\n");

  if (isset($box->content)) {
    for ($i=0; $i<count($box->content); $i++) {
      dump_tree($box->content[$i], $level+1);
    };
  };
};

function scan_styles($root) {
  switch ($root->node_type()) {
  case XML_ELEMENT_NODE:
    if ($root->tagname() === 'style') {
      // Parse <style ...> ... </style> nodes
      //
      parse_style_node($root);

    } elseif ($root->tagname() === 'link') {
      // Parse <link rel="stylesheet" ...> nodes
      //
      $rel   = strtolower($root->get_attribute("rel"));
      
      $type  = strtolower($root->get_attribute("type"));
      if ($root->has_attribute("media")) {
        $media = explode(",",$root->get_attribute("media"));
      } else {
        $media = array();
      };
      
      if ($rel == "stylesheet" && 
          ($type == "text/css" || $type == "") &&
          (count($media) == 0 || array_search("all",$media) !== false || array_search("screen",$media) !== false))  {
        $src = $root->get_attribute("href");
        if ($src) {
          css_import($src);
        };
      };
    };

    // Note that we continue processing here!
  case XML_DOCUMENT_NODE:

    // Scan all child nodes
    $child = $root->first_child();
    while ($child) {
      scan_styles($child);
      $child = $child->next_sibling();
    };
    break;
  };
};

?>
