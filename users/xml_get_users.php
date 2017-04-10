<?php
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;

	$START = ($_GET['start']) ? $_GET['start'] : 0;
	if ($_GET['showper'])
		$SHOWPER = $_GET['showper'];
	else
		$SHOWPER = 200;

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	echo "<users>";

	$query = "select id, name, full_name, active, image_id, timezone, 
				team_id, manager_id, title, phone from users where id is not null ";
	if ($_GET['uid'])
		$query .= " and id='".$_GET['uid']."' ";
	else
		$query .= " and name!='administrator' ";
	if ($_GET['profile'])
		$query .= " and id='".$USERID."' ";
	if (!$_GET['view_active'])
		$query .= " and active='t' ";
	if ($_GET['gid'])
	{
		$query .= " and id in (select user_id from user_group_mem where group_id='".$_GET['gid']."') ";
	}
	if ($_GET['search'])
	{
		$search = rawurldecode($_GET['search']);
		$sparts = explode(" ", $search);

		$cond = "";
		foreach ($sparts as $part)
		{
			if ($cond) $cond .= " and ";
			$cond .= " (lower(name) like lower('%".$dbh->Escape($part)."%')
						or lower(full_name) like lower('%".$dbh->Escape($part)."%'))";
		}

		$query .= " and ($cond) ";
	}
	$query .= " order by active, name ";
	
	$result = $dbh->Query($query);
	$num = $dbh->GetNumberRows($result);

	if ($num > $SHOWPER)
	{
		// Get total number of pages
		$leftover = $num % $SHOWPER;
		
		if ($leftover)
			$numpages = (($num - $leftover) / $SHOWPER) + 1; //($numpages - $leftover) + 1;
		else
			$numpages = $num / $SHOWPER;
		// Get current page
		if ($START > 0)
		{
			$curr = $START / $SHOWPER;
			$leftover = $START % $SHOWPER;
			if ($leftover)
				$curr = ($curr - $leftover) + 1;
			else 
				$curr += 1;
		}
		else
			$curr = 1;
		// Get previous page
		if ($curr > 1)
			$prev = $START - $SHOWPER;
		// Get next page
		if (($START + $SHOWPER) < $num)
			$next = $START + $SHOWPER;
		$pag_str = "Page $curr of $numpages";
		echo "<paginate><prev>$prev</prev><next>$next</next><pag_str>$pag_str</pag_str></paginate>";
	}
	
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		if ((is_numeric($START) || $START == 0 ) && $SHOWPER < $num)
		{
			if ($i < $START)
				continue;
			
			if ($i+1 > $START + $SHOWPER)
				break;
		}

		// Get name
		echo "<user>";
		echo "<id>".rawurlencode($row['id'])."</id>";
		echo "<image_id>".rawurlencode($row['image_id'])."</image_id>";
		echo "<theme_id>".rawurlencode($row['theme_id'])."</theme_id>";
		echo "<timezone_id>".rawurlencode($row['timezone_id'])."</timezone_id>";
		echo "<team_id>".rawurlencode($row['team_id'])."</team_id>";
		echo "<team_name>".rawurlencode(($row['team_id'])?UserGetTeamName($dbh, $row['team_id']):"")."</team_name>";
		echo "<manager_id>".rawurlencode($row['manager_id'])."</manager_id>";
		echo "<manager_name>".rawurlencode(($row['manager_id'])?UserGetFullName($dbh, $row['manager_id']):"")."</manager_name>";
		echo "<title>".rawurlencode($row['title'])."</title>";
		echo "<phone>".rawurlencode($row['phone'])."</phone>";
		echo "<name>".rawurlencode(stripslashes($row['name']))."</name>";
		echo "<full_name>".rawurlencode(stripslashes($row['full_name']))."</full_name>";
		echo "<active>".rawurlencode(stripslashes($row['active']))."</active>";
		$res2 = $dbh->Query("select id, name from user_groups where id in (select group_id from user_group_mem where user_id='".$row['id']."')");
		$num2 = $dbh->GetNumberRows($res2);
		if ($num2)
			echo "<groups>";
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh->GetNextRow($res2, $j);
			echo "<group><id>".$row2['id']."</id><name>".rawurlencode($row2['name'])."</name></group>";
		}
		$dbh->FreeResults($res2);
		if ($num2)
			echo "</groups>";
		if ($_GET['det'] == "full")
		{
			echo "<password>000000</password>";

			// Get default email account
			$res2 = $dbh->Query("select id, name, address, reply_to from email_accounts where user_id='".$row['id']."' and f_default='t'");
			if ($dbh->GetNumberRows($res2))
			{
				$row2 = $dbh->GetNextRow($res2, 0);

				echo "<email_display_name>".rawurlencode($row2['name'])."</email_display_name>";
				echo "<email_address>".rawurlencode($row2['address'])."</email_address>";
				echo "<email_replyto>".rawurlencode($row2['reply_to'])."</email_replyto>";
			}
			$dbh->FreeResults($res2);

			$fw = UserGetPref($dbh, $row['id'], "general/f_forcewizard");
			echo "<f_forcewizard>".rawurlencode(($fw)?$fw:'f')."</f_forcewizard>";
		}
		echo "<email>".rawurlencode(UserGetEmail($dbh, $row['id']))."</email>";
		echo "<mobile_phone>".rawurlencode(UserGetPref($dbh, $row['id'], "mobile_phone"))."</mobile_phone>";
		echo "<mobile_phone_carrier>".rawurlencode(UserGetPref($dbh, $row['id'], "mobile_phone_carrier"))."</mobile_phone_carrier>";
		echo "</user>";
	}

	echo "</users>";
?>
