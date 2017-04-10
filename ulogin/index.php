<?php    
	require_once("../lib/AntConfig.php");
    require_once("ant.php");
	require_once("lib/CDatabase.awp");
	require_once("users/user_functions.php");
	
	$requestedPage = $_REQUEST['p'];

	// Try to detect moble devices
	// -------------------------------------------------------------------------------
	$mobile_browser = '0';
	$match = '/(up.browser|up.link|windows ce|iemobile|mmp|symbian|';
	$match .= 'smartphone|midp|wap|phone|vodafone|o2|pocket|mobile|psp)/i';
	if(preg_match($match,strtolower($_SERVER['HTTP_USER_AGENT'])) || $_GET['mobile'])
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
		$requestedPage = base64_encode("/mobile/main.php");

	// Get forwarded variables
	// -------------------------------------------------------------------------------
	$err = $_GET['e'];
	$FWD = "";
	if ($_GET['user'])
		$FWD .= "&user=".$_GET['user'];
	if ($requestedPage)
		$FWD .= "&p=".$requestedPage;
	if ($_GET['e'])
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

	/**
	 * Global flag only set if the form is ready to submit
	 */
	var formReady = false;
	
	function main()
	{
		focusForm();
	}
	
	var g_frmFocussed = false;
	
	function focusForm()
	{ 
		try
		{
			if (!g_frmFocussed)
				document.getElementById("email").focus();
		}
		catch(e){};
	}

	/**
	 * Handle form validation
	 */
	function validateForm()
	{
		if (formReady)
		{
			document.getElementById("status").style.visibility = "hidden";
			return true;
		}
		else
		{
			getAccntInfo();
			return false;
		}
	}

	/**
	 * Get account info given the entered user name
	 */
	function getAccntInfo()
	{
		var eml = document.getElementById("email").value;
		formReady = false;
		document.getElementById("status").style.visibility = "visible";
		document.getElementById("email_no_found").style.display = "none";

		if (eml)
		{
			var ajax = new CAjax('json');
			ajax.cbData.con = this.con;
			ajax.cbData.view = this;
			ajax.onload = function(ret)
			{
				if (ret && ret.username)
				{
					var actUrl = "<?php echo ($_SERVER['SERVER_PORT'] == "443") ? "https" : "http"; ?>://";
					if (ret.account)
						actUrl += ret.account + ".";
					actUrl += "<?php echo AntConfig::getInstance()->localhost_root; ?>/authenticate.php";
					document.logon.action = actUrl;
					document.logon.user.value = ret.username;
					formReady = true;
					document.logon.submit();
				}
				else
				{
					// User not found for the email address
					formReady = false;
					document.getElementById("status").style.visibility = "hidden";
					document.getElementById("email_no_found").style.display = "block";
				}
			};        
			ajax.exec("/ulogin/getacc.php", [["email", eml]]);
		}
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

	<style type="text/css">
	
	</style>
</head>

<body onLoad="main();" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">

<div id="maincon">

	<div id="header">
	</div>

	<!-- login logo -->
	<div id="loginBoxLogo">
		<img src='/images/logo_login.png' />
	</div>

	<!-- login form -->
	<div id="loginBox">
		<div id="loginBoxForm">
			<form name="logon" method="post" onsubmit="return validateForm();">
				<h3>Email Address</h3>
				<input name="email" id="email" type="text" maxlength="64" value="<?php print($_GET['email']);?>" onfocus="g_frmFocussed=true"><br>
				<div style="display:none;color:red;font-size:12px;" id='email_no_found'>
					The email you entered is not associated with an account. Please try again or <a href="http://www.netric.com/about/contact">click here</a> to contact support.
				</div>
				<h3>Password</h3>
				<input name="password" type="password" maxlength="64" onfocus="g_frmFocussed=true">
				<div id='buttons'>
					<div id='status'>
						<img src="/images/loading.gif" style="vertical-align:middle;" />
						Logging in, please wait.
					</div>
					<button name='SUBMIT' type='submit'>Log In</button>
				</div>
				
				<!-- Hidden vars to be populated with ajax -->
				<input type="hidden" name="p" value="" />
				<input type='hidden' name="user" value="" />
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
