<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/box.table.section.php,v 1.2 2006/01/30 00:39:19 administrator Exp $

class TableSectionBoxPDF extends GenericContainerBoxPDF {
  function &create($pdf, &$root) {
    $box =& new TableSectionBoxPDF($pdf, $root);
    return $box;
  }
  
  function TableSectionBoxPDF($pdf, &$root) {
    $this->GenericContainerBoxPDF();

    // Automatically create at least one table row
    if (count($this->content) == 0) {
      $this->content[] =& new TableRowBoxPDF($pdf, $root);
    }

    // Parse table contents
    $child = $root->first_child();
    while ($child) {
      $child_box =& create_pdf_box($pdf, $child);
      $this->add_child($child_box);
      $child = $child->next_sibling();
    };
  }

  // Overrides default 'add_child' in GenericBoxPDF
  function add_child(&$item) {
    // Check if we're trying to add table cell to current table directly, without any table-rows
    if (!is_a($item,"TableRowBoxPDF")) {
      // Add cell to the last row
      $last_row =& $this->content[count($this->content)-1];
      $last_row->add_child($item);
    } else {
      // If previous row is empty, remove it (get rid of automatically generated table row in constructor)
      if (count($this->content[count($this->content)-1]->content) == 0) {
        array_pop($this->content);
      }
      
      // Just add passed row 
      $this->content[] =& $item;
    };
  }
}
?>
