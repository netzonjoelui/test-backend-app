<?php
	require_once("../lib/AntConfig.php");
	require_once("../lib/CDatabase.awp");
	require_once("ic_functions.php");

	$dbh = new CDatabase;

	$FUNCTION = $_REQUEST['function'];
	$USER = $_REQUEST['username'];
	$PASS = $_REQUEST['password'];

	// Return XML
	header("Content-type: text/xml");

	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 
	switch ($FUNCTION)
	{
	case 'get_groups':
		if ($_GET['gid'])
		{
			echo "<groups>";
			$result = $dbh->Query("select id, name from ic_groups where parent_id='".$_GET['gid']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				echo "<group>";
				echo "<id>".rawurlencode($row['id'])."</id>";
				echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
				echo "</group>";
			}
			echo "</groups>";
		}
		else
		{
			$retval = "Define a parent group!";
		}
		break;
	case 'get_group_parent':
		if ($_GET['gid'])
		{
			echo "<groups>";
			$result = $dbh->Query("select id, name from id_groups where id in (select parent_id from ic_groups where id='".$_GET['gid']."')");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				echo "<group>";
				echo "<id>".rawurlencode($row['id'])."</id>";
				echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
				echo "</group>";
			}
			echo "</groups>";
		}
		else
		{
			$retval = "Define a parent group!";
		}
		break;
	case 'get_group_name':
		if ($_GET['gid'])
		{
			echo "<groups>";
			$result = $dbh->Query("select name from ic_groups where id='".$_GET['gid']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				echo "<group>";
				echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
				echo "</group>";
			}
			echo "</groups>";
		}
		else
		{
			$retval = "Define a parent group!";
		}
		break;
	case 'get_group_info':
		if ($_GET['gid'])
		{
			echo "<groups>";
			$result = $dbh->Query("select id, name, parent_id from ic_groups where id='".$_GET['gid']."'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);

				echo "<group>";
				echo "<id>".rawurlencode($row['id'])."</id>";
				echo "<parent_id>".rawurlencode($row['parent_id'])."</parent_id>";
				echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
				echo "</group>";
			}
			echo "</groups>";
		}
		else
		{
			$retval = "Define a parent group!";
		}
		break;
	case 'get_documents':
		$query = "select * from ic_documents where id is not null ";
		if ($_GET['docid'])
			$query .= " and id='".$_GET['docid']."' ";
		if ($_GET['search'])
		{
			$query .= " and (title ilike '%".rawurldecode($_GET['search'])."%' 
							 or body ilike '%".rawurldecode($_GET['search'])."%' or keywords ilike '%".rawurldecode($_GET['search'])."%') ";
		}
		if (is_numeric($_GET['gid']))
		{
			$subgroups = ic_GroupGetSubgroups($dbh, $_GET['gid']);
			if (is_array($subgroups) && count($subgroups))
			{
				$precond = "";

				foreach ($subgroups as $sgrp)
					$precond .= " or group_id='$sgrp' ";

				$cond = " (group_id='".$_GET['gid']."' $precond)";
			}
			else
			{
				$cond = "group_id='".$_GET['gid']."'";
			}
			$query .= " and id in (select document_id from ic_document_group_mem where $cond) ";
		}
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		if ($num)
			echo "<documents>";
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$body = ($_GET['docid']) ? $row['body'] : substr(strip_tags(str_replace("<br>", " ", $row['body'])), 0, 128);

			// Get name
			echo "<document>";
			echo "<id>".rawurlencode($row['id'])."</id>";
			echo "<title>".rawurlencode(stripslashes($row['title']))."</title>";
			echo "<keywords>".rawurlencode(stripslashes($row['keywords']))."</keywords>";
			echo "<body>".rawurlencode(stripslashes($body))."</body>";
			echo "<video_file_id>".$row['video_file_id']."</video_file_id>";
			$res2 = $dbh->Query("select group_id from ic_document_group_mem where document_id='".$row['id']."'");
			$num2 = $dbh->GetNumberRows($res2);
			if ($num2)
				echo "<groups>";
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh->GetNextRow($res2, $j);
				echo "<group>".$row2['group_id']."</group>";
			}
			$dbh->FreeResults($res2);
			if ($num2)
				echo "</groups>";
			// Related documents
			$res2 = $dbh->Query("select ic_document_relation_mem.id, ic_documents.title, ic_document_relation_mem.related_id 
								from ic_documents, ic_document_relation_mem 
								where ic_documents.id=ic_document_relation_mem.related_id 
								and ic_document_relation_mem.document_id='".$row['id']."'
								order by ic_document_relation_mem.id");
			$num2 = $dbh->GetNumberRows($res2);
			if ($num2)
				echo "<related_documents>";
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh->GetNextRow($res2, $j);
				echo "<document id=\"".$row2['related_id']."\">".rawurlencode($row2['title'])."</document>";
			}
			$dbh->FreeResults($res2);
			if ($num2)
				echo "</related_documents>";
			echo "</document>";
		}

		if ($num)
			echo "</documents>";
		else
			$retval = "No documents found";

		break;
	default:
		$retval = "-1";
		break;
	}

	if ($retval)
	{

		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>".$_GET['cb_function']."</cb_function>";
		echo "</response>";
	}
?>
