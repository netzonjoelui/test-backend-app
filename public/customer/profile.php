<?php
/**
 * Customer profile home
 */
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/CPageShellPublic.php");
require_once("lib/Object/Invoice.php");

$caseId = $_REQUEST['cid'];	
$key = $_REQUEST['key']; // Key is the customer id

$page = new CPageShellPublic("View Case", "home");
$page->opts['print_subnav'] = false;
$page->PrintShell();

$case = CAntObject::factory($ANT->dbh, "case", $caseId);
$cust  = CAntObject::factory($ANT->dbh, "customer", $case->getValue("customer_id"));

// Handle comment submission
// ---------------------------------------------------------------------------------
if ($_POST['comment'])
{
	$obja = CAntObject::factory($ANT->dbh, "comment", null);
	$obja->setValue("obj_reference", "case:".$caseId);
	$obja->setValue("project_id", $case->getValue("project_id"));
	$obja->setValue("owner_id", null);
	$obja->setValue("comment", $_POST['comment']);
	$obja->setValue("sent_by", "customer:" . $cust->id);
	// TODO: we need some sort of fallback if no owner is assigned
	if ($case->getValue("owner_id"))
		$obja->setValue("notify", "user:" . $case->getValue("owner_id"));
	$obja->setMValue("associations", "case:$caseId");
	$cid = $obja->save();
}

if ($displaySuccessNotice)
{
	echo "<div class='g12 success'>$displaySuccessNotice</div>";
}

if ($displayErrorNotice)
{
	echo "<div class='g12 error'>$displayErrorNotice</div>";
}

// Case title and details
// --------------------------------------------------------
echo "<div class='g12'>";
echo "	<h1>" . $case->getValue("title") . "</h1>";
echo "</div>";

// Status
echo "<div class='g4'><strong>Status</strong>: " . $case->getForeignValue("status_id") . "</div>";

// Time created
echo "<div class='g4'><strong>Created</strong>: " . $case->getValue("ts_entered") . "</div>";

// Something else
echo "<div class='g4'><strong>Owner</strong>: " . $case->getForeignValue("owner_id") . "</div>";

echo "<div class='g12 hspacer2Hr'></div>";

// Description
// --------------------------------------------------------
echo "<div class='g3'><strong>" . $case->getValue("created_by") . "</strong>
		<br />" . $case->getValue("ts_entered") . "
	 </div>";

echo "<div class='g9'>" . str_replace("\n", "<br />", $case->getValue("description")) . "</div>";

echo "<div class='g12 hspacer2Hr'></div>";

// Comment History
// --------------------------------------------------------
$olist = new CAntObjectList($ANT->dbh, "comment");
$olist->addCondition("and", "obj_reference", "is_equal", "case:$caseId");
$olist->addOrderBy("ts_entered", "asc");
$olist->getObjects();
$num2 = $olist->getNumObjects();
for ($m = 0; $m < $num2; $m++)
{
	$obj = $olist->getObject($m);
	$sent_by = $obj->getValue("created_by");
	if ($sent_by == "customer:" . $cust->id)
		$sent_by = "Me";
	else
		$sent_by = $obj->getForeignValue("sent_by", $sent_by);

	// Display name
	echo "<div class='g3'>";
	echo "<strong>" . $sent_by . "</strong><br />";
	echo $obj->getValue("ts_entered");
	echo "</div>";

	// Display comment
	echo "<div class='g9'>";
	echo str_replace("\n", "<br />", $obj->getValue("comment"));
	echo "</div>";

	echo "<div class='g12 hspacer2Hr'></div>";
}

// Add new comment
// --------------------------------------------------------
echo "<div class='g3 tr'>&nbsp;</div>";

echo "<div class='g9'>";

echo "<form name='frm_comment' method='post' action='/public/support/case/$caseId/$key'>";
echo "<textarea name='comment' style='width:98%;height:50px;'></textarea>";
echo "<button name='add_comment'>Add Comment</button>";

echo "</form>";
echo "</div>";
