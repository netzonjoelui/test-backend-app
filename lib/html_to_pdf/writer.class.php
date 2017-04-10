<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/writer.class.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

// FIXME: move some common functions to the abstract Writer class.
// avoid overloading 'release' and 'create' functions

// Note that WRITER_TEMPDIR !REQUIRES! slash on the end (unless you want to get
// some files like tempPS_jvckxlvjl in your working directory).
define('WRITER_TEMPDIR','./temp/');
define('WRITER_FILE_PREFIX','PS_');
// number of retries to generate unique filename in case we have had troubles with
// tempnam function
define('WRITER_RETRIES',10);
define('WRITER_CANNOT_CREATE_FILE',"Cannot create unique temporary filename, sorry");

// Path to ps2pdf
// define('GS_PATH','c:\gs\gs8.51\bin\gswin32c.exe');
// define('GS_PATH','c:\gs\gs8.14\bin\gswin32c.exe');
// define('GS_PATH','c:\gs\gs7.05\bin\gswin32c.exe');
define('GS_PATH','/usr/bin/gs');

define('PDFLIB_DL_PATH','../html2ps/pdflib.so');

// This variable defines the path to PDFLIB configuration file; in particular, it contains
// information about the supported encodings.
//
// define('PDFLIB_UPR_PATH',"c:/php/php4.4.0/pdf-related/pdflib.upr");
// define('PDFLIB_UPR_PATH',"c:/php/pdf-related/pdflib.upr");

// Path to directory containing fonts used by PDFLIB
// Trailing backslash required
// Default value: the path where the script is executed + '/fonts/'
$basepath = dirname(isset($_SERVER['PATH_TRANSLATED']) ? $_SERVER['PATH_TRANSLATED'] : "");
if ($basepath == '') { $basepath = '.'; }
define('PDFLIB_TTF_FONTS_REPOSITORY',$basepath."/fonts/");

function safe_exec($cmd, &$output) {
  exec($cmd, $output, $result);

  if ($result) {
    $message = "";

    if (count($output) > 0) {
      $message .= "Error executing '{$cmd}'<br/>\n";
      error_log("Error executing '{$cmd}'.");
      $message .= "Command produced the following output:<br/>\n";
      error_log("Command produced the following output:");

      foreach ($output as $line) {
        $message .= "{$line}<br/>\n";
        error_log($line);
      };
    } else {
      $message .= "Error executing '{$cmd}'. Command produced no output.<br/>\n";
      error_log("Error executing '{$cmd}'. Command produced no output.");
      $message .= "Check if '{$cmd}' is available on your system and executable.<br/>\n";
      error_log("Check if '{$cmd}' is available on your system and executable.");
    };
    die($message);
  };
}

class PSWriter {
  var $file_handle;
  var $file_name;
  var $output;

  function set_output(&$sink) { $this->output = $sink; }
  function get_output()      { return $this->output; }

  function PSWriter($filename) {
    $this->file_name = $filename;
  }

  // Should be called by error handler
  function cleanup_error() {
    $this->close();
    unlink($this->file_name);
  }

  function get_viewport($g_media) {
    return new ViewportPs($this->file_handle, $g_media);
  }

  function write_box($box) {
    $box->show($this);
  }

  function cleanup_temp() {
    // Delete old temporary files. They can stay after script have died due the
    // execution timeout or any other problem appeared before ->release() call.

    // Walk over all files contained in WRITER_TEMPDIR
    $dh = @opendir(WRITER_TEMPDIR);
    while (false !== ($file = @readdir($dh))) {
      // Check the modification time of each file; if more than a 10 minutes passed since the file
      // have been created - try to unlink it.
      // NOTE: ignore .htaccess file
      if ($file{0} != '.') {
        if (time() - @filemtime(WRITER_TEMPDIR.$file) > 10*60) {
          @unlink(WRITER_TEMPDIR.$file);
        };
      };
    };
    @closedir($dh);
  }

  function mk_filename() {
    // Check if we can use tempnam to create files (so, we have PHP version
    // with fixed bug it this function behaviour and open_basedir/environment
    // variables are not maliciously set to move temporary files out of open_basedir
    // In general, we'll try to create these files in ./temp subdir of current
    // directory, but it can be overridden by environment vars both on Windows and
    // Linux
    $filename   = tempnam(WRITER_TEMPDIR,WRITER_FILE_PREFIX);
    $filehandle = @fopen($filename, "wb");
    // Now, if we have had any troubles, $filehandle will be
    if ($filehandle === false) {
      // Note: that we definitely need to unlink($filename); - tempnam just created it for us!
      // but we can't ;) because of open_basedir (or whatelse prevents us from opening it)

      // Fallback to some stupid algorithm of filename generation
      $tries = 0;
      do {
        $filename   = WRITER_TEMPDIR.WRITER_FILE_PREFIX.md5(uniqid(rand(), true));
        // Note: "x"-mode prevents us from re-using existing files
        // But it require PHP 4.3.2 or later
        $filehandle = @fopen($filename, "xb");
        $tries++;
      } while (!$filehandle && $tries < WRITER_RETRIES);
    };

    if (!$filehandle) {
      die(WRITER_CANNOT_CREATE_FILE);
    };
    // Release this filehandle - we'll reopen it using some gzip wrappers
    // (if they are available)
    fclose($filehandle);

    // Remove temporary file we've just created during testing
    unlink($filename);

    return $filename;
  }

  // This is "factory function" uset to detect site configuration and create
  // temporary file and corresponding raw/gzipping wrappers safely.
  function create($compress_mode, $pdfversion) {
    $filename = PSWriter::mk_filename();

    // Now, detect if there's gzip extension available.
    // AND if user allowed compression
    if (function_exists('gzopen') && $compress_mode) {
      // Well, we're assuming that all gz____ function link gzwrite/gzclose/gzread
      // come in a bunch. I suppose it is reliable. Or can we trust anybody? ;)
      return new GzippedPSWriter($filename);
    } else {
      // Oops. No gzip functions. Poor user will receive uncompresed PS.
      return new RawPSWriter($filename);
    };
  }
};

class GzippedPSWriter extends PSWriter {
  function GzippedPSWriter($file_name) {
    // Call base-class constructor;
    $this->PSWriter($file_name);
    $this->file_handle = gzopen($this->file_name,"wb");
  }

  function write($data) { gzwrite($this->file_handle, $data); }
  function close() { gzclose($this->file_handle); }

  // The writing is completed. Dump data to the browser and release file.
  function release() {
    $this->close();
    $this->output->execute(array('ps.gz','application/postscript'), $this->file_name);
    unlink($this->file_name);
  }
};

class RawPSWriter extends PSWriter {
  function RawPSWriter($file_name) {
    // Call base-class constructor;
    $this->PSWriter($file_name);
    $this->file_handle = fopen($this->file_name, "wb");
  }

  function write($data) { fwrite($this->file_handle, $data); }
  function close() { fclose($this->file_handle); }

  // The writing is completed. Dump data to the browser and release file.
  function release() {
    $this->close();
    $this->output->execute(array('ps','application/postscript'), $this->file_name);
    unlink($this->file_name);
  }
};

class PS2PDFWriter extends PSWriter {
  var $compatibility;

  function PS2PDFWriter($file_name) {
    // Check if 'exec' function is available
    if (!function_exists('exec')) {
      readfile('templates/missing_exec.html');
      error_log("'exec' function is not available");
      die();
    }

    // Check if ghostscript is available
    exec(GS_PATH." --version", $output, $result);
    if ($result) {
      readfile('templates/missing_gs.html');
      error_log("Ghostscript executable not found:'".GS_PATH."'");
      die();
    };

    // Call base-class constructor;
    $this->PSWriter($file_name);
    $this->file_handle = fopen($this->file_name, "wb");
  }

  function create($compressmode, $version) {
    $filename = PSWriter::mk_filename();
    $ps2pdf = new PS2PDFWriter($filename);
    $ps2pdf->set_compatibility($version);
    return $ps2pdf;
  }

  function get_viewport($g_media) {
    return new ViewportPs($this->file_handle, $g_media);
  }

  function set_compatibility($version) {
    $this->compatility = $version;
  }

  function write($data) { fwrite($this->file_handle, $data); }
  function close() { fclose($this->file_handle); }

  function _mk_cmd($filename) {
    return GS_PATH." -dNOPAUSE -dBATCH -dEmbedAllFonts=true -dCompatibilityLevel=".$this->compatility." -sDEVICE=pdfwrite -sOutputFile=".$filename.".pdf ".$filename;
  }

  // The wrting is completed. Dump data to the browser and release file.
  function release() {
    $this->close();

    $pdf_file = $this->file_name.'.pdf';
    $cmd = $this->_mk_cmd($this->file_name);
    safe_exec($cmd, $output);

    $this->output->execute(array('pdf','application/pdf'), $this->file_name.'.pdf');
    Unlink($this->file_name.'.pdf');
    unlink($this->file_name);
  }
};

class PDFWriter extends PSWriter {
  var $pdf;

  function PDFWriter($file_name, $version) {
    // Check if PDFLIB is available
    if (!extension_loaded('pdf')) {

      // Try to use "dl" to dynamically load PDFLIB
      $result = dl(PDFLIB_DL_PATH);

      if (!$result) {
        readfile('templates/missing_pdflib.html');
        error_log("No PDFLIB extension found");
        die();
      }
    }

    // Call base-class constructor;
    $this->PSWriter($file_name);

    $this->pdf = pdf_new();

    // Set PDF compatibility level
    pdf_set_parameter($this->pdf, "compatibility", $version);

    pdf_open_file($this->pdf, $file_name);

    // Set path to the PDFLIB UPR file containig information about fonts and encodings
    //    pdf_set_parameter($this->pdf, "resourcefile", PDFLIB_UPR_PATH);

    // Setup font outlines
    global $g_font_resolver_pdf;
    $g_font_resolver_pdf->setup_ttf_mappings($this->pdf);

    $pdf = $this->pdf;
    pdf_set_info($pdf, "Creator", "html2ps (PHP version)");
  }

  function create($compressmode, $version) {
    $filename = PSWriter::mk_filename();
    $method = new PDFWriter($filename, $version);
    return $method;
  }

  function get_viewport($g_media) {
    return new ViewportPdflib($this->pdf, $g_media);
  }

  function write($data) {  }
  function close() {
    pdf_end_page($this->pdf);
    pdf_close($this->pdf);
    pdf_delete($this->pdf);
  }

  // The wrting is completed. Dump data to the browser and release file.
  function release() {
    $this->close();

    $this->output->execute(array('pdf','application/pdf'), $this->file_name);
    unlink($this->file_name);
  }
};
?>
