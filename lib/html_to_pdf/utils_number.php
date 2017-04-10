<?php
// $Header: /var/cvs/ant.aereus.com/lib/html_to_pdf/utils_number.php,v 1.2 2006/01/30 00:39:23 administrator Exp $

function arabic_to_roman($num) {
  $arabic = array(1,4,5,9,10,40,50,90,100,400,500,900,1000); 
  $roman = array("I","IV","V","IX","X","XL","L","XC","C","CD","D","CM","M");
  $i = 12;
  $result = "";
  while ($num) { 
    while ($num >= $arabic[$i]) { 
      $num -= $arabic[$i]; 
      $result .= $roman[$i];
    } 
    $i--; 
  } 

  return $result;
}
?>