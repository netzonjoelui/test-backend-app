<?php
/**
 * ANT Service that runs all the time and pulls raw email message from the queue and puts them into ANT
 *
 * @category	AntService
 * @package		EmailQueueProcess
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/CAntObjectList.php");		
require_once("lib/AntService.php");
require_once("lib/AntRoutine.php");
require_once("lib/Object/Reminder.php");

class AntService_Reminders extends AntRoutine
{
	public function main(&$dbh)
	{
		$olist = new CAntObjectList($dbh, "reminder");
		$olist->addCondition("and", "ts_execute", "is_less", "now");
		$olist->addCondition("and", "f_executed", "is_not_equal", 't');
		$olist->getObjects();

		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$rem = $olist->getObject($i);
			$rem->execute();

			echo "Sent " . $rem->getValue("type");
			if ($rem->getValue("send_to"))
				echo " to " . $rem->getValue("send_to") . "\n";
			else
				echo " popup\n";
		}
	}
}
