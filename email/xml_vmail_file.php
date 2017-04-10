<?php
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo "\n";
	echo '<gallery title="Standalone example" description="Slideshow example demonstrating event listeners.">';
	echo '<album id="ssp2" title="Album Two" description="Video example">';
	echo '<img src="/files/';
	echo $_GET['fid'];
	echo '/video.flv" id="id1" title="" ';
	echo 'caption="" link="" target="_blank" pause="" vidpreview="" />';
	echo '</album>';
	echo '</gallery>';
?>
