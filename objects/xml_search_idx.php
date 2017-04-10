<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/CAntObject.php");
	require_once("lib/CAntObjectList.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$OFFSET = ($_GET['offset']) ? $_GET['offset'] : 0;
	$LIMIT = ($_GET['limit']) ? $_GET['limit'] : 50;
	$SEARCH = $_REQUEST['search'];

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	if (!$SEARCH)
	{
		echo "<objects></objects>";
		exit;
	}

	// Build query
	echo "<objects>";
	
	// Build query and get list
	// ------------------------------------------------------------
	switch (ANT_INDEX_TYPE)
	{
	case 'solr':

		$obj = new CAntObject($dbh, "activity"); // any object will do
		$indexer = new CAntObjectIndexSolr($dbh, $obj);

		$options = array
		(
			'hostname' => ANT_INDEX_SOLR_HOST,
			'login'    => "admin",
			'password' => "admin",
			'port'     => 8983,
			'timeout'  => 30
		);
		$solrClient = new SolrClient($options);
		$queryObject = new SolrQuery();
		$queryObject->setQuery($SEARCH);
		$queryObject->addFilterQuery("database:".$dbh->dbname." AND f_deleted:false AND (idx_private_owner_id:\"$USERID\" OR -idx_private_owner_id)");
		$queryObject->setStart(0);
		$queryObject->setRows(200);
		$query_response = $solrClient->query($queryObject);
		$response = $query_response->getResponse();
		$total = $response->response->numFound;
		$results = $response->response->docs;
		$num = count($results);
		echo "<count>$num - ".count($results)."</count>";

		break;

	case 'db':
	default:
		$act_type = objGetAttribFromName($dbh, "activity", "id");
		//$cond = "where object_type_id!='$act_type' and (private_owner_id='$USERID' or private_owner_id is null) "; // Exclude activities
		$cond = "where type_id!='$act_type' and (private_owner_id='$USERID' or private_owner_id is null) "; // Exclude activities

		$searchstr = str_replace(" ", " & ", $SEARCH);
		$cond .= " and tsv_keywords @@ to_tsquery('".$dbh->Escape($searchstr)."')";
		/*
		$parts = explode(" ", $SEARCH);
		foreach ($parts as $part)
		{
			$cond .= " and keywords like '%".$dbh->Escape(strtolower($part))."%' ";
		}
		*/
		//echo "<num>".$olist->total_num."</num>";
		
		// Print pagination
		// ------------------------------------------------------------
		/*
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
		 */

		// Print objects
		// ------------------------------------------------------------
		// ts_rank(tsv_keywords, to_tsquery('".$dbh->Escape($searchstr)."')) as rank
		$query = "select object_type_id, object_id, snippet, ts_rank(tsv_keywords, to_tsquery('".$dbh->Escape($searchstr)."')) as rank 
					from object_index_fulltext $cond order by rank DESC, ts_entered DESC limit 100";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		break;
	}

	for ($i = 0; $i < $num; $i++)
	{
		switch (ANT_INDEX_TYPE)
		{
		case 'solr':
			$row = array("object_id", $results[$i]->oid);
			$oid = $row['object_id'];
			$obj_type = $results[$i]->type;
			$snippet = $results[$i]->text;
			foreach($results[$i] as $var=>$value) 
			{
				$fname = $indexer->unescapeField($var);
				if ($value == "true")
					$value = 't';
				if ($value == "false")
					$value = 'f';
				$row[$fname] = $value;
			}
			break;
		case 'db':
		default:
			$row = $dbh->GetRow($result, $i);
			$obj_type = objGetNameFromId($dbh, $row['object_type_id']);
			$snippet = $row['snippet'];
			break;
		}

		if ($obj_type)
		{
			$obj = new CAntObject($dbh, $obj_type, $row['object_id'], $USER);
			$name = $obj->getName();

			// Highlight terms
			foreach ($parts as $part)
			{
				if ($part)
				{
					$snippet = preg_replace("|($part)|Ui", "<span style=\"font-weight:bold;\">$1</span>" , $snippet );
					$name = preg_replace("|($part)|Ui", "<span style=\"font-weight:bold;\">$1</span>" , $name );
				}
					//$snippet = str_ireplace($part, "<span style='font-weight:bold;'>$part</span>", $snippet);
				
			}

			// Print record if user has permissions
			if ($obj->dacl->checkAccess($USER, "View", ($USER->id==$obj->owner_id)?true:false))
			{
				echo "<object>";
				echo "<id>".$obj->id."</id>";
				echo "<obj_type>".rawurlencode($obj_type)."</obj_type>";
				echo "<obj_type_title>".rawurlencode(objGetNameFromId($dbh, $row['object_type_id'], "title"))."</obj_type_title>";
				echo "<name>".rawurlencode($name)."</name>";
				echo "<snippet>".rawurlencode($snippet)."</snippet>";
				echo "</object>";
			}
		}
	}
	
	echo "</objects>";
?>
