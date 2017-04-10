<?php
// authenticate username/password
// returns: 0 if username and password is incorrect
//          1 if username and password are correct

// ANT
require_once("lib/AntConfig.php");
require_once("ant.php");
require_once("lib/CDatabase.awp");
require_once("lib/AntSystem.php");
require_once("lib/CBrowser.awp");
require_once("users/user_functions.php");
require_once("customer/customer_functions.awp");
require_once("lib/AntUser.php");
require_once('lib/ServiceLocatorLoader.php');

// ALIB
//require_once("lib/aereus.lib.php/CCache.php");
//require_once("lib/aereus.lib.php/CSessions.php");

// Get new netric authentication service
if(!isset($dbh))
	$dbh = null;

$sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
$authService = $sl->get("AuthenticationService");

$binfo = new CBrowser();
$antsys = new AntSystem();

// p will be raw if from a form, but encoded if from a get url
$fwdpage = isset($_POST["p"]) ? $_POST['p'] : "";
if (!$fwdpage && isset($_GET['p']))
	$fwdpage = base64_decode($_GET['p']);

$account = $ANT->accountName;

// Check if user name and password has been saved in cookie
if (isset($_REQUEST["user"]) && isset($_REQUEST["password"]))
{
	$pass = $_REQUEST["password"];
	$username = strtolower($_REQUEST["user"]);
}
else if ($ANT->getSessionVar('uname') && $ANT->getSessionVar('aname') && $ANT->getSessionVar('uid'))
{
	$pass = "saved";
	$username = $ANT->getSessionVar('uname');
	$uid = $ANT->getSessionVar('uid');
}

if ($username && $pass && $account)
{
	$acctinf = $antsys->getAccountInfoByName($account);
	if ($acctinf['id'])
	{
		// Set variables
		$ANT->setSessionVar('db', $acctinf['database']);
		$ANT->setSessionVar('dbs', $acctinf['server']);
		$ANT->setSessionVar('aid', $acctinf['id']);

		$dbh = $ANT->dbh;
        $ANT->id = $acctinf['id'];



		// Now check user table for user name and password combinations
		if (isset($uid))
			$ret = $uid;
		else
			$ret = AntUser::authenticate($username, $pass, $dbh);
	}
	else
	{
		$ret = false;
	}

	if ($ret)
	{
		$user = $ANT->getUser($ret);

        // Set variables
        $ANT->setSessionVar('uname', $username);
		$ANT->setSessionVar('uid', $ret);
		$ANT->setSessionVar('aid', $acctinf['id']);
		$ANT->setSessionVar('aname', $account);

        // Store the new authentication string
        $authString = $authService->authenticate($username, $pass);
        setcookie("Authentication", $authString, time()+60*60*24*30);

		// Automatically determine timezone
		if (@function_exists(geoip_record_by_name) && @function_exists(geoip_time_zone_by_country_and_region) && $_SERVER['REMOTE_ADDR'])
		{
			$region = @geoip_record_by_name($_SERVER['REMOTE_ADDR']);
			if ($region)
				$ANT->setSessionVar('tz', geoip_time_zone_by_country_and_region($region['country_code'], $region['region']));
		}

		// Get default domain for this account
		$defDom = $ANT->getEmailDefaultDomain($account, $dbh);

		// Make sure default domain exists in the mailsystem
		$antsys->addEmailDomain($acctinf['id'], $defDom);

		// Make sure that an email account exists for each domain
		$emailAddress = $user->verifyEmailDomainAccounts(($pass!='saved') ? $pass : null);

		// Make sure default groups exist
		$user->verifyDefaultUserGroups();

		// Make sure default team exists
		$user->verifyDefaultUserTeam();

		// Make sure the user has a customer number
		$user->getAereusCustomerId();

		// Make sure default users exist
		$user->verifyDefaultUsers();

		// Check if save user name and password are checked
		if (isset($_REQUEST["save_password"]))
		{
		}
		
		// Find out if trial period has expired
		if ($ANT->settingsGet("general/trial_expired") == 't')
			$fwdpage = "/wizard.php?wizard=expired";

		// Find out if account was suspended due to billing errors
		if ($ANT->settingsGet("general/suspended_billing") == 't')
			$fwdpage = "/wizard.php?wizard=ubilling";

		// Determine if this is the first time this users has logged in, if so, then redirect
		if ($ANT->settingsGet("general/acc_wizard_run") == 'f')
			$fwdpage = "/wizard.php?wizard=account";
		/* We are no longer requiring a wizard the first time.
		else if ($user->isFirstLogin() || $user->getSetting("general/f_forcewizard") == 't')
			$fwdpage = "/wizard.php?wizard=user";
		 */

		// Set last login variable
		$user->logLogin();

		// redirect to protected page
		if ($fwdpage)
			$page = $fwdpage;
		else if ($settings_redirect_to)
			$page = "/$settings_redirect_to";
		else
		{
			/*
			if ($binfo->ie)
				$page = "http://".$_SERVER['SERVER_NAME']."/main"; // IE is apparently incapable of handling https well
			else
			 */
				$page = "/main";
		}
	}
	else
	{
		// redirect to error page
		header("Location: logout.php?e=2&user=$user&account=$account&p=".base64_encode($fwdpage));
		exit();
	}
}
else
{
	// redirect to error page
	header("Location: index.php");
	exit();
}

header("Location: " . $page);
exit();

/*
echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">
	  <html>
		<head>
			<title>Loading applicaiton...</title>
			<script language='javascript' type='text/javascript'>
			function LoadPage()
			{
				document.location='$page';
			}
			</script>
		</head>
		<body onload=\"LoadPage();\" style='background-color:#FFF;'>
		<div style='width:100px;height:25px;border:1px solid blue;padding-top:3px;padding-left:3px;
					font-weight:bold;'>
			Loading...
		</div>
		</body>
	   </html>";
?>
 */
