<?php
$TAX_RATE = ($obj->getValue('tax_rate')) ? $obj->getValue('tax_rate') : 0;
			  
// Get template information
if ($obj->getValue('template_id'))
{
	$templateObj = CAntObject::factory($ANT->dbh, "invoice_template", $obj->getValue('template_id'), $USER);
	$g_company_logo = $templateObj->getValue('company_logo');
	$g_company = $templateObj->getValue('company_name');
	$g_slogan = $templateObj->getValue('company_slogan');
	$g_notes_line1 = $templateObj->getValue('notes_line1');
	$g_notes_line2 = $templateObj->getValue('notes_line2');
	$g_footer_line1 = $templateObj->getValue('footer_line1');
}
else
{
	$g_company = $ANT->settingsGet("general/company_name");
	$g_slogan = "";
	$g_notes_line1 = "Please make all checks payable to $g_company";
	$g_notes_line2 = "Thank you for your business!";
	$g_footer_line1 = "";
}

if ($obj->getValue('customer_id'))
{
	$cust = CAntObject::factory($ANT->dbh, "customer", $obj->getValue('customer_id'), $USER);

	$send_to = $cust->getValue("name")."\n";
	if ($cust->getValue("billing_street"))
	{
		$send_to .= $cust->getValue("billing_street")."\n";
		$send_to .= $cust->getValue("billing_city");
		$send_to .= $cust->getValue("billing_state")." ";
		$send_to .= $cust->getValue("billing_zip");
	}
	else if ($cust->getValue("street"))
	{
		$send_to .= $cust->getValue("street")."\n";
		$send_to .= $cust->getValue("city");
		$send_to .= $cust->getValue("state")." ";
		$send_to .= $cust->getValue("zip");
	}
}

// Check if we are printing past page 1
if ($i > 0)
	$pdf->ezNewPage();

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
$pdf->addText(487, 680, 12, date("m/d/Y"));
$pdf->addText(415, 665, 12, "Invoice #:");
$pdf->addText(487, 665, 12, $obj->getValue('id'));
$pdf->addText(415, 650, 12, "Customer ID:");
$pdf->addText(487, 650, 12, $obj->getValue('customer_id'));

// Company Address
$pdf->addText(40, 640, 11, "To:");
$parts = explode("\n", $send_to);
$start_y = 640;
foreach ($parts as $line)
{
	$pdf->addText(70, $start_y, 11, $line);
	$start_y = $start_y - 12;
}

$pdf->ezSetY(560);

$data = array(array("SALESPERSON"=>UserGetFullName($ANT->dbh, $obj->getValue('owner_id')), "FOR"=>$obj->getValue('name'), 
						"Payment Terms"=>$obj->getValue('payment_terms'), "Due Date"=>$obj->getValue('date_due')));
$docCurY = $pdf->ezTable($data, NULL, NULL, 
							array('showHeadings'=>1, 'shaded'=>0, 'showLines'=>1,
								'fontSize' => 10, 'width' => 530, 'xPos' => 40,
								'xOrientation' => 'right', 'rowGap' => 0));
$pdf->ezSetY($docCurY-15);

// Add detail
$data = array();
for ($j = 0; $j < $obj->getNumItems(); $j++)
{
	$item = $obj->getItem($j);

	$total = "$".number_format($item->quantity * $item->amount, 2);

	$data[] = array("QUANTITY"=>$item->quantity, "DESCRIPTION"=>$item->name, "UNIT PRICE"=>"$".number_format($item->amount, 2), "LINE TOTAL"=>$total);
}

$docCurY = $pdf->ezTable($data, NULL, NULL, 
							array('showHeadings'=>1, 'shaded'=>0, 'showLines'=>2,
								'fontSize' => 10, 'width' => 530, 'xPos' => 40,
								'xOrientation' => 'right', 'rowGap' => 0, 
								'cols'=>array('QUANTITY'=>array('width'=> 65, 'justification'=>'center'),
												'UNIT PRICE'=>array('width'=> 75, 'justification'=>'right'),
												'LINE TOTAL'=>array('width'=> 75, 'justification'=>'right'))));

// Print totals
$data = array(array("<b>Subtotal</b>", "$".number_format($obj->getSubtotal(), 2)), 
			  array("<b>Taxes</b>", "$".number_format($subtotal*($TAX_RATE/100), 2)), 
			  array("<b>Total</b>", "$".number_format($obj->getTotal(), 2)));
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
