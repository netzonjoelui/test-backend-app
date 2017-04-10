<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
?>
<!DOCTYPE html> 
<head>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<link rel="stylesheet" href="/css/ant_base.css" />
	<link rel="stylesheet" href="/css/ant_mobile.css" />

	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<meta name="apple-mobile-web-app-status-bar-style" content="default" />
	<meta name="apple-mobile-web-app-title" content="Netric" />

    <link rel="shortcut icon" href="/images/icon_196.png" sizes="196x196" />
    <link rel="shortcut icon" href="/images/icon_128.png" sizes="128x128" />
    <link rel="apple-touch-icon" href="/images/icon_128.png" sizes="128x128" />
	<link rel="apple-touch-icon" href="/images/mobile/app_114.png" sizes="114x114" />
	<link rel="apple-touch-icon" href="/images/mobile/app_80.png" sizes="80x80" />
	<link rel="apple-touch-icon" href="/images/icon_72.png" sizes="72x72" />
	<link rel="nokia-touch.icon" href="/images/mobile/app_054.png" sizes="54x54" />
	<link rel="apple-touch-startup-image" href="/images/mobile/apple-startup.png" />


	<?php include("../inc_jslibs.php"); ?>

	<title><?php echo $settings_company_name; ?></title>

	<script>

	/**
	 * Root level global mobile apps
	 *
	 * @global
	 */
	var g_tabs = [
		{ name: "home", title: "Home" },
		{ name: "apps", title: "Applications" }, 
		{ name: "chat", title: "Chat" }, 
		{ name: "notifications", title: "Notifications" }, 
		{ name: "new", title: "Create New" }
	];

	/**
     * Load Ant script
	 */
	function loadAnt()
	{
		Ant.init(function() { main(); });
	}

	var g_userid = <?php print($USERID); ?>;

	var navMain = null; // Navigation (hash) manager
	function main()
	{ 
		Ant.isMobile = true;

		// Set company name
		<?php
			$cname = $ANT->settingsGet("general/company_name");
			if ($cname)
				echo "Ant.account.companyName = \"".str_replace('"', '\"', $cname)."\";\n";
		?>

		navMain = new AntViewsRouter();
		navMain.defaultView = "home";
		navMain.options.viewManager = new AntViewManager();
		navMain.options.viewManager.setViewsSingle(true); // Subviews will hide this view
		navMain.options.viewManager.isMobile = true; // Create mobile version of views
		navMain.onchange = function(path)
		{
			this.options.viewManager.load(path);
		}

		//var mainCon = document.getElementById("content");

		// Add main tabs
		// ------------------------------------------------
	
		// Home 
		var homeView = navMain.options.viewManager.addView("home", {}); // Default view is called index
		homeView.render = function() {
			loadHome(this);
		};

		// Applications
		var appsView = navMain.options.viewManager.addView("apps", {});
		appsView.render = function() {
			loadApps(this);
		};

		// Chat
		var chatView = navMain.options.viewManager.addView("chat", {});
		chatView.render = function() {
			loadChat(this);
		};

		// Notifications
		var notifView = navMain.options.viewManager.addView("notifications", {});
		notifView.render = function() {
			loadNotifications(this);
		};

		// Create new shortcuts
		var newView = navMain.options.viewManager.addView("new", {});
		newView.render = function() {
			loadCreateNew(this);
		};

		// Build footer tabs
		createTabs();
	}

	/****************************************************************************
	*	
	*	Function:	loadApps
	*
	*	Purpose:	Load applications available to this account and this user
	*
	*****************************************************************************/
	/**
	 * Get available applications from the server
	 */
	function loadApps(view)
	{
		view.setViewsSingle(true);
		var indexCon = alib.dom.createElement("div", view.con);
		indexCon.innerHTML = "<div class='loading'></div>";

		var ajax = new CAjax();
		ajax.view = view;
		ajax.indexCon = indexCon;
		ajax.onload = function(root)
		{
			this.indexCon.innerHTML = "";
			for (var i = 0; i < root.getNumChildren(); i++)
			{
				var app = root.getChildNode(i);
				var appTitle = unescape(app.getAttribute('title'));
				var appName  = unescape(app.getAttribute('name'));
				var icon = unescape(app.getAttribute('icon'));

				var entry = alib.dom.createElement("article", this.indexCon);
				alib.dom.styleSetClass(entry, "nav");
				entry.innerHTML = "<a behavior='selectable' href=\"#"+this.view.getPath()+"/"+appName+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
								+ "<span class='icon'><img src='"+icon+"' /></span><h2><span class='more'></span>"+appTitle+"</h2></a>";
				var viewNew = this.view.addView(appName, {name:appName});
				viewNew.render = function()
				{
					var app = new AntApp(this.options.name);
					app.clientMode = "mobile";
					app.main(this);
				}
			}

			this.view.setViewsLoaded();
		};

		var url = "/xml_getapps.php";
		ajax.exec(url);
	}

	/**
	 * Load home dashboard
	 */
	function loadHome(view)
	{
		// Display Activity Log
		var objb = new AntObjectBrowser("status_update");
		objb.setAntView(view);
		objb.addCondition('and', 'associations', 'is_equal', "user:-3");
		objb.addCondition('or', 'owner_id.team_id', 'is_equal', "-3");
		var activityCon = alib.dom.createElement("div", view.con);
		objb.printInline(activityCon, true);
	}

	/**
	 * Load chat
	 *
	 * @param {AntView} view The view for this tab
	 */
	function loadChat(view)
	{
		var messenger = new AntChatMessenger();
		messenger.renderView(view);
	}

	/**
	 * Load notifications list
	 *
	 * @param {AntView} view The view for this tab
	 */
	function loadNotifications(view)
	{
		var notif = new NotificationMan();
		notif.renderView(view);
	}

	/**
	 * Load create new tab
	 *
	 * @param {AntView} view The view for this tab
	 */
	function loadCreateNew(view)
	{
		var newObj = new Ant.NewObjectTool();
		newObj.renderView(view);
	}

	/**
	 * Change page - container by id
	 *
	 * Used to handle showing and hiding AntView containers.
	 * The main reason we are using this is so that later we can implement some 
	 * sliding transitions and create a generic navigation (like a back button) in the header.
	 */
	var lastPage = ""; // The last page that was loaded
	function changePage(page, isBack)
	{
		var isBack = isBack || false;

		// Set effect type
		var slide = (page.indexOf('/') === -1) ? false : true;

		if (lastPage == "") // If this is the first page, then hide the loading div
		{
			var loadingDiv = document.getElementById("loading");
			loadingDiv.style.display = "none";
		}
		else
		{
			var fromPage = document.getElementById(lastPage);
			if (fromPage)
			{
				if (slide)
				{
					if (isBack)
						alib.fx.hideRight(fromPage);
					else
						alib.fx.hideLeft(fromPage);
				}
				else
				{
					alib.fx.fadeOut(fromPage);
				}
			}
		}
		
		var toPage = document.getElementById(page);
		if (toPage)
		{
			if (slide)
			{
				if (isBack)
					alib.fx.showLeft(toPage);
				else
					alib.fx.showRight(toPage);
			}
			else
			{
				alib.fx.fadeIn(toPage);
			}

			lastPage = page;
		}

		// Disable all selectable items
		alib.dom.query('a[behavior="selectable"]', toPage).each(function() {
			alib.dom.styleRemoveClass(this, "selected");
		});

		// Activate main footer tabs tab
		setTabs(page);
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
			var tab = document.getElementById("footerTab-" + g_tabs[i].name);

			if (tab.rootPath == activeApp)
			{
				tab.path = path;

				alib.dom.styleAddClass(tab, "on");
				tab.onmouseover = function() { }
				tab.onmouseout = function() { }
			}
			else
			{
				alib.dom.styleRemoveClass(tab, "on");
			}
		}

		//Ant.updateAppTitle();
	}

	/**
	 * Initialize application tabs
	 */
	function createTabs()
	{
		var tabcon = document.getElementById("footerTabs");

		// Create root level views for each application
		for (var i = 0; i < g_tabs.length; i++)
		{
			var appProps = g_tabs[i];

			var a = alib.dom.createElement("div", tabcon);
			a.id = "footerTab-" + appProps.name;
			alib.dom.styleSetClass(a, "tab");
			alib.dom.styleAddClass(a, appProps.name);
			a.path = appProps.name;
			a.rootPath = appProps.name;
			a.onclick = function()
			{
				document.location.hash = this.path;
			}

			// Setup container for the icon
			var icon = alib.dom.createElement("div", a);
			alib.dom.styleSetClass(icon, "icon");

			var lbl = alib.dom.createElement("div", a);
			lbl.innerHTML = appProps.title;
		}
	}

	</script>
</head>

<body onLoad="loadAnt();">
	<div id='main'>
		<div id='loading'>
			Loading...
		</div>
	</div>
	<div id='footerTabs'>
	</div>
</body>
</html>
