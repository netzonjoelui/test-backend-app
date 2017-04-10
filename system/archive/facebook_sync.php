<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/Ant.php");
	require_once("lib/AntUser.php");
	require_once("lib/CDatabase.awp");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("users/user_functions.php");		
	require_once("email/email_functions.awp");		
	require_once("email/email_functions.awp");
	// Aereus LIB
	require_once("lib/aereus.lib.php/facebook/facebook.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "500M");	
	
	$dbh = new CDatabase();

	$USERID = null;
	$settings_account_number = null;


	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else
		$res_sys = $dbh_sys->Query("select id, database from accounts");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$settings_account_number = $dbh_sys->GetValue($res_sys, $s, 'id');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			// Detach message attachments
			$result = $dbh->Query("select user_id, key_val from system_registry where 
									key_name='/accounts/social/facebook/access_token' and key_val!=''");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				
				$user = new AntUser($dbh, $row['user_id']); 

				// Create our Application instance.
				$facebook = new Facebook(array(
											'appId'  => "160931523922545",
											'secret' => "feb68ee303cf6c9018087084a47caefc",
											'cookie' => false,
											'session' => false,
											));

				Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
				Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;

				$session = $facebook->getSession();
				try
				{
					$me = $facebook->api('/me/friends', array('access_token'=>$row['key_val']));

					if (isset($me['data']) && is_array($me['data']))
					{
						foreach ($me['data'] as $friend)
						{
							$nameparts = explode(" ", $friend['name']);
							if (count($nameparts) >= 2)
							{
								$fname = $nameparts[0];
								$lname = $nameparts[1];

								$objList = new CAntObjectList($dbh, "contact_personal", $user);
								$objList->addCondition("and", "user_id", "is_equal", $user->id);
								$objList->addCondition("and", "first_name", "is_equal", $fname);
								$objList->addCondition("and", "last_name", "is_equal", $lname);
								$obj->getObjects();
								$num2 = $obj->getNumObjects(0, 3);
								for ($c = 0; $c < $num2; $c++)
								{
									$obj = $objList->getObject($c);

									//echo "Updating contact ".$obj->getName()."\n";
									$picbinary = file_get_contents("http://graph.facebook.com/".$friend['id']."/picture?type=large");
									if (sizeof($picbinary)>0)
									{
										$antfs = new CAntFs($dbh, $user);
										$fldr = $antfs->openFolder("%userdir%/Contact Files/".$obj->id, true);
										$file = $fldr->createFile("profilepic.jpg");
										$size = $file->write($picbinary);
										if ($file->id)
										{
											$obj->setValue('image_id', $file->id);
										}
										$obj->save();
									}
									$picbinary = null;
								}
							}
						}
					}
				}
				catch(FacebookApiException $e)
				{
					echo "Exception: ".$e."\n";
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
