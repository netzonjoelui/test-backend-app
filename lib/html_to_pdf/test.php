<?php

class A {
  var $var;

  function A() {
    $this->var = "value1";
  }

  function test(&$b) {
    $b->var =& $this;
  }
}

class B {
  var $var;
}

$a = new A;
$b = new B;
$a->test($b);
print($a->var."<br>");
print($b->var->var."<br>");
$a->var = "value2";
print($a->var."<br>");
print($b->var->var."<br>");

?>
