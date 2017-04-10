<?php 
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/CPageShellPublic.php");
require_once("lib/Object/Invoice.php");

$invid = $_REQUEST['invid'];	

$page = new CPageShellPublic("View Invoice", "home");
$page->opts['print_subnav'] = false;
$page->PrintShell();

$inv = new CAntObject_Invoice($ANT->dbh, $invid);
$cust  = new CAntObject($ANT->dbh, "customer", $inv->getValue("customer_id"));

$paidStatus = $inv->getGroupingEntryByName("status_id", "Paid");
$paid = ($inv->getValue("status_id") == $paidStatus['id']) ? true : false;

// Handle online payment
// ---------------------------------------------------------------
if ($_POST['pay'] == '1')
{
	// Get gateway
	$gw = PaymentGatewayManager::getGateway($ANT->dbh); // Force test type

	// Set fake credit card info
	$cardData = array(
		"number" => $_POST['cc_number'],
		"exp_month" => $_POST['cc_exp_month'],
		"exp_year" => $_POST['cc_exp_year'],
	);

	if ($_POST['street2'])
		$_POST['street'] . "\n" . $_POST['street2'];

	// Set customer data
	$custData = array(
		"first_name" => $_POST['first_name'],
		"last_name" => $_POST['last_name'],
		"street" => $_POST['street'],
		"city" => $_POST['city'],
		"state" => $_POST['state'],
		"zip" => $_POST['zip']
	);
	
	$ret = $inv->payWithCard($gw, $cardData, $custData, $paidStatus['id']);

	if ($ret)
	{
		$displaySuccessNotice = "<strong>Payment was successful!</strong> ";
		$displaySuccessNotice .= "Your confirmation number is <strong>" . $gw->respTransId . "</strong>. ";
		$displaySuccessNotice .= "Thank you for your business!";
		$paid = true;
	}
	else
	{
		$displayErrorNotice = "ERROR: " . $gw->respReason;
	}
}

// Get template information
if ($inv->getValue('template_id'))
{
	$temp = new CAntObject($ANT->dbh, "invoice_template", $inv->getValue('template_id'));
	$g_company_logo = $temp->getValue("company_logo");
	$g_company = $temp->getValue("company_name");
	$g_slogan = $temp->getValue("company_slogan");
	$g_notes_line1 = $temp->getValue("notes_line1");
	$g_notes_line2 = $temp->getValue("notes_line2");
	$g_footer_line1 = $temp->getValue("footer_line1");
}
else
{
	$g_company = $ANT->settingsGet("general/company_name");
	$g_slogan = "";
	$g_notes_line1 = "Please make all checks payable to $g_company";
	$g_notes_line2 = "Thank you for your business!";
	$g_footer_line1 = "";
}

if ($displaySuccessNotice)
{
	echo "<div class='g12 success'>$displaySuccessNotice</div>";
}

if ($displayErrorNotice)
{
	echo "<div class='g12 error'>$displayErrorNotice</div>";
}

// Sent to customer information
echo "<div class='g9'>";
echo "	<h3 class='mgb0'>" . $cust->getName() . "</h3>";

if ($cust->getValue("billing_street"))
	echo "	<h5 class='mgb0'>" . $cust->getValue("billing_street") . "</h5>";

if ($cust->getValue("billing_city"))
{
	echo "	<h5 class='mgb0'>";
	echo $cust->getValue("billing_city") . ", " . $cust->getValue("billing_state") . ", " . $cust->getValue("billing_zip");
	echo "</h5>";
}

echo "</div>";

// Invoice Information
// ----------------------------------------------------------
echo "<div class='g3'>";
echo "<table cellpadding='0' cellspacing='0'>";
echo "<tr><td class='b'>Invoice #:</td><td>INV-" . $inv->id . "</td></tr>";
if ($inv->getValue("ts_entered"))
	echo "<tr><td class='b'>Date:</td><td>" . date("m/d/Y", strtotime($inv->getValue("ts_entered"))) . "</td></tr>";
echo "<tr><td class='b'>Customer #:</td><td>" . $cust->id . "</td></tr>";
echo "<tr><td class='b'>Status:</td><td>" . $inv->getForeignValue("status_id") . "</td></tr>";
echo "</table>";
echo "</div>";

// Spacer
// ----------------------------------------------------------
echo "<div class='g12 mgb2'></div>";

// Salesperson and terms
// ----------------------------------------------------------
echo "<div class='g12 mgb2'>";
echo "<table class='data' style='width:100%;'>";
echo "	<tr>
			<th>Salesperson</th>
			<th>For</th>
			<th>Payment Terms</th>
			<th>Due Date</th>
		</tr>";
echo "	<tr>
			<td>" . $inv->getForeignValue("owner_id") . "</td>
			<td>" . $inv->getValue("name") . "</td>
			<td>" . $inv->getValue("payment_terms") . "</td>
			<td>" . $inv->getValue("date_due") . "</td>
		</tr>";
echo "</table>";
echo "</div>";

// Spacer
// ----------------------------------------------------------
echo "<div class='g12 mgb2'></div>";

// Details
// ----------------------------------------------------------
echo "<div class='g12'>";
echo "<table class='data' style='width:100%;'>";
echo "	<tr>
			<th style='width: 50px;'>QUANTITY</th>
			<th>DESCRIPTION</th>
			<th style='width: 100px;'>UNIT PRICE</th>
			<th style='width: 100px;'>LINE TOTAL</th>
		</tr>";
for ($i = 0; $i < $inv->getNumItems(); $i++)
{
	$item = $inv->getItem($i);

	// skip if null
	if ($item == null)
		continue;

	echo "<tr>";
	echo "	<td class='tc'>" . $item->quantity . "</td>";
	echo "	<td class='tl'>" . $item->name . "</td>";
	echo "	<td class='tr'>" . $ANT->formatText($item->amount, "money") . "</td>";
	echo "	<td class='tr'>" . $ANT->formatText($item->amount * $item->quantity, "money") . "</td>";
	echo "</tr>";
}
echo "</table>";

// Print subtotals and totals
// ----------------------------------------------------------
echo "<table style='width:100%;'>";

// Subtotal
echo "<tr>";
echo "	<td></td><td></td>"; // spacer
echo "	<td class='b tr'>Subtotal:</td>";
echo "	<td class='tr' style='width:105px;padding-right: 3px;'>" . $ANT->formatText($inv->getSubtotal(), "money") . "</td>";
echo "</tr>";

// Taxes
echo "<tr>";
echo "	<td></td><td></td>"; // spacer
echo "	<td class='b tr'>Taxes:</td>";
echo "	<td class='tr' style='width:105px;padding-right: 3px;'>" . $ANT->formatText($inv->getTaxesTotal(), "money") . "</td>";
echo "</tr>";

// Total
echo "<tr>";
echo "	<td></td><td></td>"; // spacer
echo "	<td class='b tr'>Total:</td>";
echo "	<td class='tr' style='width:105px;padding-right: 3px;'>" . $ANT->formatText($inv->getTotal(), "money") . "</td>";
echo "</tr>";

echo "</table>";
echo "</div>";

// Add payment option
if (PaymentGatewayManager::hasGateway($ANT->dbh) && !$paid)
{
	echo "<div class='g12'>";

	echo "<form name='payonline' method='post' action='/public/sales/invoice/" . $_REQUEST['invid'] . "'>";
	echo "<fieldset>";
	echo "<legend>Pay Online</legend>";

	echo "<table>";

	echo "<tr><th colspan='2'>Credit Card</th><th colspan='2'>Billing Address</th></tr>";

	echo "<tr>";
	echo "<td>First Name:</td>";
	echo "<td><input type='text' name='first_name' value=\" " . $cust->getValue("first_name") . "\" /></td>";

	echo "<td>Street:</td>";
	echo "<td><input type='text' name='street' value=\" " . $cust->getValue("billing_street") . "\" /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>Last Name:</td>";
	echo "<td><input type='text' name='first_name' value=\" " . $cust->getValue("last_name") . "\"></td>";

	echo "<td>Street 2:</td>";
	echo "<td><input type='text' name='street2' value=\" " . $cust->getValue("billing_street2") . "\" /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>Credit Card Number:</td>";
	echo "<td><input type='text' name='cc_number' /></td>";

	echo "<td>City:</td>";
	echo "<td><input type='text' name='city' value=\" " . $cust->getValue("billing_city") . "\" /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td>Expires (mm/yyyy):</td>";

	echo "<td>";

	echo "<select name='cc_exp_month'>";
	for ($i = 1; $i <= 12; $i++)
	{
		$val= ($i < 10) ? "0" . $i : $i;

		echo "<option value='$val'>$i</option>";
	}
	echo "</select>";

	echo "<select name='cc_exp_year'>";
	for ($i = date("Y"); $i <= date("Y")+20; $i++)
	{
		echo "<option value='$i'>$i</option>";
	}
	echo "</select>";
	echo "</td>";

	echo "<td>State:</td>";
	echo "<td><input type='text' name='state' value=\" " . $cust->getValue("billing_state") . "\" /></td>";

	echo "</tr>";

	echo "<tr>";
	echo "<td>CCV:</td>";
	echo "<td><input type='text' name='cc_ccv' /></td>";

	echo "<td>Zipcode:</td>";
	echo "<td><input type='text' name='zip' value=\" " . $cust->getValue("billing_zip") . "\" /></td>";
	echo "</tr>";

	echo "</table>";

	echo "<button type='submit' name='pay' value='1'>Pay Invoice</button>";

	echo "</fieldset>";
	echo "</form>";

	echo "</div>";
}
