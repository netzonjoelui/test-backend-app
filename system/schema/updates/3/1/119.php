<?php
/**
 * Add outgoing columns to email accounts
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;

// Add outgoing columns
$dbh->Query("ALTER TABLE email_accounts ADD COLUMN host_out character varying(256);");
$dbh->Query("ALTER TABLE email_accounts ADD COLUMN port_out character varying(8);");
$dbh->Query("ALTER TABLE email_accounts ADD COLUMN f_ssl_out boolean DEFAULT false;");
$dbh->Query("ALTER TABLE email_accounts ADD COLUMN username_out character varying(256);");
$dbh->Query("ALTER TABLE email_accounts ADD COLUMN password_out character varying(256);");

// Add f_ssl setting if it is missing - was missing from the create script
if (!$dbh->ColumnExists("email_accounts", "f_ssl"))
    $dbh->Query("ALTER TABLE email_accounts ADD COLUMN f_ssl boolean DEFAULT false;");

// Fix system flags - previous update did not do this correctly
$dbh->Query("UPDATE email_accounts SET f_system='t' WHERE type is not NULL and type!='';");
