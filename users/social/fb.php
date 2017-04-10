<?php
	require_once("../../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Email.php");
	require_once("lib/aereus.lib.php/CCache.php");
	//require_once("lib/aereus.lib.php/CSessions.php");
	require_once("lib/aereus.lib.php/facebook/facebook.php");
	require_once("email/email_functions.awp");
	require_once("lib/aereus.lib.php/CCache.php");

	$dbh = $ANT->dbh;

	$fbconfig['appid' ]  = "160931523922545";
	$fbconfig['api'   ]  = "7ae56638f45ee2bdb27838d355224f17";
	$fbconfig['secret']  = "feb68ee303cf6c9018087084a47caefc";

	if ($_REQUEST['access_token'])
	{
		echo "Set pref to: ".$_REQUEST['access_token'];
		exit;
		//UserSetPref($dbh, $USER->id, "/accounts/social/facebook/access_token", $_REQUEST['access_token']);
		//echo "<script type='text/javascript'> window.close(); </script>";
	}

	$needauth = true;

	// Create our Application instance.
	$facebook = new Facebook(array(
								'appId'  => $fbconfig['appid'],
								'secret' => $fbconfig['secret'],
								'cookie' => false,
								'session' => false,
								));

	// We may or may not have this data based on a $_GET or $_COOKIE based session.
	// If we get a session here, it means we found a correctly signed session using
	// the Application Secret only Facebook and the Application know. We dont know
	// if it is still valid until we make an API call using the session. A session
	// can become invalid if it has already expired (should not be getting the
	// session back in this case) or if the user logged out of Facebook.
	$session = $facebook->getSession();
	if ($session)
		UserSetPref($dbh, $USER->id, "/accounts/social/facebook/access_token", $session['access_token']);

	$token = UserGetPref($dbh, $USER->id, "/accounts/social/facebook/access_token");

	//if ($token || $session)
	//{
		$fbme = null;
		try
		{
			//$params = array();
			//if ($token)
				//$params['access_token'] = $token;
			$me = $facebook->api('/me', array('access_token'=>$token));
			//$me = $facebook->api('/100001667958782'); // GWC
			//$me = $facebook->api('/sky.stebnicki/picture'); // Sky
			echo "You are currently logged in as: <strong>".$me['name']."</strong>";
			echo "<script type='text/javascript'>window.close();</script>";
			$needauth = false;
			/*
			echo "<br ><pre>";
			var_dump($me);
			echo "</pre>";
			 */

		}
		catch(FacebookApiException $e)
		{
			//echo "<strong>Facebook exception</strong>: ".$e;
			$needauth = true;
			UserSetPref($dbh, $USER->id, "/accounts/social/facebook/access_token", "");
		}
	//}

	if ($needauth && !$_GET['session'])
	{
		 $url = $facebook->getLoginUrl(array("req_perms"=>"offline_access,user_interests,user_events"));
		 echo $url;
		 //header("Location: $url");
	}
?>
