<?php
	require_once('lib/Worker.php'); 
	require_once('lib/pdf/class.ezpdf.php'); 

	if (is_array($g_workerFunctions))
	{
		$g_workerFunctions["customers/pdf/mailinglabels"] = "cust_pdf_mailing_labels";
	}

	function cust_pdf_mailing_labels($job)
	{
		$data = unserialize($job->workload());
		$dbh = $job->dbh;

		$pdf = new Cezpdf();
		$pdf->selectFont(AntConfig::getInstance()->application_path.'/lib/pdf/fonts/Helvetica.afm');
		$pdf->ezSetMargins(0,0,0,0);
		
		// Get paper coordinates
		$result = $dbh->Query("select name, cols, y_start_pos, y_interval, x_pos from printing_papers_labels where id='".$data['paper']."'");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$curY = $row['y_start_pos'];
			$num_cols = $row['cols'] - 1;
			$y_interval = $row['y_interval'];
			$x_pos = explode(",", $row['x_pos']);
		}
		$dbh->FreeResults($result);
		
		$original_y = $curY;
		$curCol = 0;

		$olist = new CAntObjectList($dbh, "customer", $job->user);
		$olist->processFormConditions($data);
		$olist->getObjects();
		$num = $olist->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{
			$line_buf = "";
			$obj = $olist->getObject($i);
			$name = $obj->getValue("salutation");
			if (!$name)
				$name = CustGetName($dbh, $obj->id);
			
			$street = $obj->getValue('street');
			$street2 = $obj->getValue('street2');
			$city = $obj->getValue('city');
			$state = $obj->getValue('state');
			$zip = $obj->getValue('zip');
			
			if (!$street || !$zip)
			{
				$street = $obj->getValue('business_street');
				$street2 = $obj->getValue('business_street2');
				$city = $obj->getValue('business_city');
				$state = $obj->getValue('business_state');
				$zip = $obj->getValue('business_zip');
			}
			
			if ($curY < 36)
			{
				$pdf->ezNewPage();
				$curY = $original_y;
				$curCol = 0;
			}
			
			$pdf->addText($x_pos[$curCol], $curY, 10, $name);
			$pdf->addText($x_pos[$curCol], $curY - 12, 10, $street);
			if ($street2)
			{
				$pdf->addText($x_pos[$curCol], $curY - 24, 10, $street2);
				$pdf->addText($x_pos[$curCol], $curY - 36, 10, "$city, $state $zip");
			}
			else
				$pdf->addText($x_pos[$curCol], $curY - 24, 10, "$city, $state $zip");
				
			if ($curCol >= $num_cols)
			{
				$curY -= $y_interval;
				$curCol = 0;
			}
			else
				$curCol++;

			$olist->unsetObject($i);
		}

		$antfs = new AntFs($dbh, $job->user);
		$fldr = $antfs->openFolder("/System/temp");
		$file = $fldr->openFile("rpt.pdf", true);
		$size = $file->write($pdf->ezOutput());
		return $file->id;
	}
?>
