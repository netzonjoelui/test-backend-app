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
require_once("lib/System/IncomingMail.php");

class AntRoutine_SystemIncomingMail extends AntRoutine
{
    /**
     * We only need to run this for system
     */
    public $perAccount = false;

    /**
     * Main routine called by service manager
     */
	public function main(&$dbh)
	{
        $sysim = new System_IncomingMail();
        $imported = $sysim->processInbox(); 
        $sysim->cleanupInbox();
	}
}
