<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/CPageShellPublic.php");
require_once("lib/Object/Invoice.php");

$objType = $_REQUEST['obj_type'];	
$formId = $_REQUEST['form_id'];

if (empty($objType))
	die("No object type defined. Aborting.");

$page = new CPageShellPublic("Submit Form", "home");
$page->opts['print_subnav'] = false;
$page->PrintShell();

if ($_REQUEST['save'])
{
	$obj = CAntObject::factory($ANT->dbh, $objType);
	$obj->setValue("first_name", $_REQUEST['first_name']);
	$obj->setValue('last_name', $_REQUEST['last_name']);
	$obj->setValue('phone', $_REQUEST['phone']);
	$obj->setValue('email', $_REQUEST['email']);
	$obj->setValue('street', $_REQUEST['street']);
	$obj->setValue('street2', $_REQUEST['street2']);
	$obj->setValue('city', $_REQUEST['city']);
	$obj->setValue('state', $_REQUEST['state']);
	$obj->setValue('zip', $_REQUEST['zip']);
	$obj->setValue('notes', $_REQUEST['notes']);
	if ($_REQUEST['source_id']) // 28
		$obj->setValue("source_id", $_REQUEST['source_id']);
	if ($_REQUEST['campaign_id']) // 28
		$obj->setValue("campaign_id", $_REQUEST['campaign_id']);
	$obj->save();

	echo "Thank you for your interest! If appropriate, we will contact you as soon as possible.";
}
else
{
	// action=’https://aesrenew.netric.com/public/webforms/lead/1691094”
	?>
	<form name="netric_lead" method="POST">
		<input type="hidden" name="auth_key" value="e9eb5fa69afa9e4cdc1395ab7ea7171f" />
		<label>First Name</label> <input type="text" name="first_name" /> <br />
		<label>Last Name</label> <input type="text" name="last_name" /> <br />
		<label>Phone</label> <input type="text" name=”phone” /> <br />
		<label>Email</label> <input type="text" name="email" /><br />
		<label>Street</label> <input type="text" name="street" /><br />
		<label>Street 2</label> <input type="text" name="street2" /><br />
		<label>City</label> <input type="text" name="city" /><br />
		<label>State</label> <input type="text" name="state" /><br />
		<label>Zipcode</label> <input type="text" name="zip" /><br />
		<label>Comments/Notes</label><br />
		<textarea name="notes"></textarea><br />
		<input type="submit" name="save" value="Submit" />
	</form>
	<?php

}