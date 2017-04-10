<?php
/**
 * Move files to the new AntFs subsystem from user_files
 * if and only if the data resides in an Ans server
 */
require_once("../lib/AntConfig.php");
require_once("../userfiles/file_functions.awp");
require_once("../users/user_functions.php");		
require_once("../email/email_functions.awp");		
// ANT LIB
require_once("../lib/CDatabase.awp");
require_once("../email/email_functions.awp");
require_once("../lib/aereus.lib.php/CAnsClient.php");
require_once("../lib/aereus.lib.php/AnsClient.php"); // new v2
require_once("../lib/AntFs.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("memory_limit", "2G");	

$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

$ACCOUNT_DB = $_SERVER['argv'][1];;

// Get database to use from account
if (AntConfig::getInstance()->version) // limit to current version
{
	$res_sys = $dbh_sys->Query("select id, name, database, server from accounts where 
								version='".AntConfig::getInstance()->version."' ".(($ACCOUNT_DB)?" and database='$ACCOUNT_DB'":''));
}
else
	$res_sys = $dbh_sys->Query("select id, name, database, server from accounts ".(($ACCOUNT_DB)?" where  database='$ACCOUNT_DB'":''));

$num_sys = $dbh_sys->GetNumberRows($res_sys);
for ($s = 0; $s < $num_sys; $s++)
{
	$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
	$server = $dbh_sys->GetValue($res_sys, $s, 'server');
	if (!$server) $server = "localhost";
	AntConfig::getInstance()->localhost = $dbh_sys->GetValue($res_sys, $s, 'name') . "." . AntConfig::getInstance()->localhost_root;
	echo "Updating $dbname - ".AntConfig::getInstance()->localhost."\n";

	if ($dbname)
	{
		$dbh = new CDatabase($server, $dbname);
		$antfs = new AntFs($dbh);

		// Reset
		$fileObj = CAntObject::factory($dbh, "file");
		$fileObj->fields->clearCache();
		unset($fileObj);
		$fileObj = CAntObject::factory($dbh, "file");
		$fileOtid = objGetAttribFromName($dbh, "file", "id");

		// Get objects and fields that reference folders
		/*
		$query = "select app_object_types.name, app_object_type_fields.name as fname
					from app_object_types, app_object_type_fields
					where app_object_types.id=app_object_type_fields.type_id
					and 
					((app_object_type_fields.type='fkey' and app_object_type_fields.subtype='user_file_categories')
					or (app_object_type_fields.type='object' and app_object_type_fields.subtype='folder'))
					and app_object_types.name!='file' and app_object_types.name != 'folder';";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			$olist = new CAntObjectList($dbh, $row['name'], $user);
			$olist->addCondition("and", $row['fname'], "is_not_equal", ""); // not null
			$olist->getObjects(0, 100000);
			for ($j = 0; $j < $olist->getNumObjects(); $j++)
			{
				$obj = $olist->getObject($j);
				$oldCid = $obj->getValue($row['fname']);

				if (is_numeric($oldCid) && $oldCid<888029) // make sure it is an old dir
				{
					$antFsPath = sysUfToAntFsGetCatPath($dbh, $oldCid);
					$folder = $antfs->openFolder($antFsPath, true);
	

					echo "Moving ".$row['name'].".".$row['fname']." from $oldCid to ".$folder->id."\n";

					$obj->setValue($row['fname'], $folder->id);
					$obj->save(false);
				}
			}
		}
		 */

		// Get all files that have not been moved where ans_key is set (has been uploaded) and is not deleted
		/* used for testing instances without ans
		$query = "select * from user_files where  f_deleted is not true
				  and f_moved is not true";
		 */
		$query = "select * from user_files where ans_key is not null and f_deleted is not true
				  and f_moved is not true LIMIT 1000";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			// Translate the old path into a new one
			$antFsPath = sysUfToAntFsGetCatPath($dbh, $row['category_id']);
			$folder = $antfs->openFolder($antFsPath, true);
			echo "Moving file " . ($i + 1) . " of $num =  " . $row['id'] . "\t";

			if ($folder && $folder->id)
			{
				if (!$row['revision']) $row['revision'] = 1;
				if (!$row['time_updated']) $row['time_updated'] = "now";

				// Add file to new AntFs
				$query = "INSERT into objects_file_act(id, object_type_id, revision, ts_entered, 
					ts_updated, owner_id, creator_id, f_deleted, name, folder_id, dat_ans_key, file_size)
					values('".$row['id']."', '".$fileOtid."', '".$row['revision']."', '".$row['time_updated']."', 
					'".$row['time_updated']."', ".$dbh->EscapeNumber($row['user_id']).", ".$dbh->EscapeNumber($row['user_id']).", 
					'f', '".$dbh->Escape($row['file_title'])."', '".$folder->id."', '".$dbh->Escape($row['ans_key'])."', 
					".$dbh->EscapeNumber($row['file_size']).");";
				$res = $dbh->Query($query);
				if (!$res)
				{
					echo "[failed] - " . $dbh->getLastError() . "\n";
				}
				else
				{
					echo "[success]\n";
					// Update moved flag in old table
					$dbh->Query("update user_files set f_moved='t' where id='".$row['id']."'");
				}
			}
			else
			{
				echo "[failed]\n";
			}
		}



		/*
		// Start at the account root 
		$rootId = UserFilesGetAccountRootId($dbh, null, true);
		$user = new AntUser($dbh, USER_ADMINISTRATOR);
		$antfs = new AntFs($dbh, $user);
		$antFsFolder = $antfs->openFolder("/", true);
		ufCopyFolderToAntFs($dbh, $rootId, $antFsFolder, $antfs);

		// Now copy user directories into the /System/Users directory
		$query = "select id, user_id from user_files where user_id is not null and name='/'";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);
			$user = new AntUser($dbh, $row['user_id']);
			$antfs = new AntFs($dbh, $user);
			$antFsFolder = $antfs->openFolder("%userdir%", true);
			ufCopyFolderToAntFs($dbh, $row['id'], $antFsFolder, $antfs);
		}

		*
		 */

		/*
		$query = "select id, file_title, revision from user_files where ans_key is null";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			$tempfile = tempnam($settings_data_path."/tmp", "migra-");
			$key = $dbh->dbname."/".$row['id']."/".$row['revision']."/".$row['file_title'];

			echo "Uploading ".($i + 1)." of $num - ".$row['id'];

			$ret = UserFilesGetFileContents($dbh, $row['id'], null, null, $tempfile);

			if ($ret)
			{
				$ret = $ansClient->put($tempfile, $key);

				if (!$ret)
					echo "\t\t[failed!]\n\t\t".$ansClient->lastError."\n";
				else
				{
					$dbh->Query("update user_files set ans_key='".$dbh->Escape($key)."', file_size='".filesize($tempfile)."' where id='".$row['id']."'");
					echo "\t\t[done]\n";
				}
			}
			else
			{
				echo "\t\t[failed!]\n\t\t* could not download local file!\n";
			}

			// cleanup
			unlink($tempfile);
		}

		$dbh->FreeResults($result);
		 */
	}
}


function sysUfToAntFsGetCatPath(&$dbh, $CATID, &$added=array())
{
	global $USERID;
	$retval = NULL;
	if (is_numeric($CATID))
	{
		$result = $dbh->Query("select id, name, parent_id, user_id from user_file_categories where id='$CATID'");
			
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			
			//$retval = "<span style='color:#FFFFFF;'>&nbsp;/&nbsp;</span>";
			//if ($row['name'] != '/')
			
			// Just in case, look for infinite loops
			if (in_array($row['id'], $added))
				return "";
			else
				$added[] = $row['id'];

			$retval = $row['name'];
			
			if ($row['parent_id'])
			{
				$pre = sysUfToAntFsGetCatPath($dbh, $row['parent_id'], $added);
				if ($pre == '/')
					$retval = $pre.$retval;
				else
					$retval = $pre."/".$retval;
			}
			else
			{
				if ($row['name'] == '/' && is_numeric($row['user_id'])) // this is a user root dir
				{
					$retval = "/System/Users/".$row['user_id'];
				}
			}
		}
		$dbh->FreeResults($result);
	}

	return $retval;
}

function ufCopyFolderToAntFs($dbh, $fid, $antFsFolder, $antfs)
{
	// Get folders
	$query = "SELECT id, user_id, name FROM user_file_categories WHERE parent_id='$fid' AND f_deleted is not true";
	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);
		$fldr = $antFsFolder->openFolder($row['name'], true);
		ufCopyFolderToAntFs($dbh, $row['id'], $fldr, $antfs); // Recursively move through directories
	}
	$dbh->FreeResults($result);
	
	// Get files
	$query = "SELECT id, file_title, user_id FROM user_files WHERE category_id='$fid' and f_deleted is not true";
	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetRow($result, $i);

		$tempfile = tempnam(AntConfig::getInstance()->data_path."/tmp", "migra-");
		$key = $dbh->dbname."/".$row['id']."/".$row['revision']."/".$row['file_title'];

		echo "Uploading ".($i + 1)." of $num - ".$row['id'];

		$ret = UserFilesGetFileContents($dbh, $row['id'], null, null, $tempfile);

		if ($ret)
		{
			// TODO: figure out how to make this file have the same id as the imported file
			$antFsFolder->importFile($tempfile, $row['file_title']);
			/*
			$ret = $ansClient->put($tempfile, $key);

			if (!$ret)
				echo "\t\t[failed!]\n\t\t".$ansClient->lastError."\n";
			else
			{
				$dbh->Query("update user_files set ans_key='".$dbh->Escape($key)."', file_size='".filesize($tempfile)."' where id='".$row['id']."'");
				echo "\t\t[done]\n";
			}
			 */
		}
		else
		{
			echo "\t\t[failed!]\n\t\t* could not download local file!\n";
		}

		// cleanup
		unlink($tempfile);
	}
}
