<?php
/**
 * @fileoverview This worker handles ant file system processes
 */
require_once('lib/Worker.php'); 
require_once('lib/AntFs.php'); 
require_once("lib/aereus.lib.php/AnsClient.php");

/**
 * Add functions to list of callbacks
 */
if (is_array($g_workerFunctions))
{
	$g_workerFunctions["afs/file_purge"] = "afs_file_purge";
	$g_workerFunctions["antfs/file_upload_ans"] = "antfs_file_upload_ans"; // new upload function for AntFs
	$g_workerFunctions["afs/file_upload_ans"] = "afs_file_upload_ans"; // DEPRICATED
}

/**
 * Purge a file from local afs
 *
 * Workload Variables:
 * fid = the unique file id to delete
 * user_id = (optional) the id of the user trying to delete this file
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function afs_file_purge($job)
{
	$data = unserialize($job->workload());
	$dbh = $job->dbh;

	// Set to current user if not set
	if (!$data['user_id'])
		$data['user_id'] = $job->user->id;

	$antfs = new AntFs($dbh, $job->user);
	$antfs->removeFileById($data['fid']);

	return true;
}

/**
 * DEPRICATED: Upload a file to ans in the background - old CAntFs system, replaced by antfs_file_upload_ans below
 *
 * Workload Variables:
 * full_local_path = full path to the file to be uploaded
 * fid = the file id eto upload
 * revision = the revision at the time of uploading
 *
 * // The below variables are used if special processing like video encoding
 * process_function = (optional) special processor for file like video conversion
 * local_path = the path of the local file
 * fname = the name of the file on disk (usually system generated)
 * name = the actual file name - human form
 *
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function afs_file_upload_ans($job)
{
	$data = unserialize($job->workload());
	$dbh = $job->dbh;

	$ansClient = new AnsClient();

	if ($data['process_function'])
	{
		$newname = UserFilesProcess($data['local_path'], $data['fname'], $data['name'], $data['process_function']);
		$postfix = substr($newname, strrpos($newname, ".")+1);
		if ($newname != $data['fname'])
		{
			$data['full_local_path'] = $data['local_path']."/".$newname;
			$dbh->Query("update user_files set file_name='".$dbh->Escape($newname)."', 
									file_title='".$dbh->Escape($data['name'])."', 
									file_type='".$dbh->Escape($postfix)."', 
									file_size=".$dbh->EscapeNumber(filesize($data['full_local_path']))." 
									where id='".$data['fid']."'");
		}
	}

	// Upload file
	$key = $dbh->dbname."/".$data['fid']."/".$data['revision']."/".$data['name'];
	$ret = $ansClient->put($data['full_local_path'], $key);

	if ($ret)
	{
		$commit = true;

		if ($commit)
		{
			$dbh->Query("update user_files set ans_key='".$dbh->Escape($key)."' where id='".$data['fid']."'");
			unlink($data['full_local_path']);
		}
	}
	else
	{
		echo "\tERROR: ".$ansClient->lastError."\n";
	}

	return $ret;
}

/**
 * Upload a file to ans in the background
 *
 * Workload Variables:
 * full_local_path = full path to the file to be uploaded
 * fid = the file id eto upload
 * revision = the revision at the time of uploading
 *
 * // The below variables are used if special processing like video encoding
 * process_function = (optional) special processor for file like video conversion
 * full_local_path = the path of the local file
 * name = the name of the file
 * fid = the unique id of the file
 * revision = the revision of the file to upload
 *
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function antfs_file_upload_ans($job)
{
	$data = unserialize($job->workload());

	if (!$data['fid'] || !$data['name'] || !$data['revision'] || !$data['full_local_path'] || !$data['local_path'])
		return false;

	$dbh = $job->dbh;
	$antfs = new AntFs($dbh, $job->user);
	$file = $antfs->openFileById($data['fid']);
	if (!$file)
		return false;

	$ansClient = new AnsClient(); // new ANS

	if ($data['process_function'])
	{
		// TODO: work on this
		/*
		$newname = UserFilesProcess($data['local_path'], $data['fname'], $data['name'], $data['process_function']);
		$postfix = substr($newname, strrpos($newname, ".")+1);
		if ($newname != $data['fname'])
		{
			$data['full_local_path'] = $data['local_path']."/".$newname;
			$dbh->Query("update user_files set file_name='".$dbh->Escape($newname)."', 
									file_title='".$dbh->Escape($data['name'])."', 
									file_type='".$dbh->Escape($postfix)."', 
									file_size=".$dbh->EscapeNumber(filesize($data['full_local_path']))." 
									where id='".$data['fid']."'");
		}
		*/
	}

	// Upload file
	$key = $dbh->dbname."/antfs/".$data['fid']."/".$data['revision']."/".$data['name'];
	$ret = $ansClient->put($data['full_local_path'], $key);

	if ($ret)
	{
		// Check if the actual file data was updated
		if ($file->getValue("dat_local_path") == $data['local_path'])
		{
			$file->setValue("dat_ans_key", $key);
			$file->setValue("dat_local_path", "");
			$file->save(false);
		}

		// Check if we are updating the file at its most current version
		if ($file->revision != $data['revision'])
		{
			// Updating a past revision, this can happen if the user updates the file again
			// before the last background upload completed. In that case we want to make sure we only
			// update the revision and not the most recent version which could potentially have
			// already been updated by another process.
			$file->updateRevField("dat_ans_key", $key, $data['revision']);
			$file->updateRevField("dat_local_path", "", $data['revision']);
		}

		unlink($data['full_local_path']);
	}
	else
	{
		echo "\tERROR: ".$ansClient->lastError."\n";
	}

	return $ret;
}
