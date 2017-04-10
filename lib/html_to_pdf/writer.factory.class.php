<?php

// Unfortunatately, there's no good way to create a reference to a "statis" class function (at least in PHP4),
// so we're force to use this case structure instead of more elegant type-to-function mapping array
//
class WriterFactory {
  function create($method, $compress, $pdfversion) {
    switch ($method) {
    case "ps": 
      return PSWriter::create($compress, $pdfversion);
    case "ps2pdf":
      return PS2PDFWriter::create($compress, $pdfversion);
    case "pdflib":
      return PDFWriter::create($compress, $pdfversion);
    case "fpdf":
      return FPDFWriter::create($compress, $pdfversion);
    case "fastps":
      return FastPSWriter::create($compress, $pdfversion);
    default:
      die("Unknown output method specified: ".$method);
    }
  }
};
?>