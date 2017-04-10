<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>file_upload</title>
<style type='text/css'>
html, body 
{
	height: 100%;
	margin: 0;
	padding: 0;
}	
body 
{
	overflow: hidden;
	background-color: none;
}
</style>
<script language="javascript"> AC_FL_RunContent = 0; </script>
<script language="javascript"> DetectFlashVer = 0; </script>
<script src="/lib/js/AC_RunActiveContent.js" language="javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
// -----------------------------------------------------------------------------
// Globals
// Major version of Flash required
var requiredMajorVersion = 8;
// Minor version of Flash required
var requiredMinorVersion = 0;
// Revision of Flash required
var requiredRevision = 0;
// -----------------------------------------------------------------------------
// -->

function ToggleAdd()
{
}

function FlUploadComplete()
{
}

function setFunctionCb(name, func)
{
	switch(name)
	{
	case 'cancel':
		ToggleAdd = func;
		break;
	}
}
</script>
</head>
<body>
<!--url's used in the movie-->
<!--text used in the movie-->
<script language="JavaScript" type="text/javascript">
<!--
if (AC_FL_RunContent == 0 || DetectFlashVer == 0) {
	alert("This page requires AC_RunActiveContent.js.");
} else {
	var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
	if(hasRightVersion) {  // if we've detected an acceptable version
		// embed the flash movie
		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0',
			'width', '450',
			'height', '57',
			'src', 'file_upload',
			'quality', 'high',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'align', 'middle',
			'play', 'true',
			'loop', 'true',
			'scale', 'showall',
			'wmode', 'transparent',
			'devicefont', 'false',
			'id', 'file_upload',
			'bgcolor', '#ffffff',
			'name', 'file_upload',
			'menu', 'true',
			'allowScriptAccess','sameDomain',
			'allowFullScreen','false',
			'movie', 'file_upload',
			'salign', ''
			); //end AC code
	} else {  // flash is too old or we can't detect the plugin
		var alternateContent = 'Alternate HTML content should be placed here.'
			+ 'This content requires the Adobe Flash Player.'
			+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash</a>';
		document.write(alternateContent);  // insert non-flash content
	}
}
// -->
</script>
<noscript>
	// Provide alternate content for browsers that do not support scripting
	// or for those that have scripting disabled.
  	Alternate HTML content should be placed here. This content requires the Adobe Flash Player.
  	<a href="http://www.macromedia.com/go/getflash/">Get Flash</a>
</noscript>
</body>
</html>
