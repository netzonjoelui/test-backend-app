<?php 
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/writer.fastps.class.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

class FastPSWriter extends PSWriter {
  function create($compressmode, $pdfcompat) {
    $filename = PSWriter::mk_filename();
    return new FastPSWriter($filename);
  }
  
  function FastPSWriter($file_name) {
    $this->PSWriter($file_name);
    $this->file = fopen($file_name, "wb");
  }

  function get_viewport($media) {
    return new ViewportFastPS($this->file, $media);
  }

  function write($data) { }

  function close() {
    fclose($file_name);
  }

  function release() {
    $this->close();

    $this->output->execute(array('ps','text/postscript'), $this->file_name);
    unlink($this->file_name);
  }
};

?>