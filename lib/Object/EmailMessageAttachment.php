<?php
/**
 * Aereus Object Email Attachment
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * added functionality like deleting attachments from AntFs
 *
 * @category	CAntObject
 * @package		EmailMessage
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntFs.php");

/**
 * Object extensions for managing emails in ANT
 */
class CAntObject_EmailMessageAttachment extends CAntObject
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
		parent::__construct($dbh, "email_message_attachment", $id, $user);
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
		if ($this->getValue("file_id"))
		{
			$antfs = new AntFs($this->dbh, $this->user);
			$file = $antfs->openFileById($this->getValue("file_id"));

			if ($file)
			{
				if ($hard)
					$file->removeHard();
				else
					$file->remove();
			}
		}
	}

	/**
	 * Unremove this attachment
	 */
	protected function unremoved()
	{
		if ($this->getValue("file_id"))
		{
			$antfs = new AntFs($this->dbh, $this->user);
			$file = $antfs->openFileById($this->getValue("file_id"));

			if ($file)
				$file->unremove();
		}
	}
}
