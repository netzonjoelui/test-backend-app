<?php
/**
 * This file is used to create default groupings for objects if not already set
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CAntObject.php");
require_once("lib/CDatabase.awp");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");

if (!$ant)
	die("This script must be called from the system schema manager and ant mut be set");

$dbh = $ant->dbh;
$user = new AntUser($dbh, USER_ADMINISTRATOR);

/**
 * Cases
 * --------------------------------------------------------------------------
 */
$obj = new CAntObject($dbh, "case", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "New", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "In-Progress", "FF9233", 3);
	$obj->addGroupingEntry("status_id", "Closed: Resolved", "1D6914", 5);
	$obj->addGroupingEntry("status_id", "Closed: Unresolved", "AD2323", 7);
}

// severity_id
if (0==count($obj->getGroupingData("severity_id")))
{
	$obj->addGroupingEntry("severity_id", "Low", "1D6914", 1);
	$obj->addGroupingEntry("severity_id", "Medium", "575757", 2);
	$obj->addGroupingEntry("severity_id", "High", "AD2323", 3);
}

// type_id
if (0==count($obj->getGroupingData("type_id")))
{
	$obj->addGroupingEntry("type_id", "Customer Support", "1D6914", 1);
	$obj->addGroupingEntry("type_id", "Technical Support", "575757", 2);
}

/**
 * Project Stories
 * --------------------------------------------------------------------------
 */
$obj = new CAntObject($dbh, "project_story", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "New", "2A4BD7", 1);
	//$obj->addGroupingEntry("status_id", "Approved", "575757", 2);
	$obj->addGroupingEntry("status_id", "In-Progress", "FF9233", 3);
	$obj->addGroupingEntry("status_id", "Ready for testing", "FFEE33", 4);
	$obj->addGroupingEntry("status_id", "Test Passed", "575757", 5);
	$obj->addGroupingEntry("status_id", "Test Failed", "29D0D0", 6);
	$obj->addGroupingEntry("status_id", "Completed", "1D6914", 7);
	$obj->addGroupingEntry("status_id", "Rejected", "AD2323", 8);
}

// priority_id
if (0==count($obj->getGroupingData("priority_id")))
{
	$obj->addGroupingEntry("priority_id", "Low", "1D6914", 1);
	$obj->addGroupingEntry("priority_id", "Medium", "575757", 2);
	$obj->addGroupingEntry("priority_id", "High", "AD2323", 3);
}

// priority_id
if (0==count($obj->getGroupingData("type_id")))
{
	$obj->addGroupingEntry("type_id", "Enhancement", "1D6914", 1);
	$obj->addGroupingEntry("type_id", "Defect", "AD2323", 1);
}

/**
 * Campaigns
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "marketing_campaign", null, $user);

// type_id
if (0==count($obj->getGroupingData("type_id")))
{
	$obj->addGroupingEntry("type_id", "Email", "2A4BD7", 1);
	$obj->addGroupingEntry("type_id", "Advertisement", "575757", 2);
	$obj->addGroupingEntry("type_id", "Telephone", "FF9233", 3);
	$obj->addGroupingEntry("type_id", "Banner Ads", "FFEE33", 4);
	$obj->addGroupingEntry("type_id", "Public Relations", "1D6914", 5);
	$obj->addGroupingEntry("type_id", "Partners", "AD2323", 6);
	$obj->addGroupingEntry("type_id", "Resellers", "A0A0A0", 7);
	$obj->addGroupingEntry("type_id", "Referral Program", "814A19", 8);
	$obj->addGroupingEntry("type_id", "Direct Mail", "8126C0", 9);
	$obj->addGroupingEntry("type_id", "Trade Show", "9DAFFF", 10);
	$obj->addGroupingEntry("type_id", "Conference", "E9DEBB", 11);
	$obj->addGroupingEntry("type_id", "Other", "29D0D0", 12);
}

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "Planning", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "Active", "575757", 2);
	$obj->addGroupingEntry("status_id", "Inactive", "FF9233", 3);
	$obj->addGroupingEntry("status_id", "Complete", "FFEE33", 4);
}


/**
 * CMS Post
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "content_feed_post", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "Draft", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "Awaiting Review", "575757", 3);
	$obj->addGroupingEntry("status_id", "Rejected", "FF9233", 5);
	$obj->addGroupingEntry("status_id", "Published", "FFEE33", 7);
}

/**
 * CMS Page
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "cms_page", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "Draft", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "Awaiting Review", "575757", 3);
	$obj->addGroupingEntry("status_id", "Rejected", "FF9233", 5);
	$obj->addGroupingEntry("status_id", "Published", "FFEE33", 7);
}

/**
 * Activity
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "activity", null, $user);

// type_id - verify the required types exist
$obj->addGroupingEntry("type_id", "Phone Call", "2A4BD7", 1);
$obj->addGroupingEntry("type_id", "Status Update", "575757", 2);

/**
 * Phone Call
 * --------------------------------------------------------------------------
 */
$obj = new CAntObject($dbh, "phone_call", null, $user);

// status_id
if (0==count($obj->getGroupingData("purpose_id")))
{
	$obj->addGroupingEntry("purpose_id", "Prospecting", "2A4BD7", 1);
	$obj->addGroupingEntry("purpose_id", "Administrative", "FF9233", 3);
	$obj->addGroupingEntry("purpose_id", "Negotiation", "1D6914", 5);
	$obj->addGroupingEntry("purpose_id", "Demo", "AD2323", 7);
	$obj->addGroupingEntry("purpose_id", "Project", "1D6914", 9);
	$obj->addGroupingEntry("purpose_id", "Support", "AD2323", 11);
}

/**
 * Lead
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "lead", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "New: Not Contacted", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "New: Pre Qualified", "9DAFFF", 2);
	$obj->addGroupingEntry("status_id", "Working: Attempted to Contact", "575757", 3);
	$obj->addGroupingEntry("status_id", "Working: Contacted", "FF9233", 4);
	$obj->addGroupingEntry("status_id", "Working: Contact Later", "FFEE33", 5);
	$obj->addGroupingEntry("status_id", "Closed: Converted", "1D6914", 6);
	$obj->addGroupingEntry("status_id", "Closed: Lost", "AD2323", 7);
	$obj->addGroupingEntry("status_id", "Closed: Junk", "29D0D0", 8);
}

// rating_id
if (0==count($obj->getGroupingData("rating_id")))
{
	$obj->addGroupingEntry("rating_id", "Hot", "2A4BD7", 1);
	$obj->addGroupingEntry("rating_id", "Medium", "575757", 3);
	$obj->addGroupingEntry("rating_id", "Cold", "FF9233", 5);
}

// source_id
if (0==count($obj->getGroupingData("source_id")))
{
	$obj->addGroupingEntry("source_id", "Advertisement", "2A4BD7", 1);
	$obj->addGroupingEntry("source_id", "Cold Call", "575757", 2);
	$obj->addGroupingEntry("source_id", "Employee Referral", "FF9233", 3);
	$obj->addGroupingEntry("source_id", "External Referral", "FFEE33", 4);
	$obj->addGroupingEntry("source_id", "Website", "1D6914", 5);
	$obj->addGroupingEntry("source_id", "Partner", "AD2323", 6);
	$obj->addGroupingEntry("source_id", "Email", "A0A0A0", 7);
	$obj->addGroupingEntry("source_id", "Web Research", "814A19", 8);
	$obj->addGroupingEntry("source_id", "Direct Mail", "8126C0", 9);
	$obj->addGroupingEntry("source_id", "Trade Show", "9DAFFF", 10);
	$obj->addGroupingEntry("source_id", "Conference", "E9DEBB", 11);
	$obj->addGroupingEntry("source_id", "Other", "29D0D0", 12);
}

/**
 * Meeting proposal status
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "calendar_event_proposal", null, $user);

// status_id
if (0==count($obj->getGroupingData("status_id")))
{
	$obj->addGroupingEntry("status_id", "Draft", "2A4BD7", 1);
	$obj->addGroupingEntry("status_id", "Sent: Awaiting Replies", "FF9233", 3);
	$obj->addGroupingEntry("status_id", "Completed", "1D6914", 5);
	$obj->addGroupingEntry("status_id", "Canceled", "AD2323", 7);
}

/**
 * Opportunities
 * --------------------------------------------------------------------------
 */

$obj = new CAntObject($dbh, "opportunity", null, $user);

// stage_id
if (0==count($obj->getGroupingData("stage_id")))
{
	$obj->addGroupingEntry("stage_id", "Qualification", "2A4BD7", 10);
	$obj->addGroupingEntry("stage_id", "Needs Analysis", "575757", 20);
	$obj->addGroupingEntry("stage_id", "Value Proposition", "FF9233", 30);
	$obj->addGroupingEntry("stage_id", "Id. Decision Makers", "FFEE33", 40);
	$obj->addGroupingEntry("stage_id", "Proposal/Price Quote", "1D6914", 50);
	$obj->addGroupingEntry("stage_id", "Negotiation/Review", "AD2323", 60);
	$obj->addGroupingEntry("stage_id", "Closed Won", "A0A0A0", 70);
	$obj->addGroupingEntry("stage_id", "Closed Lost", "814A19", 80);
	//$obj->addGroupingEntry("stage_id", "Closed Lost to Competition", "8126C0", 90);
}

// type_id
if (0==count($obj->getGroupingData("type_id")))
{
	$obj->addGroupingEntry("type_id", "New Business", "2A4BD7", 10);
	$obj->addGroupingEntry("type_id", "Existing Busienss", "575757", 20);
}

// objection_id
if (0==count($obj->getGroupingData("objection_id")))
{
	$obj->addGroupingEntry("objection_id", "Not Interested / Don't need it", "2A4BD7", 10);
	$obj->addGroupingEntry("objection_id", "Already Working with Someone", "575757", 20);
	$obj->addGroupingEntry("objection_id", "Trouble Getting Approved", "FF9233", 30);
	$obj->addGroupingEntry("objection_id", "Price Too High", "FFEE33", 40);
	$obj->addGroupingEntry("objection_id", "Troubling Reputation", "1D6914", 50);
	$obj->addGroupingEntry("objection_id", "Never Heard of Us", "AD2323", 60);
	$obj->addGroupingEntry("objection_id", "Had Problems in the Past", "A0A0A0", 70);
	$obj->addGroupingEntry("objection_id", "Too Confusing/Complex", "814A19", 80);
	$obj->addGroupingEntry("objection_id", "Not a Good Fit", "8126C0", 90);
	/*
	$obj->addGroupingEntry("objection_id", "Trade Show", "9DAFFF", 10);
	$obj->addGroupingEntry("objection_id", "Conference", "E9DEBB", 11);
	$obj->addGroupingEntry("objection_id", "Other", "29D0D0", 12);
	*/
}

// selling_point_id
if (0==count($obj->getGroupingData("selling_point_id")))
{
	$obj->addGroupingEntry("selling_point_id", "Price", "2A4BD7", 10);
	$obj->addGroupingEntry("selling_point_id", "Features", "575757", 20);
	$obj->addGroupingEntry("selling_point_id", "Good Reputation", "FF9233", 30);
	$obj->addGroupingEntry("selling_point_id", "Support", "FFEE33", 40);
	$obj->addGroupingEntry("selling_point_id", "Simplicity", "1D6914", 50);
	$obj->addGroupingEntry("selling_point_id", "Good Expeirence", "AD2323", 60);
	/*
	$obj->addGroupingEntry("selling_point_id", "", "A0A0A0", 7);
	$obj->addGroupingEntry("selling_point_id", "Web Research", "814A19", 8);
	$obj->addGroupingEntry("selling_point_id", "Direct Mail", "8126C0", 9);
	$obj->addGroupingEntry("selling_point_id", "Trade Show", "9DAFFF", 10);
	$obj->addGroupingEntry("selling_point_id", "Conference", "E9DEBB", 11);
	$obj->addGroupingEntry("selling_point_id", "Other", "29D0D0", 12);
	*/
}
