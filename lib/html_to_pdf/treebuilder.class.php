<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/treebuilder.class.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

if (!defined('XML_ELEMENT_NODE')) { define('XML_ELEMENT_NODE',1); };
if (!defined('XML_TEXT_NODE')) { define('XML_TEXT_NODE',2); };
if (!defined('XML_DOCUMENT_NODE')) { define('XML_DOCUMENT_NODE',3); };

// Why I Hate PHP? They change their interfaces too often
// This time we'll need to emulate old DOM XML behaviour using 
// New DOMDocument PHP 5 object (or just rewrite _all_ DOM-related code)
  class DOMTree {
    var $domelement;
    var $content;

    function document_element() { return $this; }
    function DOMTree($domelement) {
      $this->domelement = $domelement;
      $this->content = $domelement->textContent;
    }

    function first_child() {
      if ($this->domelement->firstChild) {
        return new DOMTree($this->domelement->firstChild);
      } else {
        return false;
      };
    }
    function from_DOMDocument($domdocument) { return new DOMTree($domdocument->documentElement); }

    function get_attribute($name) { return $this->domelement->getAttribute($name); }
    function get_content() { return $this->domelement->textContent; }

    function has_attribute($name) { return $this->domelement->hasAttribute($name); }

    function last_child() {
      $child = $this->first_child();

      if ($child) {
        $sibling = $child->next_sibling();
        while ($sibling) {
          $child = $sibling;
          $sibling = $child->next_sibling();
        };
      };

      return $child;
    }

    function next_sibling() {
      if ($this->domelement->nextSibling) {
        return new DOMTree($this->domelement->nextSibling);
      } else {
        return false;
      };
    }
    function node_type() { return $this->domelement->nodeType; }

    function parent() {
      if ($this->domelement->parentNode) {
        return new DOMTree($this->domelement->parentNode);
      } else {
        return false;
      };
    }
    function previous_sibling() {
      if ($this->domelement->previousSibling) {
        return new DOMTree($this->domelement->previousSibling);
      } else {
        return false;
      };
    }

    function tagname() { return $this->domelement->localName; }
  }

// Wrapper for ActiveLink pure PHP DOM extension
  class ActiveLinkDOMTree {
    var $xml;
    var $index;
    var $parent_indices;
    var $parents;
    var $content;

    function from_XML($xml) { return new ActiveLinkDomTree($xml,0, array(), array()); }

    function ActiveLinkDOMTree($xml, $index, $indices, $parents) {
      $this->xml            = $xml;
      $this->index          = $index;
      $this->parent_indices = $indices;
      $this->parents        = $parents;

      if (is_a($this->xml,"XMLLeaf")) {
        $this->content = $xml->value;
      } else {
        $this->content = $xml->getXMLContent();
      };
    }

    function node_type() { return is_a($this->xml,"XMLLeaf") ? XML_TEXT_NODE : XML_ELEMENT_NODE; }
    function tagname()   { return is_a($this->xml,"XMLLeaf") ? "text" : $this->xml->getTagName(); }

    function get_attribute($name) { return $this->xml->getTagAttribute($name); }
    function has_attribute($name) { return $this->xml->getTagAttribute($name) !== false; }

    function get_content() { return $this->xml->getXMLContent(); }

    function document_element() { return $this; }

    function last_child() {
      $child = $this->first_child();

      if ($child) {
        $sibling = $child->next_sibling();
        while ($sibling) {
          $child = $sibling;
          $sibling = $child->next_sibling();
        };
      };

      return $child;
    }

    function parent() {
      if (!(is_a($this->xml,"XMLBranch") || is_a($this->xml,"XMLLeaf"))) { return false; }

      if (count($this->parents) > 0) {
        $parents = $this->parents;
        $parent = array_pop($parents);
        return $parent;
      } else {
        return false;
      };
    }

    function first_child() {
      $children = $this->xml->nodes;
      $indices = $this->parent_indices;
      array_push($indices, $this->index);
      $parents = $this->parents;
      array_push($parents, $this);

      if ($children) {
        $node = new ActiveLinkDOMTree($children[0], 0, $indices, $parents);       
        return $node;
      } else {
        return false;
      };
    }

    function previous_sibling() {
      $parent = $this->parents[count($this->parents)-1];
      $nodes  = $parent->xml->nodes;

      if ($this->index <= 0) { return false; };

      return new ActiveLinkDOMTree($nodes[$this->index-1],$this->index-1, $this->parent_indices, $this->parents);
    }

    function next_sibling() {
      $parent = $this->parents[count($this->parents)-1];
      $nodes  = $parent->xml->nodes;
     
      if ($this->index >= count($nodes)-1) { 
        return false; 
      };

      $node = new ActiveLinkDOMTree($nodes[$this->index+1], $this->index+1, $this->parent_indices, $this->parents);

      return $node;
    }
  }

  class TreeBuilder { 
    function build($xmlstring) {
      // Detect if we're using PHP 4 (DOM XML extension) 
      // or PHP 5 (DOM extension)
      // First uses a set of domxml_* functions, 
      // Second - object-oriented interface
      // Third - pure PHP XML parser
      if (function_exists('domxml_open_mem')) { return domxml_open_mem($xmlstring); };
      if (class_exists('DOMDocument')) { return DOMTree::from_DOMDocument(DOMDocument::loadXML($xmlstring)); };
      if (file_exists('classes/include.php')) { 
        require_once('classes/include.php');
        import('org.active-link.xml.XML');
        import('org.active-link.xml.XMLDocument');
        
        // preprocess character references
        // literal references (actually, parser SHOULD do it itself; nevertheless, Activelink just ignores these entities)
        $xmlstring = preg_replace("/&amp;/","&",$xmlstring);
        $xmlstring = preg_replace("/&quot;/","\"",$xmlstring);
        $xmlstring = preg_replace("/&lt;/","<",$xmlstring);
        $xmlstring = preg_replace("/&gt;/",">",$xmlstring);
  
        // in decimal form
        while (preg_match("@&#(\d+);@",$xmlstring, $matches)) {
          $xmlstring = preg_replace("@&#".$matches[1].";@",code_to_utf8($matches[1]),$xmlstring);
        };
        // in hexadecimal form
        while (preg_match("@&#x(\d+);@i",$xmlstring, $matches)) {
          $xmlstring = preg_replace("@&#x".$matches[1].";@i",code_to_utf8(hexdec($matches[1])),$xmlstring);
        };

        $tree = ActiveLinkDOMTree::from_XML(new XML($xmlstring));

        return $tree; 
      }
      die("None of DOM/XML, DOM or ActiveLink DOM extension found. Check your PHP configuration.");
    }
  };
?>