<?php
	require_once('lib/AntConfig.php'); 
	require_once('lib/Worker.php'); 
	require_once('lib/CAntObjectImporter.php');
	require_once('lib/AntObjectSync.php');

	if (is_array($g_workerFunctions))
	{
		$g_workerFunctions["lib/object/index"] = "object_index";
		$g_workerFunctions["lib/object/save"] = "object_save";
		$g_workerFunctions["lib/object/import"] = "object_import";
		$g_workerFunctions["lib/object/syncstat"] = "object_sync_stat";
	}

	/**
	 * Index an object
	 *
	 * @param WorkerJob $job this job. The workload includes oid, obj_type, and optional index_type
	 */
	function object_index($job)
	{
		$data = unserialize($job->workload());
		$dbh = $job->dbh;
		$oid = $data['oid'];
		$obj_type = $data['obj_type'];
		$indexType = null;
        
        if(isset($data['index_type']))
            $indexType = $data['index_type'];

		// Make sure we have the required data
		if (!$dbh || !$oid || !$obj_type)
			return false;

		$obj = new CAntObject($dbh, $obj_type, $oid);
		
        if ($indexType)
			$obj->setIndex($indexType);
		$ret = $obj->index(true, true);

		return $ret;
	}

	/**
	 * Save object as a background process
	 *
	 * @param WorkerJob $job this job. The workload includes oid, obj_type, vals, and mvals in the data
	 */
	function object_save($job)
	{
		$data = unserialize($job->workload());
		$dbh = $job->dbh;
		$oid = $data['oid'];
		$obj_type = $data['obj_type'];
		$vals = ($data['vals']) ? $data['vals'] : array();
		$mvals = ($data['mvals']) ? $data['mvals'] : array();

		// Make sure we have the required data
		if (!$dbh || !$oid || !$obj_type)
			return false;

		$obj = new CAntObject($dbh, $obj_type, $oid);

		// Set single values
		foreach ($vals as $fname=>$fval)
			$obj->setValue($fname, $fval);

		// Set multi values
		foreach ($mvals as $fname=>$mval)
		{
			foreach ($mval as $fval)
			{
				$obj->setMValue($fname, $fval);
			}
		}

		$obj->save();

		return true;
	}

	/**
	 * Import data
	 *
	 * @param {WorkerJob} $job A handle to the current worker job
	 * @return {bool} true on success, false on failure
	 */
	function object_import($job)
	{
		$data = unserialize($job->workload());
		$dbh = $job->dbh;

		$imp = new CAntObjectImporter($dbh, $data['obj_type']);
		if ($data['data_file'])
			$imp->setSourceFile($data['data_file']);
		else if ($data['data_file_id'])
			$imp->setSourceAntFS($data['data_file_id']);
		else
			return false;

		// Set column to field maps
		if (is_array($data['map_fields']))
		{
			foreach ($data['map_fields'] as $srccol=>$fld)
				$imp->addFieldMap($srccol, $fld);
		}

		// Set field defaults (to be used if source cell is empty)
		if (is_array($data['field_defaults']))
		{
			foreach ($data['field_defaults'] as $fname=>$fval)
				$imp->addFieldDefault($fname, $fval);
		}

		// Set fields/columns to use for merging into existing records
		if (is_array($data['mergeby']))
		{
			foreach ($data['mergeby'] as $mb)
				$imp->addMergeBy($mb);
		}

		// Run the import
		$imp->import();

		// Email login information
		if ($data['send_notifaction_to'])
		{
			$message = "This email is being sent to inform you that import job id ".$job->id." has completed\r\n";
			$message .= $imp->numImported." ".$imp->obj->titlePl." were successfully imported";
			$headers = array();
			$headers['From']  = AntConfig::getInstance()->email['noreply'];
			$headers['To']  = $data['send_notifaction_to'];
			$headers['Subject']  = "Import Completed";
			// Create new email object
			$email = new Email();
			$status = $email->send($headers['To'], $headers, $message);
			unset($email);
		}

		return $imp->numImported;
	}

	/**
	 * Record update into sync stat table for all listening devices
	 *
	 * @param {WorkerJob} $job A handle to the current worker job
	 * @return {bool} true on success, false on failure
	 */
	function object_sync_stat($job)
	{
		$data = unserialize($job->workload());
		$dbh = $job->dbh;

		if (!$data['obj_type'])
			return false;

		$sync = new AntObjectSync($dbh, $data['obj_type']);
		if (isset($data['debug']))
			$sync->debug = true;
		if (isset($data['skipcoll']))
			$sync->ignoreCollection = $data['skipcoll'];
		if (!isset($data['revision']))
			$data['revision'] = null;
		if (!isset($data['parent_id']))
			$data['parent_id'] = null;

		if ($data['field_name'] && $data['field_val'])
			$colUpdated = $sync->updateGroupingStat($data['field_name'], $data['field_val'], $data['action']);
		else
			$colUpdated = $sync->updateObjectStat($data['oid'], $data['action'], $data['revision'], $data['parent_id']);

		return count($colUpdated);
	}
?>
