<?php
class ViewportPs extends ViewportGeneric {
  function encoding($encoding) {
    $encoding = trim(strtolower($encoding));

    $translations = array(
                          'iso-8859-1'   => "ISOLatin1Encoding",
                          'iso-8859-2'   => "ISO-8859-2-Encoding",
                          'iso-8859-3'   => "ISO-8859-3-Encoding",
                          'iso-8859-4'   => "ISO-8859-4-Encoding",
                          'iso-8859-5'   => "ISO-8859-5-Encoding",
                          'iso-8859-7'   => "ISO-8859-7-Encoding",
                          'iso-8859-9'   => "ISO-8859-9-Encoding",
                          'iso-8859-10'  => "ISO-8859-10-Encoding",
                          'iso-8859-11'  => "ISO-8859-11-Encoding",
                          'iso-8859-13'  => "ISO-8859-13-Encoding",
                          'iso-8859-14'  => "ISO-8859-14-Encoding",
                          'iso-8859-15'  => "ISO-8859-15-Encoding",
                          'dingbats'     => "Dingbats-Encoding",
                          'symbol'       => "Symbol-Encoding",
                          'koi8-r'       => "KOI8-R-Encoding",
                          'cp1250'       => "Windows-1250-Encoding",
                          'cp1251'       => "Windows-1251-Encoding",
                          'windows-1250' => "Windows-1250-Encoding",
                          'windows-1251' => "Windows-1251-Encoding",
                          'windows-1252' => "Windows-1252-Encoding"
                           );

    if (isset($translations[$encoding])) { return $translations[$encoding]; };
    return $encoding;
  }

  function font_ascender($name, $encoding) { return 0; }
  function font_descender($name, $encoding) { return 0; }
  function stringwidth($string, $font, $size) { return 0; }

  function ViewportPs($stream, $media) {
    $this->ViewportGeneric($media);
  }
}
?>