<?php
/**
 * Return version invormation about the latest running version of the client
 */
header("Content-type: text/xml");			// Returns XML document
echo '<?xml version="1.0" encoding="UTF-8"
  standalone="yes"?>'; 

echo "<response>";
echo "<version>2.4</version>";
echo "</response>";
