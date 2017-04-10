<?php
/**
 * Process and index incoming email queue
 *
 * New messages are placed into a queue table (email_message_queue) and the raw
 * text of the message is stored in a large object. This service takes the messages
 * from the queue and processes (delivers) them into messages in ANT.
 *
 * @category	Ant
 * @package		Email
 * @subpackage	Queue_Process
 * @copyright	Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */
require_once("../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/AntService.php");
require_once("services/EmailQueueProcess.php");

ini_set("memory_limit", "-1");	

$svc = new EmailQueueProcess();
while($svc->run())
{
	sleep(120); // Run every 2 minutes
}

?>
