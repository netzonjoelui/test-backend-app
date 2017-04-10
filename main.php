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
	
	if ((isset($_GET['change_theme']) && is_numeric($_GET['change_theme'])) && is_numeric($USERID))
	{
		$dbh->Query("update users set theme='".$_GET['change_theme']."' where id='$USERID'");
		// If using default image, reset text color
		if (!UserGetPref($dbh, $USERID, "hompage_messagecntr_image"))
			UserDeletePref($dbh, $USERID, 'hompage_messagecntr_txtclr');

		$cache = CCache::getInstance();
		$cache->remove($dbh->dbname."/users/$USERID/theme");
			
		header("Location: /main");
		exit;
	}

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

	<link rel="stylesheet" id='ant_css_base' href="/css/ant_base.css"> 
	<link rel="stylesheet" id='ant_css_theme' href="/css/<?php echo $USER->themeCss; ?>"> 

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

	<?php include("inc_jslibs.php"); ?>
	
	<script LANGUAGE="javascript" TYPE="text/javascript">
	var g_cssfile = "<?php echo UserGetTheme($dbh, $USERID, 'css'); ?>";
	var g_cssname = "<?php echo UserGetTheme($dbh, $USERID, 'name'); ?>";
	var g_userid = <?php echo $USERID; ?>;

	var g_lastTabSelected = null;

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
		Ant.init(function() { antMain(); });
		Ant.getUpdateStream();
	}

	/**
     * Main function for loading ANT after the page loads
	 */
	function antMain()
	{
		createTabs();

		// Create main views router.
		// This will take care of handling document.hash changes and routing them to the appropriate views
		var navMain = null
		navMain = new AntViewsRouter();
		navMain.defaultView = "home";
		navMain.options.viewManager = new AntViewManager();
		navMain.options.viewManager.setViewsToggle(true); // Only view one view at a time at the root level
		navMain.onchange = function(path)
		{
			this.options.viewManager.load(path);
			setTabs(path);
		}
		
		var bodyCon = document.getElementById("appbody");

		// Create root level views for each application
		for (var i = 0; i < g_apps.length; i++)
		{
			var appProps = g_apps[i];

			var appView = navMain.options.viewManager.addView(appProps.name, {name:appProps.name}, bodyCon); // Default view is called index
			appView.render = function()
			{
				var app = new AntApp(this.options.name);
				app.main(this);
				Ant.apps[this.options.name] = app;
			}
		}

		// Manually add settings application
		var appView = navMain.options.viewManager.addView("settings", {name:"settings"}, bodyCon);
		appView.render = function()
		{
			var app = new AntApp(this.options.name);
			app.main(this);
			Ant.apps[this.options.name] = app;
		}

		// Manually add help application
		var appView = navMain.options.viewManager.addView("help", {name:"help"}, bodyCon);
		appView.render = function()
		{
			var app = new AntApp(this.options.name);
			app.main(this);
			Ant.apps[this.options.name] = app;
		}

		//XmlCheckCalReminders();
        Ant.userCheckin();
		//loadSearch();
		
		// Load the searcher application
		var search = new Ant.Searcher();
		search.render(document.getElementById('divAntSearch'));

		// Load the notification manager
		var notMan = new NotificationMan();
		notMan.anchorToEl(document.getElementById('divAntNotifications'));

		// Load the new object tool
		var notMan = new Ant.NewObjectTool();
		notMan.anchorToEl(document.getElementById('divNewObject'));
		
		/**
		 * Help menu
		 */
		var menu = new alib.ui.PopupMenu();

		// Tutorials
		var item = new alib.ui.MenuItem("Watch Tutorials", {icon:"<img src='/images/icons/movie_track_10.png' />"});
		item.onclick = function() { loadSupportDoc(121); };
		menu.addItem(item);

		// Documentation
		var item = new alib.ui.MenuItem("View Documentation", {icon:"<img src='/images/icons/info_10.png' />"});
		item.onclick = function() { window.open("http://www.netric.com/support"); };
		menu.addItem(item);

		// Contact Support
		var item = new alib.ui.MenuItem("Contact Support", {icon:"<img src='/images/icons/help_10.png' />"});
		item.onclick = function() { document.location.hash = 'help/contact'; };
		menu.addItem(item);

		// Past Cases
		var item = new alib.ui.MenuItem("View Support Cases", {icon:"<img src='/images/icons/cases_10.png' />"});
		item.onclick = function() { document.location.hash = 'help/cases'; };
		menu.addItem(item);

		menu.attach(document.getElementById("mainHelpLink"));

		// Ant Chat
		var chatLnk = alib.dom.createElement("a", document.getElementById('divAntChat'), "<img src='/images/icons/chat_24_off.png'>");
		chatLnk.href = "javascript:void(0);";
		var messenger = new AntChatMessenger();						
		messenger.print(chatLnk);
		
		/**
		 * Profile picture / logoff
		 */
		var menu = new alib.ui.PopupMenu();

		// Profile settings
		var item = new alib.ui.MenuItem("Profile Settings", {icon:"<img src='/images/icons/settings_10.png' />"});
		item.onclick = function() { document.location.hash = 'settings'; };
		menu.addItem(item);

		// Logout
		var item = new alib.ui.MenuItem("Log Out <?php echo $USER->fullName; ?>", {icon:"<img src='/images/icons/history_10.png' />"});
		item.onclick = function() { document.location="/logout.php"; };
		menu.addItem(item);

		menu.attach(document.getElementById("mainProfileLink"));

		// Replace tooltips with alib.ui.Tooltip
		alib.dom.query('[title!=""]', document.body).each(function() {
			var tt = new alib.ui.Tooltip(this);
		});


		// Load welcome tour
		Ant.HelpTour.loadTours(document.getElementById("tour-welcome"));
	}

	/**
	 * Update the application tabs
	 *
	 * Each time the url after the # symbol changes in the url
	 * this function will be called with the full path. This will be cached
	 * for reloading tabs.
	 *
	 * @param {string} path Stil of the full path after the hash (#)
	 */
	function setTabs(path)
	{
		// Loop through all tabs for a match in root path, if set, then load last path
		var pathParts = path.split('/');
		var activeApp = pathParts[0];

		for (var i = 0; i < g_tabs.length; i++)
		{
			if (g_tabs[i].rootPath == activeApp)
			{
				g_tabs[i].path = path;

				alib.dom.styleSetClass(g_tabs[i], "topNavTabOn");
				g_tabs[i].onmouseover = function() { }
				g_tabs[i].onmouseout = function() { }
			}
			else
			{
				alib.dom.styleSetClass(g_tabs[i], "topNavTabOut");
				g_tabs[i].onmouseover = function() { alib.dom.styleSetClass(this, "topNavTabOver"); }
				g_tabs[i].onmouseout = function() { alib.dom.styleSetClass(this, "topNavTabOut"); }
			}
		}

		Ant.updateAppTitle();
	}

	/**
	 * Initialize application tabs
	 */
	function createTabs()
	{
		var tabcon = document.getElementById("apptabs");

		// Create root level views for each application
		for (var i = 0; i < g_apps.length; i++)
		{
			var appProps = g_apps[i];

			var a = alib.dom.createElement("div", tabcon);
			alib.dom.styleSet(a, "display", "inline-block");
			alib.dom.styleSetClass(a, "topNavTabOut");
			a.onmouseover = function() { alib.dom.styleSetClass(this, "topNavTabOver"); }
			a.onmouseout = function() { alib.dom.styleSetClass(this, "topNavTabOut"); }
			a.path = appProps.name;
			a.rootPath = appProps.name;
			a.onclick = function()
			{
				document.location.hash = this.path;
			}

			var lbl = alib.dom.createElement("div", a);
			lbl.innerHTML = appProps.short_title;

			g_tabs[g_tabs.length] = a;
		}

	}

	/*
	 * Open an application in a new window
	 */
	function openAppNewWin(name)
	{
		var url = '/apploader/'+name;

		window.open(url, 'app'+name);
	}

	/**
	 * Resize main is called on window.onresize
	 */
	function resizeMain()
	{
		Ant.resizeActiveApp();
	}

	// Calendar section
	// -------------------------------------------------------------------------------
	var g_xmlCalReminders = null;

	/**
	 * @depricated
	 *
	function XmlCheckCalReminders()
	{
		//var xmlLocal = null;
		
		var url = "/calendar/xml_get_popup_reminders.awp";
		
		// branch for native XMLHttpRequest object
		if (window.XMLHttpRequest) 
			g_xmlCalReminders = new XMLHttpRequest();
		else if (window.ActiveXObject) 
			g_xmlCalReminders = new ActiveXObject("Microsoft.XMLHTTP");
		
		if (g_xmlCalReminders) 
		{
			function ProcessUpdateCallback()
			{
				
			}
			g_xmlCalReminders.onreadystatechange = function ()
			{
				if (g_xmlCalReminders.readyState == 4) 
				{
					// only if "OK"
					if (g_xmlCalReminders.status == 200) 
					{
						response  = g_xmlCalReminders.responseXML.documentElement;
						var reminders = response.getElementsByTagName("reminder");
						for(var i = 0; i < reminders.length; ++i)
						{
							if (response.getElementsByTagName('event_id')[i].firstChild)
								var evntid = response.getElementsByTagName('event_id')[i].firstChild.nodeValue;
							if (response.getElementsByTagName('event_name')[i].firstChild)
								var event_name = response.getElementsByTagName('event_name')[i].firstChild.nodeValue;
							if (response.getElementsByTagName('location')[i].firstChild)
								var location = response.getElementsByTagName('location')[i].firstChild.nodeValue;
							if (response.getElementsByTagName('dates')[i].firstChild)
								var dates = response.getElementsByTagName('dates')[i].firstChild.nodeValue;
							if (response.getElementsByTagName('times')[i].firstChild)
								var times = response.getElementsByTagName('times')[i].firstChild.nodeValue;
							
							var remider = window.open('http://<?php print(AntConfig::getInstance()->localhost); ?>/calendar/pop_reminder.awp?evntid='+unescape(evntid), 'reminder'+evntid, 
														'top=200,left=100,width=450,height=350,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no,status=no,resizable=yes');
							// Check if popup was blocked
							if (!remider)
							{
								var alrtmsg = "Event Reminder: " + unescape(event_name) + "\n";
								alrtmsg += "Location: " + unescape(location) + "\n";
								alrtmsg += "Dates: " + unescape(dates) + "\n";
								alrtmsg += "Times: " + unescape(times) + "\n";
								alrtmsg += "Hint: A popup blocker prevented me from opening a new window. If you allow popups for \n";
								alrtmsg += "this site I can give you more information next time!\n";
								alert(alrtmsg)
							}
						}
					} 

					//delete xmlLocal;
					//xmlLocal = null;
					setTimeout('XmlCheckCalReminders()', 30000);
				}
			};
			g_xmlCalReminders.open("GET", url, true);
			g_xmlCalReminders.send(null);
		}
	}
	 */

	function loadSearch()
	{
		var con = document.getElementById("mainSearchCon");

		var inp = alib.dom.createElement("input");
		alib.dom.styleSet(inp, "width", "100px");
		Ant.Dom.setInputBlurText(inp, "search", "CToolbarInputBlur", "", "");
		inp.onkeyup = function(e)
		{
			if (typeof e == 'undefined') 
			{
				if (ALib.m_evwnd)
					e = ALib.m_evwnd.event;
				else
					e = window.event;
			}

			if (typeof e.keyCode != "undefined")
				var code = e.keyCode;
			else
				var code = e.which;

			if (code == 13 && this.value) // keycode for a return
			{
				Ant.Execute('/objects/app_search.js', 'CSearch', 'Global Search', [["search", this.value]]);
				this.value = "";
			}
		}
		con.appendChild(inp);
	}

	// Set env vars
	Ant.theme.name= g_cssname;
	Ant.m_css = g_cssfile;
	Ant.f_framed = true;
	</script>	
</head>
<body onload="loadAnt();" onresize="resizeMain()">

	<!-- application header -->
	<div id='appheader' class='header'>
		<!-- right actions -->
		<div id='headerActions'>
			<table border='0' cellpadding="0" cellspacing="0">
			<tr valign="middle">			
				<!-- notifications -->
				<td style='padding-right:10px'><div id='divAntNotifications'></div></td>

				<!-- chat -->
				<td style='padding-right:10px'><div id='divAntChat'></div></td>

				<!-- new object dropdown -->
				<td style='padding-right:10px'><div id='divNewObject'></div></td>

				<!-- settings -->
				<td style='padding-right:10px'>
					<a href="javascript:void(0);" class="headerLink" 
						onclick="document.location.hash = 'settings';" 
						title='Click to view system settings'>
							<img src='/images/icons/main_settings_24.png' />
					</a>
				</td>

				<!-- help -->
				<td style='padding-right:10px' id='mainHelpLink'>
					<a href='javascript:void(0);' title='Click to get help'><img src='/images/icons/help_24_gs.png' /></a>
				 </td>
				<td id='mainProfileLink'>
					<a href='javascript:void(0);' title='Logged in as <?php echo $USER->fullName; ?>'><img src="/files/userimages/current/0/24" style='height:24px;' /></a>
				</td>
			</tr>
			</table>
		</div>

		<!-- logo -->
		<div class='headerLogo'>
		<?php
			$header_image = $ANT->settingsGet("general/header_image");
			if ($header_image)
			{
				echo "<img src='/antfs/images/$header_image' />";
			}
			else
			{
				echo "<img src='/images/netric-logo-32.png' />";
				/*
				$company_name = $ANT->settingsGet("general/company_name");
				if ($company_name)
					echo "<div class='headerTitle'>$company_name</div>";
				else
					echo "<div class='headerTitle'>Aereus Network Tools</div>";
				 */
			}
		?>
		</div>
		<!-- end: logo -->
		
		<!-- middle search -->
		<div id='headerSearch'><div id='divAntSearch'></div></div>

		<div style="clear:both;"></div>
	</div>
	<!-- end: application header -->

	<!-- application tabs -->
	<div id='appnav'>
		<div class='topNavbarHr'></div>
		<div class='topNavbarBG' id='apptabs'></div>
		<div class='topNavbarShadow'></div>
	</div>
	<!-- end: application tabs -->

	<!-- application body - where the applications load -->
	<div id='appbody'>
	</div>
	<!-- end: application body -->

	<!-- welcome dialog -->
	<div id='tour-welcome' style='display:none;'>
		<div data-tour='apps/netric' data-tour-type='dialog'></div>
	</div>
	<!-- end: welcome dialog -->

</body>
</html>
