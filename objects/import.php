<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntFs.awp");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/CAntObjectFields.php");
	require_once("lib/CAntFs.awp");
	require_once("lib/WorkFlow.php");
	require_once("lib/aereus.lib.php/CCache.php");
	require_once("lib/CAntObject.php");
	require_once("email/email_functions.awp");
	require_once("lib/aereus.lib.php/CCache.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THEME = $USER->themeName;
	$type = $_GET['obj_type'];
?>
<html>
<head>
<title>Test</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php
	// Aereus lib
	include("lib/aereus.lib.js/js_lib.php");
	// ANT lib
	include("lib/js/includes.php");
?>
<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>">
<script language="javascript" type="text/javascript">
	function main()
	{
		var con = document.getElementById("bdy");
		var ob = new CAntObjectImpWizard("<?php print($type); ?>", "<?php print($USERID); ?>");
		ob.showDialog();
	}
</script>
</head>
<body onload='main()' id='bdy'>
</body>
</html>
