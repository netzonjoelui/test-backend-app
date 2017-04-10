<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("infocenter/ic_functions.php");

	ini_set("max_execution_time", "7200");	

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$OBJ_TYPE = $_GET['obj_type'];

	header("Content-type: text/csv");
	//header("Content-Length: ".strlen($buf));
	header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
	header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
	header("Content-Disposition: attachment; filename=export-".date("m-d-Y").".csv");

	// Print headers
	// ------------------------------------------------------------
	$objf = new CAntObjectFields($dbh, $OBJ_TYPE);
	$ofields = $objf->getFields();
	$buf = "";
	foreach ($ofields as $fname=>$field)
	{
		if ($buf) $buf .= ",";
		$buf .= "$fname";
	}
	echo "$buf\n";

	// Build query and get list
	// ------------------------------------------------------------
    $numRecords = 100;
    $offset = 0;
    
	$olist = new CAntObjectList($dbh, $OBJ_TYPE, $USER);
	$olist->processFormConditions($_POST);
	$olist->getObjects($offset, $numRecords);
    $total = $olist->getTotalNumObjects();
	$num = $olist->getNumObjects();
	for ($i = 0; $i < $num; $i++)
	{
		$line_buf = "";
		$obj = $olist->getObject($i);
		foreach ($ofields as $fname=>$field)
		{
			$buf = "";
			if ($field['type']=='fkey_multi' || $field['type']=='object_multi')
			{
				$vals = $obj->getValue($fname);

				if (is_array($vals) && count($vals))
				{
					foreach ($vals as $val)
					{
						if ($buf) $buf .= "; ";
						$buf .= $obj->getForeignValue($fname, $val);
					}
				}
			}
			else if ($field['type']=='fkey' || $field['type']=='object')
			{
				$buf = stripslashes($obj->getForeignValue($fname));
			}
			else
			{
				$buf = stripslashes($obj->getValue($fname));
			}

			if ($line_buf)
				$line_buf .= ",";

			$line_buf .= "\"";
			$line_buf .= str_replace('"', "'", stripslashes($buf));
			$line_buf .= "\"";
		}
        
        // If result set is larger than 1000
        $offset++;
        if ($i == ($num-1) && $offset < $total)
        {                
            $olist->getObjects($offset, $numRecords); // Get next page
            $num = $olist->getNumObjects();
            $i = -1;
        }
        
		echo $line_buf."\n";  
		$olist->unsetObject($i);
	}
?>
