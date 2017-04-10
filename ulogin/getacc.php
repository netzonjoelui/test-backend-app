<?php    
/**
 * Get account informaiton from an email address
 *
 * @author joe <sky.stebnicki@aereus.com>
 */
require_once("../lib/AntConfig.php");
require_once("lib/CDatabase.awp");
require_once("lib/AntSystem.php");

$ret = array("account"=>"", "username"=>"");

$eml = $_REQUEST['email'];
if ($eml)
{
	// Get the account and username from AntSystem
	$sys = new AntSystem();
	$ret = $sys->getAccountFromEmail($eml);
}

// Set response
echo json_encode($ret);
