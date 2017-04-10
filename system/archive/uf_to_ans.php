<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../userfiles/file_functions.awp");
	require_once("../users/user_functions.php");
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	ini_set("max_execution_time", "28800");	
	ini_set('default_socket_timeout', "28800"); 

	$DEBUG = TRUE;
	
	$ans = new CAnsCLient();

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	$ACCOUNT_DB = "aereus_ant";

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select id, database from accounts where database='$ACCOUNT_DB'");
	else
		$res_sys = $dbh_sys->Query("select id, database from accounts where f_use_ans is not false");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		$acid = $dbh_sys->GetValue($res_sys, $s, 'id');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);
			$result = $dbh->Query("select id, file_name, file_title, file_type, category_id, user_id from user_files 
									where (remote_file='' or remote_file is null) and file_name!=''
									and user_files.category_id in (select id from user_file_categories where id is not null)
									order by f_ans_skipped, time_updated DESC limit 10000");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$title = ($row['file_title']) ? $row['file_title'] : $row['file_name'];
				$settings_account_number = UserFilesGetCatAccount($dbh, $row['category_id']);
				$settings_account_number2 = UserGetAccount($dbh, $row['user_id']);
				$size = "";

				switch ($row['file_type'])
				{
				case 'jpg':
				case 'jpeg':
					$type = "image/jpeg";
					break;
				case 'png':
					$type = "image/png";
					break;
				case 'bmp':
					$type = "image/bmp";
					break;
				case 'gif':
					$type = "image/gif";
					break;
				default:
					$type = "application/octet-stream";
					break;
				}

				if ($DEBUG)
					echo "Sending ".($i + 1)." of $num:\t".$row['id']." -\t";

				// New
				$file_dir = $settings_data_path."/$settings_account_number/userfiles";
				$file_dir2 = $settings_data_path."/$settings_account_number2/userfiles";
				// TODO: temp recovery
				$file_dir3 = $settings_server_root."/data2/$settings_account_number/userfiles";
				$file_dir4 = $settings_server_root."/data2/$settings_account_number2/userfiles";

				if ($row['user_id'])
				{
					$file_dir .= "/".$row['user_id'];
					$file_dir2 .= "/".$row['user_id'];
					$file_dir3 .= "/".$row['user_id'];
					$file_dir4 .= "/".$row['user_id'];
				}

				if (!file_exists($file_dir."/".$row['file_name']))
				{
					if (file_exists($file_dir2."/".$row['file_name']))
						$file_dir = $file_dir2;
					else if (file_exists($file_dir3."/".$row['file_name']))
						$file_dir = $file_dir3;
					else if (file_exists($file_dir4."/".$row['file_name']))
						$file_dir = $file_dir4;
				}

				if (!file_exists($file_dir."/".$row['file_name']) || !filesize($file_dir."/".$row['file_name']))
				{
					if ($DEBUG)
						echo "Skipping\n";

					$dbh->Query("update user_files set f_ans_skipped='t' where id='".$row['id']."'");
					continue;
				}
				else
				{
					$size = filesize($file_dir."/".$row['file_name']);
				}

				$acc_name = UserAccGetNameFromId($dbh, $settings_account_number);
				$url = "http://$acc_name.$settings_localhost_root/files/".$row['id'];

				echo $acc_name."\t";

				$ret = $ans->putFile($file_dir."/".$row['file_name'], $title, $type, $row['id'], "/userfiles/$settings_account_number");
				//$ret = $ans->putFileByUrl($url, $title, $type, $settings_account_number."/".$row['id'], "/userfiles", $size);	

				if ($DEBUG)
					echo "$ret\n";

				if ("1" == $ret)
				{
					//$file_dir .= "/".$row['file_name'];

					//if ($ans->fileExists($title, $row['id'], "/userfiles"))
					if ($ans->fileVerify($title, $row['id'], "/userfiles/$settings_account_number"))
					{
						$dbh->Query("update user_files set remote_file='".$dbh->Escape($title)."' where id='".$row['id']."'");
						/*
						unlink($file_dir."/".$row['file_name']);
						if ($DEBUG)
							echo "\t\t--verified and removed file\n\n";
						 */

						@mkdir($settings_data_path."/backup/$settings_account_number", 0777);
						@rename($file_dir."/".$row['file_name'], $settings_data_path."/backup/$settings_account_number/".$row['id']);

						if ($DEBUG)
							echo "\t\t-- moved file to backup\n\n";
					}
					else
					{
						$dbh->Query("update user_files set remote_file=NULL where id='".$row['id']."'");
					}

				}
				else
				{
					$dbh->Query("update user_files set f_ans_skipped='t' where id='".$row['id']."'");
				}
			}
			$dbh->FreeResults($result);
		}
	}
?>
