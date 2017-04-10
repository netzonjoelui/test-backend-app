<?php
/**
 * Extend queries made to email_message object types
 *
 * @category  	AntObjectList_Plugin
 * @package   	EmailMessage
 * @author 		joe <sky.stebnicki@aereus.com>
 * @copyright 	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntMail/Sync.php");
require_once("lib/WorkerMan.php");

/**
 * EmailMessage plugin
 */
class AntObjectList_Plugin_EmailMessage extends AntObjectList_Plugin
{
	/**
	 * Job id used to reflect the background job id if launched
	 *
	 * @var int
	 */
	public $jobId = null;

	/**
	 * Called just before a query is executed in the object list
	 *
	 * This function checks email accounts and polls the backend server for new messages
	 * at certain intervals.
	 */
	public function onQueryObjectsBefore()
	{
        // Do no execute worker if object list is called from sync function. It will trigger an infinite loop
        if($this->objectList->fromSync)
            return true;
        
		// Check if we are getting messages for a specific mailbox, if not then
		// we don't need to bother checking the backend servers
		$mailboxId = null;
		$accountId = null;
		foreach ($this->objectList->conditions as $cond)
		{
			if ($cond['field'] == "mailbox_id" && is_numeric($cond['value']))
				$mailboxId = $cond['value'];

			if ($cond['field'] == "email_account" && is_numeric($cond['value']))
				$accountId = $cond['value'];
		}

		if (!$mailboxId)
			return true; // Skip

		// Get last updated - limit sync intervals to scale better
		$cache = CCache::getInstance();
		$lastUpdate = $cache->get($this->objectList->dbh->dbname . "/email/sync/" . $this->objectList->user->id . "/" . $mailboxId);
		if ($lastUpdate > time() - 30) // maximum update every 30 seconds
			return;

		// Add background job
		$data = array(
			"user_id" => $this->objectList->user->id, 
			"mailbox_id" => $mailboxId,
			"email_account" => $accountId,
		);
		$wman = new WorkerMan($this->objectList->dbh);
		$this->jobId = $wman->runBackground("email/account_sync_mailbox", serialize($data));

		/*
		// Get email accounts (filter if account is in query)
		$params = array();
		if ($accountId)
			$params['id'] = $accountId;
		$accounts = $this->objectList->user->getEmailAccounts($params);

		foreach ($accounts as $account)
		{
			if (!$account->type || !$account->host)
				continue;

			$this->accountsProcessed[] = $account->id;

			// Sync mailbox
			$sync = new AntMail_Sync($this->objectList->dbh, $this->objectList->user);
			try
			{
				$sync->syncMailbox($mailboxId, $account);
			}
			catch (Exception $e)
			{
				$context = "AntObjectList_Plugin_EmailMessage::onQueryObjectsBefore";
				AntLog::getInstance()->error("$context - Exception when trying to sync: " . $e->getMessage());
			}
		}
		*/
	}
}
