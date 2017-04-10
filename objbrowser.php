<?php
	require_once("lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CToolMenu.awp");
	require_once("lib/Button.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/WorkFlow.php");
	require_once("email/email_functions.awp");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;	

	$obj_type = $_GET['obj_type'];
	if (!$obj_type)
		exit();
?>
<html>
<head>
<title>Object Browser</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="STYLESHEET" id='ant_css_base' type="text/css" href="/css/ant_base.css">
<link rel="STYLESHEET" id='ant_css_theme' type="text/css" href="/css/<?php echo $USER->themeCss; ?>">
<?php if (AntConfig::getInstance()->debug) { ?>
	<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.js"></script>
	<?php include("lib/js/includes.php"); ?>
<?php } else { ?>
	<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.cmp.js"></script>
	<script language="javascript" type="text/javascript" src="/lib/js/ant_full.cmp.js"></script>
<?php } ?>
<script language="javascript" type="text/javascript">
<?php
	echo "var g_userid  = $USERID;\n";
?>
	function main()
	{
		var con = document.getElementById("bdy");
		var ob = new AntObjectBrowser("<?php print($obj_type); ?>");
		ob.print(con);
	}
</script>
<style type='text/css'>
body
{
	margin: 0px;
	padding: 0px;
}
</style>
</head>
<body onload='main()' id='bdy' class="popup">
<?php
?>
</body>
</html>
