<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once('lib/pdf/class.ezpdf.php'); 
	require_once('lib/CAntObject.php'); 
	require_once("customer_functions.awp");
	
	ini_set("max_execution_time", "7200");	
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$TAX_RATE = ($_POST['tax_rate']) ? $_POST['tax_rate'] : 0;
				  
	// Get template information
	$g_company = $ANT->settingsGet("general/company_name");
	$g_slogan = "";
	$g_notes_line1 = "Please make all checks payable to $g_company";
	$g_notes_line2 = "Thank you for your business!";
	$g_footer_line1 = "";

	if ($_POST['ship_to'] && $_POST['ship_to_cship']!='t')
	{
		$send_to = $_POST['ship_to'];
	}
	else if ($_POST['customer_id'])
	{
		$obj = new CAntObject($dbh, "customer", $_POST['customer_id'], $USER);

		$send_to = $obj->getValue("name")."\n";
		if ($obj->getValue("shipping_street"))
		{
			$send_to .= $obj->getValue("shipping_street")."\n";
			$send_to .= $obj->getValue("shipping_city");
			$send_to .= $obj->getValue("shipping_state")." ";
			$send_to .= $obj->getValue("shipping_zip");
		}
		else if ($obj->getValue("street"))
		{
			$send_to .= $obj->getValue("street")."\n";
			$send_to .= $obj->getValue("city");
			$send_to .= $obj->getValue("state")." ";
			$send_to .= $obj->getValue("zip");
		}
	}

	$pdf =& new Cezpdf('LETTER');
	$pdf->selectFont('../lib/pdf/fonts/Helvetica.afm');
	$pdf->ezSetMargins(50,45,40,40);
	
	// Create Header
	/*
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);
	$pdf->line(40,805,550, 805);
	$pdf->addText(40,810,12, $title);
	$pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($all,'all');
	 */
	
	// Create Footer
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(.6,.6,.6,1);
	$pdf->setColor(.3,.3,.3, 1);
	$pdf->line(40,40,550, 40);
	$pdf->addText(40,30,8, $g_footer_line1);
	$pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($all,'all');
	
	// Start numbering pages
	$pdf->ezStartPageNumbers(520+30,810,10,'','',1);

	$pdf->setColor(.6,.6,.6,1);
	$pdf->addText(415, 720, 34, "<b>INVOICE</b>");
	$pdf->setColor(0,0,0,1);

	// Company log
	if ($g_company_logo)
	{
		if (!file_exists(AntConfig::getInstance()->data_path."/tmp"))
			mkdir(AntConfig::getInstance()->data_path."/tmp");

		// Copy the image to temp file using temp name
		$tmpfname = tempnam(AntConfig::getInstance()->data_path."/tmp", "image-");
		file_put_contents($tmpfname, file_get_contents($ANT->getAccBaseUrl()."/antfs/$g_company_logo"));
		// Insert temp file
		$pdf->addJpegFromFile($tmpfname,40, 380, 0, 0, 100);
		// Delete the temp image
		@unlink($tmpfname);
	}

	// Company and slogan
	$pdf->addText(40, 680, 18, $g_company);
	$pdf->setColor(.3,.3,.3,1);
	$pdf->addText(40, 665, 10, $g_slogan);
	$pdf->setColor(0,0,0,1);

	// Date and invoice number
	$pdf->addText(415, 680, 12, "Date:");
	//$pdf->addText(487, 680, 12, $_POST['date_entered']);
	$pdf->addText(487, 680, 12, date("m/d/Y"));
	$pdf->addText(415, 665, 12, "Invoice #:");
	$pdf->addText(487, 665, 12, $_POST['number']);
	$pdf->addText(415, 650, 12, "Customer ID:");
	$pdf->addText(487, 650, 12, $_POST['customer_id']);

	// Company Address
	$pdf->addText(40, 640, 11, "To:");
	$parts = explode("\n", $send_to);
	$start_y = 640;
	foreach ($parts as $line)
	{
		$pdf->addText(70, $start_y, 11, $line);
		$start_y = $start_y - 12;
	}
	/*
	$pdf->addText(70, 640, 11, "joe");
	$pdf->addText(70, 628, 11, "Guaranty RV, Inc");
	$pdf->addText(70, 616, 11, "2038 Bonnie Lane");
	$pdf->addText(70, 604, 11, "Springfield OR 97477");
	 */

	$pdf->ezSetY(560);

	$data = array(array("SALESPERSON"=>UserGetFullName($dbh, $_POST['owner_id']), "FOR"=>$_POST['name'], 
							"Payment Terms"=>$_POST['payment_terms'], "Due Date"=>$_POST['date_due']));
	$docCurY = $pdf->ezTable($data, NULL, NULL, 
								array('showHeadings'=>1, 'shaded'=>0, 'showLines'=>1,
									'fontSize' => 10, 'width' => 530, 'xPos' => 40,
									'xOrientation' => 'right', 'rowGap' => 0));
	$pdf->ezSetY($docCurY-15);

	$subtotal = 0;

	$data = array();
	if (is_array($_POST['entries']))
	{
		for ($i = 0; $i < count($_POST['entries']); $i++)
		{
			$quantity = $_POST['ent_quantity_'.$i];
			$name = $_POST['ent_name_'.$i];
			$amount = $_POST['ent_amount_'.$i];
			$amount_fmt = "$".number_format($amount, 2);
			$total = "$".number_format($quantity*$amount, 2);
			$subtotal += $quantity*$amount;

			$data[] = array("QUANTITY"=>$quantity, "DESCRIPTION"=>$name, "UNIT PRICE"=>$amount_fmt, "LINE TOTAL"=>$total);
		}
	}

	$docCurY = $pdf->ezTable($data, NULL, NULL, 
								array('showHeadings'=>1, 'shaded'=>0, 'showLines'=>2,
									'fontSize' => 10, 'width' => 530, 'xPos' => 40,
									'xOrientation' => 'right', 'rowGap' => 0, 
									'cols'=>array('QUANTITY'=>array('width'=> 65, 'justification'=>'center'),
													'UNIT PRICE'=>array('width'=> 75, 'justification'=>'right'),
													'LINE TOTAL'=>array('width'=> 75, 'justification'=>'right'))));

	// Print totals
	$data = array(array("<b>Subtotal</b>", "$".number_format($subtotal, 2)), 
				  array("<b>Taxes</b>", "$".number_format($subtotal*($TAX_RATE/100), 2)), 
				  array("<b>Total</b>", "$".number_format($subtotal+($subtotal*($TAX_RATE/100)), 2)));
	$docCurY = $pdf->ezTable($data, NULL, NULL, 
								array('showHeadings'=>0, 'shaded'=>0, 'showLines'=>1,
									'fontSize' => 10, 'width' => 530, 'xPos' => 420,
									'xOrientation' => 'right', 'rowGap' => 0,
									'cols'=>array('0'=>array('width'=> 75, 'justification'=>'right'),
												  '1'=>array('width'=> 75, 'justification'=>'right'))));
	if ($docCurY >= 300)
	{
		$pdf->ezSetY($docCurY-25);
		$pdf->ezText($g_notes_line1, 10, array('justification'=>'center'));
		$pdf->ezText("<b>".$g_notes_line2."</b>", 12, array('justification'=>'center'));
	}
	
	$pdf->ezStream();
?>
