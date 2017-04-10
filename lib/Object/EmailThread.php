<?php
/**
 * Aereus Object - email thread
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * added functionality like removing all messages in this thread
 *
 * @category	CAntObject
 * @package		EmailMessage
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntFs.php");

/**
 * Object extensions for managing emails in ANT
 */
class CAntObject_EmailThread extends CAntObject
{
	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh An active handle to a database connection
	 * @param int $id The attachment id we are editing - this is optional
	 * @param AntUser $user Optional current user
	 */
	function __construct($dbh, $id=null, $user=null)
	{
		parent::__construct($dbh, "email_thread", $id, $user);
	}
	
	/**
	 * Function used for derrived classes to hook load event
	 */
	protected function loaded()
	{
	}

	/**
	 * Before we save set some require variables
	 */
	protected function beforesaved()
	{
	}

	/**
	 * Function used for derrived classes to hook save event
	 */
	protected function saved()
	{
	}

	/**
	 * Function used for derrived classes to hook deletion event
	 *
	 * @param bool $hard Set to true if this is a hard delete rather than just a soft or flagged deletion
	 */
	protected function removed($hard=false)
	{
		// This has been moved to Netric\Entity\ObjType\EmailThreadEntity

		/*
		// Select undeleted messages and delete
		$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
		$olist->addCondition('and', "thread", "is_equal", $this->id);
		$olist->getObjects();
		$a_num = $olist->getNumObjects();
		for ($m = 0; $m < $a_num; $m++)
		{
			$obj = $olist->getObject($m);
			$obj->skipProcessThread = true; // prevent endless loop where email message calls thread
			if ($hard)
				$obj->removeHard();
			else
				$obj->remove();
		}
		*/
	}

	/**
	 * Function used for derrived classes to hook undelete event
	 */
	protected function unremoved()
	{
		// This has been moved to Netric\Entity\ObjType\EmailThreadEntity

		/*
		// Select undeleted messages and delete
		$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
		$olist->addCondition('and', "thread", "is_equal", $this->id);
		$olist->addCondition('and', "f_deleted", "is_equal", 't');
		$olist->getObjects();
		$a_num = $olist->getNumObjects();
		for ($m = 0; $m < $a_num; $m++)
		{
			$obj = $olist->getObject($m);
			$obj->skipProcessThread = true; // prevent endless loop where email message calls thread
			$obj->unremove();
		}
		*/
	}

	/**
	 * Move this thread to another mailbox/group by id
	 *
	 * @param int $gid The group id to move this message to
	 * @param int $fromGid An optional group to remove (we are moving from this group to the new one)
	 * @return true on success
	 */
	public function move($gid, $fromGid=null)
	{
		// Check if we are moving to or from trash
		$trashGroup = $this->getGroupingEntryByName("mailbox_id", "Trash");
		if($gid == $trashGroup['id'] && $this->getValue("f_deleted")!='t')
		{
			$result = $this->remove();
            return $result;
		}

		$sentGroup = $this->getGroupingEntryByName("mailbox_id", "Sent");
		$wasinsent = $this->getMValueExists("mailbox_id", $sentGroup['id']);

		if ($fromGid)
		{
			// Remove this thread from old mailbox
			$this->removeMValue("mailbox_id", $fromGid);
		}
		else // remove from all other groups
		{
			$this->removeMValues("mailbox_id");
		}

		if ($wasinsent)
			$this->setMValue("mailbox_id", $sentGroup['id']);

		// Add mailbox/group to this thread
		$this->setMValue("mailbox_id", $gid);

		// Select individual messages in the same group and move them too
		$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
		$olist->addCondition('and', "thread", "is_equal", $this->id);
		if ($fromGid)
			$olist->addCondition('and', "mailbox_id", "is_equal", $fromGid); // Only move messages in the same group if possible
		$olist->getObjects();
		$a_num = $olist->getNumObjects();
		for ($m = 0; $m < $a_num; $m++)
		{
			$obj = $olist->getObject($m);
			$obj->setValue("mailbox_id", $gid);
			$obj->save();
		}

		// Undelete this message if it was previously deleted
		if ($fromGid && $fromGid == $trashGroup['id'] && $this->getValue("f_deleted")=='t')
		{
			$this->unremove();
		}

		return $this->save();
	}

	/**
	 * Flag a thread
	 *
	 * @param bool $on If true the flag is on, otherwise remove flag
	 */
	public function markFlag($on=true)
	{
		$this->setValue("f_flagged", ($on) ? 't' : 'f');
		return $this->save();
	}

	/**
	 * Flag a thread as spam
	 *
	 * @param bool $isspam If true then this thread is a spam thread, otherwise it is safe
	 */
	public function markSpam($isspam=true)
	{
		if ($this->id)
		{
			$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
			$olist->addCondition('and', "thread", "is_equal", $this->id);
			$olist->getObjects();
			for ($i = 0; $i < $olist->getNumObjects(); $i++)
			{
				$msg = $olist->getObject($i);
				$msg->skipProcessThread = true; // prevent loops
				$msg->markSpam($isspam);
			}
		}

		return true;
	}

	/**
	 * Mark the thread as read
	 *
	 * @param bool $isspam If true then this thread is a spam thread, otherwise it is safe
	 */
	public function markRead($read=true)
	{
		$this->setValue("f_seen", ($read) ? 't' : 'f');
		
		if ($this->id)
		{
			$olist = new CAntObjectList($this->dbh, "email_message", $this->user);
			$olist->addCondition('and', "thread", "is_equal", $this->id);
			$olist->getObjects();
			for ($i = 0; $i < $olist->getNumObjects(); $i++)
			{
				$msg = $olist->getObject($i);
				$msg->skipProcessThread = true; // prevent loops
				$msg->markRead($read);
			}
		}

		return $this->save();
	}
    
    /**
     * Parse and builds the email address lists
     *
     * @param bool $isspam If true then this thread is a spam thread, otherwise it is safe
     */
    public function updateSenders($emailAddress, $existingAddress)
    {
        $updatedEmailAddress = array();
        
        // Put sentFrom in the array first.
        $emailAddressParts = explode(",", $emailAddress);
        if(empty($existingAddress))
            $updatedEmailAddress = $emailAddressParts;
        else
        {
            $existingAddressParts = explode(",", $existingAddress);
            $updatedEmailAddress = array_merge($emailAddressParts, $existingAddressParts);
        }
            
        $updatedEmailAddress = array_unique($updatedEmailAddress);
        
        $result = implode(",", $updatedEmailAddress);
        return $result;
    }

	/**
	 * Return default list of mailboxes which is called by verifyDefaultGroupings in base class.
	 *
	 * @param string $fieldName the name of the grouping(fkey, fkey_multi) field 
	 * @return array
	 */
	public function getVerifyDefaultGroupingsData($fieldName)
	{
		$checkfor = array();

		if ($fieldName == "mailbox_id")
			$checkfor = array("Inbox" => "-100", "Trash" => "-90", "Junk Mail" => "-80", "Sent" => "-70");

		return $checkfor;
	}
}
