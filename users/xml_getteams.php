<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("lib/Dacl.php");
	require_once("lib/CAntObject.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	function localAdminPrintTeams(&$dbh, $USERID, $ACCOUNT, $gid=null)
	{
		global $TEAM_ACLS;
		
		$parent = ($gid) ? "='$gid'" : ' is NULL';
		$result = $dbh->Query("select id, parent_id, name from user_teams where parent_id$parent and account_id='$ACCOUNT' order by name");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);

			$DACL_TEAM = new Dacl($dbh, "teams/".$row['id'], true, $TEAM_ACLS);
			
			echo "<team>";
			echo "<id>".$row['id']."</id>";
			echo "<name>".rawurlencode($row['name'])."</name>";
			echo "<parent_id>".$row['parent_id']."</parent_id>";
			echo "<permissions_link>".$DACL_TEAM->getEditLink()."</permissions_link>";

			if ($_GET['get_obj_frm'])
			{
				$otid = objGetAttribFromName($dbh, $_GET['get_obj_frm'], "id");
				if ($otid)
				{
					$res2 = $dbh->Query("select form_layout_xml from app_object_type_frm_layouts where team_id='".$row['id']."' and type_id='$otid'");
					if ($dbh->GetNumberRows($res2))
						echo "<form_layout_text>".rawurlencode($dbh->GetValue($res2, 0, "form_layout_xml"))."</form_layout_text>";

				}
			}

			echo "</team>";

			localAdminPrintTeams($dbh, $USERID, $ACCOUNT, $row['id']);
		}
		$dbh->FreeResults($result);
	}

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<teams>";
	localAdminPrintTeams($dbh, $USERID, $ACCOUNT);
	echo "</teams>";
?>
