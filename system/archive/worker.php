<?php
	require_once("../lib/AntConfig.php");
	require_once("lib/Ant.php");
	require_once("lib/ant_error_handler.php");
	require_once("lib/CAntFs.awp");
	require_once("lib/AntUser.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("lib/CAntObject.php");
	require_once("lib/Email.php");
	require_once("customer/CCustomer.php");
	require_once("email/email_functions.awp");
	require_once("lib/aereus.lib.php/CAnsClient.php");
	require_once("lib/aereus.lib.php/AnsClient.php");
	require_once("lib/aereus.lib.php/CAntCustomer.php");
	require_once("lib/aereus.lib.php/CAntOpportunity.php");
	require_once('lib/aereus.lib.php/antapi.php');

	require_once("PEAR.php");
	require_once('Mail.php');
	require_once('Mail/mime.php');

	ini_set("max_execution_time", "7200");	
	ini_set("max_input_time", "7200");	
	ini_set("memory_limit", "2G");	

	$dbhSys = new CDatabase($settings_db_syshost, $settings_db_sysdb, $settings_db_sysuser, $settings_db_syspass);
	$ans = new CAnsCLient();
	$ansClient = new AnsClient("anstest.aereusdev.com"); // new ANS

	$pid = $argv[1];
	$function_name = ""; 	// Function to work on
	$data = "";				// Blob of data
	$output_oid = null; 	// If a job produces output, it will be streamed to an oid in pgsql and the id of this var will be set and later saved.
	$output_name = "";
	$output_ctype = "";

	if ($pid)
	{
		$function_name = "test";
		$result = $dbhSys->Query("select function_name, data, progress from workerpool where id='$pid'");
		if ($dbhSys->GetNumberRows($result))
		{
			$row = $dbhSys->GetRow($result, 0);
			$function_name = $row['function_name'];
			$data = unserialize($row['data']);
		}
	}
	else
	{
		// Make sure we only have 5 processes running at once
		$result = $dbhSys->Query("select count(*) as cnt from workerpool where progress>0");
		if ($dbhSys->GetValue($result, 0, "cnt")>=5)
		{
			echo "5 processes are already in in-progress. Exiting...\n";
			exit;
		}

		$exit = false;
		$query = "select id, function_name, data, progress from workerpool where progress='-1'";
		if ($settings_workerpool_funct)
			$query .= " and function_name='$settings_workerpool_funct' ";
		$query .= " order by id limit 1000";
		$result = $dbhSys->Query($query);
		$num = $dbhSys->GetNumberRows($result);
			echo "Found $num processes in the queue\n";
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbhSys->GetRow($result, $i);
			$function_name = $row['function_name'];
			$data = unserialize($row['data']);
			$pid = $row['id'];
			$exit = false;

			// Check version
			if ($data['account_id'])
			{
				$res2 = $dbhSys->Query("select version from accounts where id='".$data['account_id']."'");
				if ($dbhSys->GetNumberRows($res2))
				{
					if ($settings_version)
					{
						echo "Checking against version $settings_version for ".$data['account_id']."\t";
						if ($dbhSys->GetValue($res2, "version", 0) == $settings_version)
							break;
						echo "FAIL!";
					}
					else if (!$dbhSys->GetValue($res2, "version", 0))
						break;
				}
			}
			else
			{
				break;
			}

			$exit = true;
		}

		if ($exit || !$pid)
			exit;
		else
			echo "Running process $pid - $function_name\n";
	}

	switch ($function_name)
	{
	case "upload_ans_file";
		if ($data['account_id'])
		{
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$antAcct = new Ant($data['account_id']);

			if ($data['process_function'])
			{
				$newname = UserFilesProcess($data['local_path'], $data['fname'], $data['name'], $data['process_function']);
				$postfix = substr($newname, strrpos($newname, ".")+1);
				if ($newname != $data['fname'])
				{
					$data['full_local_path'] = $data['local_path']."/".$newname;
					$antAcct->dbh->Query("update user_files set file_name='".$antAcct->dbh->Escape($newname)."', 
											file_title='".$antAcct->dbh->Escape($data['name'])."', 
											file_type='".$antAcct->dbh->Escape($postfix)."', 
											file_size=".$antAcct->dbh->EscapeNumber(filesize($data['full_local_path']))." 
											where id='".$data['fid']."'");
				}
			}

			$dbhSys->Query("update workerpool set progress='50' where id='$pid'");

			// Upload file
			$key = $antAcct->dbh->dbname."/".$data['fid']."/".$data['revision']."/".$data['name'];
			$ret = $ansClient->put($data['full_local_path'], $key);

			// Upload to ANS
			/*
			$ret = $ans->putFile($data['full_local_path'], $data['name'], $data['content_type'], 
								 $data['fid'], "/userfiles/".$data['account_id']);
			if ($ret == "1")
			*/
			if ($ret)
			{
				$commit = true;

				// Make sure file has not been updated since last 
				/*
				if ($data['revision'])
				{
					$result = $antAcct->dbh->Query("select revision from user_files where id='".$data['fid']."'");
					if ($antAcct->dbh->GetNumberRows($result))
					{
						if ($antAcct->dbh->GetValue($result, 0, "revision") > $data['revision'])
						{
							// Remove file from ANS
							$ans->deleteFile($data['name'], $data['fid'], "/userfiles/".$data['account_id']);
							// TODO: restart upload_ans_file
						}
					}
				}
				 */

				if ($commit)
				{
					//$antAcct->dbh->Query("update user_files set remote_file='".$antAcct->dbh->Escape($data['name'])."' where id='".$data['fid']."'");
					$antAcct->dbh->Query("update user_files set ans_key='".$antAcct->dbh->Escape($key)."' where id='".$data['fid']."'");
					unlink($data['full_local_path']);
				}
			}
			else
			{
				echo "\tERROR: ".$ansClient->lastError."\n";
			}

			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	case "file_purge";
		if ($data['account_id'])
		{
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$antAcct = new Ant($data['account_id']);
			UserFilesRemoveFile($antAcct->dbh, $data['fid'], $data['user_id'], false, true);
			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	case "object_import";
		if ($data['account_id'] && $data['data_file_id'] && $data['obj_type'])
		{
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$USERID = $data['user_id'];
			$ACCOUNT = $data['account_id'];
			$antAcct = new Ant($data['account_id']);
			$dbh = $antAcct->dbh;
			include("system/worker_import.php");
			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	// Update image of non-linked customers if email address matches
	case "contact_sync_image";
		if ($data['account_id'] && $data['contact_id'] && $data['user_id'])
		{
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$USERID = $data['user_id'];
			$ACCOUNT = $data['account_id'];
			$antAcct = new Ant($data['account_id']);
			$dbh = $antAcct->dbh;
			$user = new AntUser($dbh, $data['user_id']);

			$conObj = new CAntObject($dbh, "contact_personal", $data['contact_id'], $user);
			$imgid = $conObj->getValue("image_id");

			if ($conObj->getValue("email") || $conObj->getValue("email2"))
			{
				$olist = new CAntObjectList($dbh, "customer", $user);
				$olist->addCondition("and", "image_id", "is_equal", "");
				$olist->addCondition("and", "email", "is_equal", $conObj->getValue("email"));
				$olist->addCondition("or", "email2", "is_equal", $conObj->getValue("email"));
				$olist->getObjects(0, 10);
				$num = $olist->getNumObjects();
				for ($c = 0; $c < $num; $c++)
				{
					$custObj = $olist->getObject($c);
					$custObj->setValue("image_id", $imgid);
					$custObj->save(false);
				}
			}

			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	// Print customer mailing labels
	case "customer_pdf_mailing_labels";
		if ($data['account_id'] && $data['user_id'])
		{
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$USERID = $data['user_id'];
			$ACCOUNT = $data['account_id'];
			$antAcct = new Ant($data['account_id']);
			$dbh = $antAcct->dbh;
			$user = new AntUser($dbh, $data['user_id']);
			include("workers/customers/pdf_mailing_labels.php");
			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	// Send email messages
	case "send_email";
		if ($data['account_id'])
		{
			$USERID = $data['user_id'];
			$ACCOUNT = $data['account_id'];
			$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
			$antAcct = new Ant($data['account_id']);
			$dbh = $antAcct->dbh;
			$EMAILUSERID = EmailGetUserId($dbh, $USERID);
			$EMAILUSERNAME = EmailGetUserName($dbh, $USERID);
			include("system/worker_mail.php");
			$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		}
		break;

	case "create_account";
		$dbhSys->Query("update workerpool set progress='1' where id='$pid'");
		$antAcct = new Ant($data['account_id']);
		$dbh = $antAcct->dbh;
		include("system/worker_createaccount.php");
		$dbhSys->Query("update workerpool set progress='100' where id='$pid'");
		break;

	case "test";
		for ($i = 0; $i < 100; $i++)
		{
			$dbhSys->Query("update workerpool set progress='".($i+1)."' where id='$pid'");
			sleep(3);
		}
		break;
	}

	// Update Status
	if ($pid)
	{
		if ($output_oid)
		{
			$dbhSys->Query("insert into workerpool_results(job_id, result, result_name, result_ctype, ts_finished)
							values('$pid', '$output_oid', '".$dbhSys->Escape($output_name)."', '".$dbhSys->Escape($output_ctype)."', 'now'))");
		}

		$dbhSys->Query("delete from workerpool where id='$pid'");
	}
?>
