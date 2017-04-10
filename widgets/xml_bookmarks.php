<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$FUNCTION = $_GET['function'];

	function buildBookmarksXml($dbh, $USERID, $CID=null)
	{
		$ret = "";

		// List folders first
		$query = "select id, name from favorites_categories where user_id='$USERID'";
		if ($CID)
			$query .= " and parent_id='$CID' ";
		$query .= " order by name";

		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$ret .= "<category id='".$row['id']."' name='".rawurlencode($row['name'])."'>";
			$ret .= buildBookmarksXml($dbh, $USERID, $row['id']);
			$ret .= "</category>";
		}
		$dbh->FreeResults($result);

		// Now get bookmarks
		$query = "select id, name, url, notes, favorite_category from favorites where user_id='$USERID'";
		if ($CID) 
			$query .= " and favorite_category='$CID'";
		else
			$query .= " and favorite_category is NULL";
		
		$result = $dbh->Query($query." order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i=0; $i<$num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			$ret .= "<bookmark>";
			$ret .= "<id>".$row['id']."</id>";
			$ret .= "<name>".rawurlencode($row['name'])."</name>";
			$ret .= "<url>".rawurlencode($row['url'])."</url>";
			$ret .= "<notes>".rawurlencode($row['notes'])."</notes>";
			$ret .= "<cid>".rawurlencode($row['favorite_category'])."</cid>";
			$ret .= "</bookmark>";
		}
		$dbh->FreeResults($result);

		return $ret;
	}

	function bookmarksDeleteCat(&$dbh, $CATID)
	{
		if (is_numeric($CATID))
		{
			// Get all sub directories
			$result = $dbh->Query("select id from favorites_categories where parent_id='$CATID'");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				FavoritesDeleteCat($dbh, $row['id']);
			}
			$dbh->FreeResults($result);
			
			// Delete this directory
			$dbh->Query("delete from favorites_categories where id='$CATID'");
			$dbh->Query("delete from favorites where favorite_category='$CATID'");
		}
		return $retval;
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	switch ($FUNCTION)
	{
	case "get_bookmarks":
		echo "<bookmarks>";
		echo buildBookmarksXml($dbh, $USERID);
		echo "</bookmarks>";
		break;

	case "delete_bookmark":
		if ($USERID && $_GET['bid'])
			$dbh->Query("delete from favorites where id='".$_GET['bid']."' and user_id='$USERID'");

		$retval = 1;
		break;
	
	case "edit_bookmark":
		$url = rawurldecode($_GET['url']);

		if ("http://" != strtolower(substr($url, 0, 7)) && "https://" != strtolower(substr($url, 0, 8)))
			$url = "http://".$url;

		$query = "";

		if ($USERID && $_GET['bid'])
		{
			$query = "update favorites set name='".$dbh->Escape(rawurldecode($_GET['name']))."', 
					  url='".$dbh->Escape($url)."' where id='".$_GET['bid']."'";
			$retval = $_GET['bid'];
			$dbh->Query($query);
		}
		else if ($USERID)
		{	
			$query = "insert into favorites(user_id, favorite_category, name, url)
					  values('$USERID', ".$dbh->EscapeNumber($_GET['cid']).",
							 '".$dbh->Escape(rawurldecode($_GET['name']))."', 
							 '".$dbh->Escape($url)."');
					  select currval('favorites_id_seq') as id;";
			$result = $dbh->Query($query);

			if ($dbh->GetNumberRows($result))
				$retval = $dbh->GetValue($result, 0, "id");
			else
				$retval = 0;
		}

		break;

	case "delete_category":
		if ($USERNAME && $_GET['catid'])
			bookmarksDeleteCat($dbh, $_GET['catid']);

		$retval = 1;
		break;

	case "rename_category":
		$catname = rawurldecode($_GET['cname']);

		if ($USERNAME && $_GET['cid'] && $catname)
			$dbh->Query("update favorites_categories set name='".$dbh->Escape($catname)."' where id='".$_GET['cid']."'");

		$retval = 1;
		break;

	case "add_category":
		$catname = rawurldecode($_GET['cname']);
		if (!$catname) $catname = "New Category";

		$result = $dbh->Query("insert into favorites_categories(user_id, parent_id, name)
							   values('$USERID', ".(($_GET['pcid']) ? "'".$_GET['pcid']."'" : 'NULL').", '$catname');
							   select currval('favorites_categories_id_seq') as id;");

		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$dbh->FreeResults($result);
			$retval = $row['id'];
		}
		else
		{
			$retval = -1;
		}
		break;
	}

	// Check for RPC
	if ($retval || $retval == -1)
	{
		echo "<response>";
		echo "<retval>" . rawurlencode($retval) . "</retval>";
		echo "<cb_function>" . $_GET['cb_function'] . "</cb_function>";
		echo "</response>";
	}
?>


