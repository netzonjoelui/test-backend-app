<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");
	require_once("infocenter/ic_functions.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$OBJ_TYPE = $_GET['obj_type'];
	$OFFSET = ($_GET['offset']) ? $_GET['offset'] : 0;
	$LIMIT = ($_GET['limit']) ? $_GET['limit'] : 50;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<objects>";
	
	// Build query and get list
	// ------------------------------------------------------------
	$olist = new CAntObjectList($dbh, $OBJ_TYPE, $USER);
	if ($_GET['showdeleted']==1 || $_GET['type']=="sync")
		$olist->hideDeleted = false;
	if ($_GET['type']=="sync" && $_GET['ts_lastsync'])
	{
		$olist->hideDeleted = false;
		$olist->addCondition("and", "ts_updated", "is_greater_or_equal", $_GET['ts_lastsync']);
	}

	// Check for private
	if ($olist->obj->isPrivate())
	{
		if ($olist->obj->fields->getField("owner"))
			$olist->fields("and", "owner", "is_equal", $USER->id);
		if ($olist->obj->fields->getField("owner_id"))
			$olist->addCondition("and", "owner_id", "is_equal", $USER->id);
		if ($olist->obj->fields->getField("user_id"))
			$olist->addCondition("and", "user_id", "is_equal", $USER->id);
	}

	// Set conditions based on UI form
	$olist->processFormConditions($_POST);

	$ret = $olist->getObjects($OFFSET, $LIMIT);
	$num = $olist->getNumObjects();

	//echo "<query>".($olist->lastQuery)."</query>";
	if ($ret == -1)
		echo "<error>".$olist->lastError."</error>";

	echo "<num>".$olist->total_num."</num>";
	if ($_GET['type']=="sync")
		echo "<ts_lastsync>".gmdate("Y-m-d\\TG:i:s\\Z", time())."</ts_lastsync>";

	// Print pagination
	// ------------------------------------------------------------
	if ($olist->total_num > $LIMIT)
	{
		// Get total number of pages
		$leftover = $olist->total_num % $LIMIT;
		
		if ($leftover)
			$numpages = (($olist->total_num - $leftover) / $LIMIT) + 1; //($numpages - $leftover) + 1;
		else
			$numpages = $olist->total_num / $LIMIT;
		// Get current page
		if ($OFFSET > 0)
		{
			$curr = $OFFSET / $LIMIT;
			$leftover = $OFFSET % $LIMIT;
			if ($leftover)
				$curr = ($curr - $leftover) + 1;
			else 
				$curr += 1;
		}
		else
			$curr = 1;
		// Get previous page
		if ($curr > 1)
			$prev = $OFFSET - $LIMIT;
		// Get next page
		if (($OFFSET + $LIMIT) < $olist->total_num)
			$next = $OFFSET + $LIMIT;
		$pag_str = "Page $curr of $numpages";
		echo "<paginate><prev>$prev</prev><next>$next</next><pag_str>$pag_str</pag_str></paginate>";
	}

	// Print facets
	// ------------------------------------------------------------
	if (count($olist->facetCounts))
	{
		echo "<facet_counts>";
		foreach ($olist->facetCounts as $fname=>$cnts)
		{
			echo "<field name=\"$fname\">"; 
			foreach ($cnts as $term=>$cnt)
				echo "<term value=\"".rawurlencode($term)."\" count=\"$cnt\" />";
			echo "</field>";
		}
		echo "</facet_counts>";
	}

	// Print objects
	// ------------------------------------------------------------
	for ($i = 0; $i < $num; $i++)
	{
		if ($_GET['updatemode']) // Only get id and revision
		{
			$objMin = $olist->getObjectMin($i);	

			echo "<object>";
			echo "<id allowopen='1'>".$objMin['id']."</id>";
			echo "<revision>".$objMin['revision']."</revision>";
			echo "</object>";
		}
		else // Print full details
		{
			$obj = $olist->getObject($i);	

			$f_canview = $obj->dacl->checkAccess($USER, "View", ($USER->id==$obj->owner_id)?true:false);

			echo "<object>";

			echo "<id allowopen='".(($f_canview)?'1':'0')."' hascomments='".(($obj->hasComments())?'1':'0')."'>".$obj->id."</id>";
			$rev = $obj->getValue("revision");
			echo "<revision>".(($rev)?$rev:1)."</revision>";

			$ofields = $olist->fields_def_cache->getFields();
			foreach ($ofields as $fname=>$field)
			{
				if ($fname == "id")
					continue;

				if (!$f_canview && $fname!="name" && $fname!="user_id" && $fname!="owner_id")
				{
					echo "";
				}
				else
				{
					if ($field['type']=='fkey_multi' || $field['type']=='object_multi')
					{
						echo "<$fname>";
						$vals = $obj->getValue($fname);

						if (is_array($vals) && count($vals))
						{
							foreach ($vals as $val)
								echo "<value key=\"".rawurlencode($val)."\">".rawurlencode($obj->getForeignValue($fname, $val))."</value>";
						}
						echo "</$fname>";
					}
					else if ($field['type']=='fkey' || $field['type']=='object')
					{
						$val = $obj->getValue($fname);
						echo "<$fname key=\"".rawurlencode($val)."\">";
						echo rawurlencode(stripslashes($obj->getForeignValue($fname, $val)));
						echo "</$fname>";
					}
					else
					{
						$val = $obj->getValue($fname, true);
						if ($fname == $olist->fields_def_cache->listTitle && $olist->fields_def_cache->parentField)
						{
							$path = $obj->getValue("path");
							if ($path)
								$val = $path."/".$val;
						}
						//if (strlen($val) > 512)
							//$val = substr($val, 0, 512)."...";
                            
                        if ($field['type']=='timestamp')
                        {
                            $val = correctTimezone($val);
                        }
                            
						echo "<$fname>";
						echo rawurlencode(strip_tags($val));
                        //echo $val;
						echo "</$fname>";
					}
				}

			}
			echo "</object>";

			$olist->unsetObject($i);
		}
	}
	
	echo "</objects>";
?>
