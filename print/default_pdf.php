<?php
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
	if ($field['type']!='fkey_multi' && $fname!="notes" && $fname!="f_deleted" 
		&& $fname!="revision" && $fname!="uname" && $fname!="password")
	{
		if ($obj->getValue($fname))
		{
			$col_arr[] = $field['title'];

			if ($field['type']=='fkey' || $field['type']=='alias')
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
}

if (count($col_arr))
	$genral_data[] = $col_arr;

$docCurY = $pdf->ezTable($genral_data, NULL, NULL, 
								array('showHeadings'=>0, 'shaded'=>0, 'showLines'=>0, 
									'fontSize' => 9, 'width' => 550, 'xPos' => 40,
									'xOrientation' => 'right', 'rowGap' => 0,
									'cols'=>array('0'=>array('width'=> 70),
													'1'=>array('width'=> 113),
													'2'=>array('width'=> 70),
													'3'=>array('width'=> 113),
													'4'=>array('width'=> 70),
													'5'=>array('width'=> 113))));

if ($obj->getValue("notes"))
	$pdf->ezText("\nNOTES:\n" . $obj->getValue("notes"));

if ($obj->hasComments())
{
	$pdf->ezText("\nCOMMENTS:");

	$commList = new CAntObjectList($ANT->dbh, "comment", $USER);
	$commList->addCondition("and", "associations", "is_equal", $OBJ_TYPE . ":" . $obj->id);
	$commList->addOrderBy("ts_entered", "DESC");
	$commList->getObjects(0, 10);
	$numComm = $commList->getNumObjects();
	for($j = 0; $j < $numComm; $j++)
	{
		$comm = $commList->getObject($j);

		$by = ($comm->getValue("sent_by")) ? $comm->getForeignValue("sent_by") : $comm->getValue("owner_id");
		
		$pdf->ezText("<u>" . $by . "</u> <i>@ " . $comm->getValue("ts_entered") . "</i>:\n" . $comm->getValue("comment") . "\n");
		
	}
}

$pdf->ezText("\n");
