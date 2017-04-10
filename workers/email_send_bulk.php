<?php
/**
 * @fileoverview This worker handles sending all messages via smtp
 */
require_once('lib/Worker.php'); 

/**
 * Add functions to list of callbacks
 */
if (is_array($g_workerFunctions))
{
	$g_workerFunctions["email/send_bulk"] = "email_send_bulk";
}

/**
 * Send a bulk email message to customers
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function email_send_bulk($job)
{
	$data = unserialize($job->workload());
	$dbh = $job->dbh;

	// Verify that the required data is set
	if (!is_numeric($data['user_id']) && !is_numeric($data['object_id']))
		return false;

	// Create user
	$user = new AntUser($dbh, $data['user_id']);
    
	$ALIB_CACHE_DISABLE = true; // disable caching
    
    // Instantiate Campaign Object
    $campaignObj = CAntObject::factory($dbh, "email_campaign", $data['object_id'], $user);
    
    if(isset($data['test_mode']) && $data['test_mode']==1)
        $campaignObj->testMode = true;
        
    $result = $campaignObj->processEmailCampaign();
	
	return $result;
}
