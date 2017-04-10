<?php    
	//require_once("lib/AntConfig.php");    
	//require_once("settings/settings_functions.php");
	require_once("lib/AntConfig.php");
    require_once("ant.php");
	require_once("lib/CDatabase.awp");
	require_once("users/user_functions.php");
	
	$requestedPage = (isset($_REQUEST['p'])) ? $_REQUEST['p'] : null;

	// Try to detect moble devices
	// -------------------------------------------------------------------------------
	$mobile_browser = '0';
	$match = '/(up.browser|up.link|windows ce|iemobile|mmp|symbian|';
	$match .= 'smartphone|midp|wap|phone|vodafone|o2|pocket|mobile|psp)/i';
	if(preg_match($match,strtolower($_SERVER['HTTP_USER_AGENT'])) || isset($_GET['mobile']))
	{
		$mobile_browser++;
	}
	
	if(((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'text/vnd.wap.wml') > 0) 
		|| (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0)) 
			|| ((((isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']) 
				|| isset($_SERVER['X-OperaMini-Features']) || isset($_SERVER['UA-pixels']))))))
	{
		$mobile_browser++;
	}

	$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
	$mobile_agents = array('acs-','alav','alca','amoi','audi','aste','avan','benq',
						   'bird','blac','blaz','brew','cell','cldc','cmd-','dang',
						   'doco','eric','hipt','inno','ipaq','java','jigs','kddi',
						   'keji','leno','lg-c','lg-d','lg-g','lge-','maui','maxo',
						   'midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
						   'newt','noki','opwv','palm','pana','pant','pdxg','phil',
						   'play','pluc','port','prox','qtek','qwap','sage','sams',
						   'sany','sch-','sec-','send','seri','sgh-','shar','sie-',
						   'siem','smal','smar','sony','sph-','symb','t-mo','teli',
						   'tim-','tosh','tsm-','upg1','upsi','vk-v','voda','wap-',
						   'wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-');
	if(in_array($mobile_ua, $mobile_agents))
	{
		$mobile_browser++;
	}
	 
	// && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "webkit")===false
	if($mobile_browser>0 && !$requestedPage)
		header("Location: /mobile/");

	// Get forwarded variables
	// -------------------------------------------------------------------------------
	$err = (isset($_GET['e'])) ? $_GET['e'] : null;
	$FWD = "";
	if (isset($_GET['user']))
		$FWD .= "&user=".$_GET['user'];
	if ($requestedPage)
		$FWD .= "&p=".$requestedPage;
	if (isset($_GET['e']))
		$FWD .= "&e=".$_GET['e'];

	// Get forwarded variables
	// -------------------------------------------------------------------------------
	if ($_SERVER['SERVER_PORT'] != "443" && AntConfig::getInstance()->force_https)
		header("Location: https://".$_SERVER['SERVER_NAME']."/login.php".(($FWD)?"?$FWD":''));

	// TEMP: make sure everyone is using non-https
	/*
	if ($_SERVER['SERVER_PORT'] == "443")
		header("Location: http://".$_SERVER['SERVER_NAME']."/login.php".(($FWD)?"?$FWD":''));
	 */

	// Get account information
	// -------------------------------------------------------------------------------
	$account = $ANT->detectAccount();
	if ($account)
	{
		if ($ANT->accountIsActive())
		{
			$custom_login = $ANT->settingsGet("general/login_image");
			$settings_login_logo = ($custom_login) ? $custom_login : "/images/logo_login.png";
		}
		else
			$err = 4;
	}
	else
		$settings_login_logo = ($settings_login_logo) ? $settings_login_logo : "/images/logo_login.png";
	
	// User is logged in via cookie
	// -------------------------------------------------------------------------------
	if ($err == NULL)
	{
		if (Ant::getSessionVar('uname') && Ant::getSessionVar('uid') && Ant::getSessionVar('aname') && Ant::getSessionVar('aid'))
		{
			header("Location: /authenticate.php".(($FWD)?"?$FWD":''));
			exit();
		}
	}
?>
<!DOCTYPE HTML>
<html>
<head>
	<title>netric login</title>
	<link rel="shortcut icon" href="/favicon.ico" type="image/ico">	
	<link rel="stylesheet" href="/css/login.css" />
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
	<script language="javascript" type="text/javascript" src="/lib/aereus.lib.js/alib_full.cmp.js"></script>

	<script type="text/javascript">
	
	var mobile_browser = false;
	
	function main()
	{
		var fwdpage = "<?php print(($requestedPage)?base64_decode($requestedPage):""); ?>";
		if (fwdpage && document.location.hash)
		{
			fwdpage += document.location.hash;
		}

		if (fwdpage)
		{
			document.logon.p.value = fwdpage;
		}
		else
		{
			// Catch mobile browsers
			if (alib.dom.getClientWidth() < 800)
			{
				document.logon.p.value = "/mobile/main.php";
			}
		}
			
		focusForm();
	}
	
	var g_frmFocussed = false;
	
	function focusForm()
	{ 
		try
		{
			if (!g_frmFocussed)
				document.logon.user.focus();
		}
		catch(e){};
	}


	// Determine the client size and load appropriate css for reactive design
	var cwidth = alib.dom.getClientWidth();
	if (cwidth < 800)
	{
		// Set global flag
		mobile_browser = true;

		// Load mobile css
		var ss = document.createElement("link");
		ss.type = "text/css";
		ss.rel = "stylesheet";
		ss.href = "/css/login_mobile.css";
		document.getElementsByTagName("head")[0].appendChild(ss);
	}
	</script>

</head>

<body onLoad="main();" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">


<div id="maincon">

	<div id="header">
	</div>

	<div id="loginBoxLogo">
		<?php
			// check the error code and generate an appropriate error message switch
			if ($err != NULL)
			{
				switch ($err) 
				{
					case -1:
						$message = "User could not be found!";
						$message .= "<br />Please try logging in again.";
						break;
					
					case 0:
						$message = "Invalid username and/or password!";
						$message .= "<br />Please try logging in again.";
						break;
					
					case 2:
						$message = "Invalid username and/or password!";
						$message .= "<br />Please try logging in again.";
						break;

					case 3:
						$message = "You have logged in from another location.";
						break;

					case 4:
						$message = "Account is not active!";
						$message .= "<br /><br /><a href='http://www.aereus.com/products/ant/order'>www.aereus.com</a>";
						break;
				
					default:
						$message = "An unspecified error occurred.";
						$message .= "<br />Please try logging in again.";
						break;
				}
				echo "<div class='loginMessage'>$message</div>";
			}	
			else
			{
				if (is_numeric($settings_login_logo))
					$settings_login_logo = "/public/antfs/stream_image.php?fid=$settings_login_logo&w=305";
				echo "<img src='$settings_login_logo'>";
			}
		?>
	</div>
	<div id="loginBox">
		<div id="loginBoxForm">
			<form name="logon" method="post" action="authenticate.php">
				<?php if ($err != '4') { ?>
				<h3>Username</h3>
				<input name="user" type="text" maxlength="64" value="<?php if (isset($_GET['user'])) { print($_GET['user']); } ?>" onfocus="g_frmFocussed=true"><br>
				<h3>Password</h3>
				<input name="password" type="password" maxlength="64" onfocus="g_frmFocussed=true">
				<div style='margin-top: 10px;'>
					<input type='checkbox' name='save_password'> Remember Me
				</div>
				<?php
					echo "<input type='hidden' name=\"account\" type=\"text\" value=\"$account\" maxlength=\"40\">";
				?>
				<?php } ?>
				<div id='buttons'>
				<?php if ($err != '4') { ?>
					<button name='SUBMIT' type='submit'>Log In</button>
				<?php } ?>
				<input type="hidden" name="p" value="" />
			</form>		
		</div>
	</div>
</div>

<div id="footer">	
	<a href='http://www.netric.com'>Netric.com</a> |
	<a href='http://www.netric.com/about/privacy'>Privacy Policy</a> |
	<a href='http://www.netric.com/about/tos'>Terms of Service</a> |
	<a href='http://www.netric.com/about/contact'>Contact Us</a><br /><br />
	Copyright &copy; Aereus Corporation, <?php echo date("Y"); ?>. All rights reserved.
</div> 	
										
</body>
</html>
