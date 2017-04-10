<?php
/**
 * This update sets all users in local db with associated email addresses into the antsystem.account_users table
 * for universal login via email address and redirection to the correct email address.
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR); // will make sure user def is updated with email column

$results = $dbh->Query("SELECT id, email FROM users WHERE id >'0' AND (email is NULL OR email='')");
$num = $dbh->GetNumberRows($results);
for ($i = 0; $i < $num; $i++)
{
	$uid = $dbh->GetValue($results, $i, "id");
	$emaildb = $dbh->GetValue($results, $i, "email");

	$usr = $ant->getUser($uid);
	$eml = $usr->getEmail(); // this will set the email field in the user object
	if ($emaildb == "" || $emaildb == null)
	{
		$usr->setValue("email", $eml);
		$usr->save();
		echo "\tSet {$usr->name} to $eml\n";
	}
}
