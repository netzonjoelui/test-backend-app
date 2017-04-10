<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once('lib/pdf/class.ezpdf.php'); 
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	
	ini_set("max_execution_time", "7200");	
	
	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$OBJ_TYPE = $_GET['obj_type'];
				  
	$pdf =& new Cezpdf();
	$pdf->selectFont('../lib/pdf/fonts/Helvetica.afm');
	$pdf->ezSetMargins(50,45,40,40);
	
	// Create Header
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);
	$pdf->line(40,755,550,755);
	$pdf->addText(40,760,12,$title);
	$pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($all,'all');
	
	// Create Footer
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);
	$pdf->line(40,45,550,45);
	$foot = "ANT Reports - ".date("l, F d, Y");
	$pdf->addText(40,35,8, $foot);
	$pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($all,'all');
	
	// Start numbering pages
	$pdf->ezStartPageNumbers(520+30,760,10,'','',1);
	
	// Build query and get list
	// ------------------------------------------------------------
	$objf = new CAntObjectFields($dbh, $OBJ_TYPE);
	$ofields = $objf->getFields();
	$olist = new CAntObjectList($dbh, $OBJ_TYPE, $USER);
	$olist->processFormConditions($_POST);
	$olist->getObjects();
	$num = $olist->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$obj = $olist->getObject($i);
		$docCurY = $pdf->ezText("<b>".$obj->getValue($objf->listTitle)."</b>", 12);
		$pdf->setStrokeColor(.9,.9,.9,1);
		$pdf->setLineStyle(1);
		$pdf->line(41,$docCurY-2,549, $docCurY-2);

		$genral_data = array();
		// Print general fields
		$col_cnt = 1;
		$col_arr = array();
		foreach ($ofields as $fname=>$field)
		{
			if ($field['type']!='fkey_multi' && $fname!="notes")
			{
				$col_arr[] = $field['title'];

				if ($field['type']=='fkey')
					$col_arr[] = stripslashes($obj->getForeignValue($fname));
				else 
					$col_arr[] = stripslashes($obj->getValue($fname));

				$col_cnt++;

				if ($col_cnt >= 4)
				{
					$col_cnt = 1;
					$genral_data[] = $col_arr;
					$col_arr = array();
				}
			}
		}

		if (count($col_arr))
			$genral_data[] = $col_arr;

		$docCurY = $pdf->ezTable($genral_data, NULL, NULL, 
										array('showHeadings'=>0, 'shaded'=>0, 'showLines'=>0, 
											'fontSize' => 8, 'width' => 550, 'xPos' => 40,
											'xOrientation' => 'right', 'rowGap' => 0,
											'cols'=>array('0'=>array('width'=> 70),
															'1'=>array('width'=> 113),
															'2'=>array('width'=> 70),
															'3'=>array('width'=> 113),
															'4'=>array('width'=> 70),
															'5'=>array('width'=> 113))));

		$pdf->ezText("\n");
		$olist->unsetObject($i);
	}

	$pdf->ezStream();
?>
