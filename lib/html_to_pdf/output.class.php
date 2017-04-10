<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/output.class.php,v 1.2 2006/01/30 00:39:22 administrator Exp $

define('OUTPUT_FILE_ALLOWED',true);
// Note you'll need to create this directory manually
define('OUTPUT_FILE_DIRECTORY','./out');
define('OUTPUT_DEFAULT_NAME','unnamed');

class Output {
  var $filename;
  function Output() {
    $this->filename = "";
  }

  function set_filename($filename) { $this->filename = $filename; }
  function get_filename() { return empty($this->filename) ? OUTPUT_DEFAULT_NAME : $this->filename; }
  function filename_escape($filename) { return preg_replace("/\W/","_",$filename); }
}

class BrowserInlineOutput extends Output {
  function BrowserOutput() {}
  function execute($content_type, $src_filename) {
    header("Content-Type:".$content_type[1]);
    header("Content-Disposition:inline; filename=".$this->filename_escape($this->get_filename()).".".$content_type[0]);
    header("Content-Transfer-Encoding: binary");
    header("Cache-Control: private");

    // readfile does not work well with Windows machines
    echo(file_get_contents($src_filename));
  }
}

class BrowserAttachmentOutput extends Output {
  function BrowserOutput() {}
  function execute($content_type, $src_filename) {
    header("Content-Type:".$content_type[1]);
    header("Content-Disposition:attachment; filename=".$this->filename_escape($this->get_filename()).".".$content_type[0]);
    header("Content-Transfer-Encoding: binary");
    header("Cache-Control: private");

    // readfile does not work well with Windows machines
    echo(file_get_contents($src_filename));
  }
}

class FileOutput extends Output {
  function FileOutput() {
    if (!OUTPUT_FILE_ALLOWED) { die("File output is not allowed. You can enable it by setting OUTPUT_FILE_ALLOWED in 'output.class.php' to 'true'"); };
  }
  function execute($content_type, $src_filename) {
    $dest_filename = OUTPUT_FILE_DIRECTORY."/".$this->filename_escape($this->get_filename()).".".$content_type[0];
    copy($src_filename, $dest_filename);
    print("File saved as: ".$dest_filename);
  }
}
?>