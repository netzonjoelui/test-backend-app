<?php 
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/writer.fpdf.class.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

define('FPDF_PATH','./fpdf/');

include('fpdf/fpdf.php');
include('fpdf/font/makefont/makefont.php');

class FPDFWriter extends PSWriter {
  var $pdf;

  function FPDFWriter($file_name, $version) {
    if (!class_exists('FPDF')) {
      readfile('templates/missing_fpdf.html');
      error_log("No FPDF class found");
      die();
    };

    // Call base-class constructor;
    $this->PSWriter($file_name);
    $this->file_name = $file_name;    
  }

  function create($compressmode, $version) {
    $filename = PSWriter::mk_filename();
    $method = new FPDFWriter($filename, $version);
    return $method;
  }

  function get_viewport($g_media) {
    $this->pdf = new FPDF("P","pt",array(mm2pt($g_media->width()), mm2pt($g_media->height())));
    return new ViewportFPDF($this->pdf, $g_media);
  }

  function write($data) {  }

  function close() { 
    $this->pdf->Output($this->file_name);
  }

  // The wrting is completed. Dump data to the browser and release file.
  function release() {
    $this->close();

    $this->output->execute(array('pdf','application/pdf'), $this->file_name);
    unlink($this->file_name);
  }
};

?>