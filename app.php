<?php
	require_once("lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("email/email_functions.awp");
	require_once("lib/Email.php");
	require_once("lib/aereus.lib.php/CCache.php");
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$THEME_NAME = $USER->themeName;
	$APP = $_GET['app'];

	if (!$APP)
		die("Application not found");
	
	$apps = array();
	$result = $dbh->Query("SELECT id, name, short_title, title FROM applications WHERE scope='system' ORDER BY sort_order");
	// TODO: add user or team scopes as well
	for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
	{
		$row = $dbh->GetRow($result, $i);
		$apps[] = $row;
	}
?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php print($ANT->settingsGet("general/company_name")); ?> | netric</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="shortcut icon" href="/favicon.ico" type="image/ico">	

	<link rel="STYLESHEET" id='ant_css_base' type="text/css" href="/css/ant_base.css">
	<link rel="STYLESHEET" id='ant_css_theme' type="text/css" href="/css/<?php echo $USER->themeCss; ?>">

	<style type='text/css'>
	body, html
	{
		height: 100%;
	}
	body
	{
		padding: 0px;
		margin: 0px;
		/*overflow: hidden;*/
	}
	</style>

	<?php if (AntConfig::getInstance()->debug) { ?>
		<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.js"></script>
		<?php include("lib/js/includes.php"); ?>
	<?php } else { ?>
		<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.cmp.js"></script>
		<script language="javascript" type="text/javascript" src="/lib/js/ant_full.cmp.js"></script>
	<?php } ?>

	<script LANGUAGE="javascript" TYPE="text/javascript">
	var g_cssfile = "<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>";
	var g_cssname = "<?php echo UserGetTheme($dbh, $USERID, 'name'); ?>";
	var g_userid = <?php echo $USERID; ?>;

	var g_tabs = new Array();
<?php
	echo "var g_apps = [";
	$i = 0;
	foreach ($apps as $app)
	{
		if ($i) echo ",";
		echo "{name:\"".$app["name"]."\", short_title:\"".$app["short_title"]."\", title:\"".$app["short_title"]."\"}";
		$i++;
	}
	echo "]\n";

?>
	/**
     * Load Ant script
	 */
	function loadAnt()
	{
		Ant.init(function() { appMain(); });
	}

	/**
     * Main function for loading the application
	 */
	function appMain()
	{
		// Create main views router.
		// This will take care of handling document.hash changes and routing them to the appropriate views
		var navMain = null
		navMain = new AntViewsRouter();
		navMain.defaultView = "<?php print($APP); ?>";
		navMain.options.viewManager = new AntViewManager();
		navMain.options.viewManager.setViewsToggle(true); // Only view one view at a time at the root level
		navMain.onchange = function(path)
		{
			this.options.viewManager.load(path);
		}
		
		var bodyCon = document.getElementById("appbody");

		// Create root level view for the selecte application
		var appView = navMain.options.viewManager.addView("<?php print($APP); ?>", {name:"<?php print($APP); ?>"}, bodyCon); // Default view is called index
		appView.render = function()
		{
			var app = new AntApp(this.options.name);
            app.isNewWin = true;
			app.main(this);
			Ant.apps[this.options.name] = app;
		}

        Ant.userCheckin();
	}

	/**
	 * Resize main is called on window.onresize
	 */
	function resizeMain()
	{
		Ant.resizeActiveApp();
	}

	// Set env vars
	Ant.theme.name= g_cssname;
	Ant.m_css = g_cssfile;
	Ant.f_framed = true;
	</script>	
</head>
<body onload="loadAnt();" onresize="resizeMain()">

	<!-- application header -->
	<div id='appheader' class='header' style='display:none;'>
	</div>
	<!-- end: application header -->

	<!-- application tabs -->
	<div id='appnav'>
	</div>
	<!-- end: application tabs -->

	<!-- application body - where the applications load -->
	<div id='appbody'>
	</div>
	<!-- end: application body -->

</body>
</html>
