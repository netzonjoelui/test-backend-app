<?php
	require_once("../../lib/aereus.lib.php/CAnsClient.php");

	// Test Upload
	$ans = new CAnsCLient("ans.aereus.com", "ant", "kryptos78");
	echo $ans->putFile("./upload.png", "test-upload.png", "image/png", "test/upload", "/");
