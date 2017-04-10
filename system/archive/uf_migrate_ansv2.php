<?php
	require_once("../lib/AntConfig.php");
	require_once("../userfiles/file_functions.awp");
	require_once("../users/user_functions.php");		
	require_once("../email/email_functions.awp");		
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../email/email_functions.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");
	require_once("../lib/aereus.lib.php/AnsClient.php"); // new v2

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set("memory_limit", "2G");	
	
	$ansClient = new AnsClient();
	$ACCOUNT_DB = "aereus_ant";

	$USERID = null;

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if (AntConfig::getInstance()->version) // limit to current version
	{
		$res_sys = $dbh_sys->Query("select name, database, server from accounts where 
									version='".AntConfig::getInstance()->version."' 
									".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
	}
	else
	{
		$res_sys = $dbh_sys->Query("select name, database, server from accounts 
									where version is null ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
	}

	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$server = $dbh_sys->GetValue($res_sys, $s, 'server');
		if (!$server)
			$server = "localhost";

		AntConfig::getInstance()->localhost = $dbh_sys->GetValue($res_sys, $s, 'name') . "." . AntConfig::getInstance()->localhost_root;
		echo "Updating $dbname - ".AntConfig::getInstance()->localhost."\n";

		if ($dbname)
		{
			$dbh = new CDatabase($server, $dbname);
			$query = "select id, file_title, revision from user_files where ans_key is null and f_deleted is not true
					  AND f_ans_cleaned is false";
			$result = $dbh->Query($query);
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$tempfile = tempnam(AntConfig::getInstance()->data_path."/tmp", "migra-");
				$key = $dbh->dbname."/".$row['id']."/".$row['revision']."/".$row['file_title'];

				echo "Uploading ".($i + 1)." of $num - ".$row['id'];

				$ret = file_get_contents("http://" . AntConfig::getInstance()->localhost . "/userfiles/file_download.awp?fid=" . $row['id']);
				//$ret = UserFilesGetFileContents($dbh, $row['id'], null, null, $tempfile);

				if ($ret)
				{
					file_put_contents($tempfile, $ret);
					$ret = $ansClient->put($tempfile, $key);

					if (!$ret)
						echo "\t\t[failed!]\n\t\t".$ansClient->lastError."\n";
					else
					{
						$dbh->Query("update user_files set ans_key='".$dbh->Escape($key)."', file_size='".filesize($tempfile)."',
									 f_ans_cleaned='t' where id='".$row['id']."'");
						echo "\t\t[done]\n";
					}
				}
				else
				{
					echo "\t\t[failed!]\n\t\t* could not download local file from ".AntConfig::getInstance()->localhost."/".$row['id']."!\n";
				}

				// cleanup
				unlink($tempfile);
			}

			$dbh->FreeResults($result);
		}
	}
?>
