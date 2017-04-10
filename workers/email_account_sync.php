<?php
/**
* These worker functions handle email account synchornization
*/
require_once('lib/AntConfig.php'); 
require_once('lib/AntLog.php'); 
require_once('lib/AntUser.php'); 
require_once('lib/Worker.php'); 
require_once('lib/aereus.lib.php/CCache.php'); 
require_once('lib/AntMail/Account.php'); 
require_once('lib/AntMail/Sync.php'); 

/**
* Add functions to list of callbacks
*/
if (is_array($g_workerFunctions))
{
    $g_workerFunctions["email/account_sync"] = "email_account_sync";
    $g_workerFunctions["email/account_sync_mailbox"] = "email_account_sync_mailbox";
}

/**
* Syncs the email status/action to the imap server
*
* @param {WorkerJob} $job A handle to the current worker job
* @return {bool} true on success, false on failure
*/
function email_account_sync($job)
{
    $data = unserialize($job->workload());
    $dbh = $job->dbh;
    
    // Verify that the required data is set
    if (!is_numeric($data['user_id']) || !is_numeric($data['object_id']))
        return false;

    // Create user
    $user = new AntUser($dbh, $data['user_id']);
    
    // Instantiate Object Class
    $emailMessageObj = CAntObject::factory($dbh, "email_message", $data['object_id'], $user);
    
    $emailMessageObj->testMode = $data['test_mode'];    
    $emailMessageObj->processUpsync($data);
    
    return true;
}

/**
 * Sync messages for a mailbox/grouping id
 *
 * @param {WorkerJob} $job A handle to the current worker job
 * @return {bool} true on success, false on failure
 */
function email_account_sync_mailbox($job)
{
    $data = unserialize($job->workload());
    $dbh = $job->dbh;
    
    // Verify that the required data is set
    if (!is_numeric($data['user_id']) || !is_numeric($data['mailbox_id']))
        return false;

	$ret = array();

	// Get last updated - limit sync intervals to scale better
	$cache = CCache::getInstance();
	$lastUpdate = $cache->get($dbh->dbname . "/email/sync/" . $data['user_id'] . "/" . $data['mailbox_id']);
	if ($lastUpdate > time() - 30) // maximum update every 30 seconds
		return true;

    // Create user
    $user = new AntUser($dbh, $data['user_id']);
	
	// Get email accounts (filter if account is in data)
	$params = array();
	if ($data['email_account'])
		$params['id'] = $data['email_account'];

	$accounts = $user->getEmailAccounts($params);
	foreach ($accounts as $account)
	{
		if (!$account->type || !$account->host)
			continue;

		$ret[] = $account->id;

		// Sync mailbox
		$sync = new AntMail_Sync($dbh, $user);

		try
		{
			$sync->syncMailbox($data['mailbox_id'], $account);
		}
		catch (Exception $e)
		{
			$context = "Worker::email_account_sync";
			AntLog::getInstance()->error("$context - Exception when trying to sync: " . $e->getMessage());
		}

        /*
         * This option is currently disabled until we make sure that
         * we have resolved an issue with it creating duplicate mailboxes.
         * For now we will just sync the inbox.
         *
		// Check last full sync and update every 30 minutes - 60 * 30
		if ($account->tsLastFullSync==null || $account->tsLastFullSync > (time() - 60*30))
			$sync->syncMailboxes($account);
        */
	}

	$lastUpdate = $cache->set($dbh->dbname . "/email/sync/" . $data['user_id'] . "/" . $data['mailbox_id'], time());
    
    return serialize($ret);
}
