<?php
require_once("PEAR.php");
require_once('Mail.php');
require_once('Mail/mime.php');

require_once(dirname(__FILE__)."/../../lib/AntConfig.php");
require_once("lib/Ant.php");
require_once("lib/CAntFs.awp");
require_once("lib/AntUser.php");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/CAntObject.php");
require_once("lib/Email.php");
require_once("customer/CCustomer.php");
require_once("email/email_functions.awp");
require_once("lib/aereus.lib.php/CAnsClient.php");
require_once("lib/aereus.lib.php/CAntCustomer.php");
require_once("lib/aereus.lib.php/CAntOpportunity.php");
require_once("lib/WorkerMan.php");

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
ini_set('memory_limit','2G');

$worker = new Worker();
$worker->debug = true;
$worker->enableProfile = true;

while($worker->work())
{
    if ($worker->returnCode() != WORKER_SUCCESS)
    {
        echo "Result: " . $worker->returnCode() . "\n";
        break;
    }
}
