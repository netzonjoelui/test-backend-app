<?php
	function ic_GroupGetFullName($dbh, $gid)
	{
		$full_name = "";

		$result = $dbh->Query("select name, parent_id from ic_groups where id='$gid'");
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);
			$pre = "";

			if ($row['parent_id'])
			{
				$pre =  ic_GroupGetFullName($dbh, $row['parent_id']);
				$full_name = $pre . " > " . $row['name'];
			}
			else
			{
				$full_name = $row['name'];
			}

		}
		$dbh->FreeResults($result);

		return $full_name;
	}

	function ic_GroupDelete($dbh, $gid)
	{
		$result = $dbh->Query("select id from ic_groups where parent_id='$gid'");
		for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			ic_GroupDelete($dbh, $row['id']);
		}
		$dbh->FreeResults($result);

		$dbh->Query("delete from ic_groups where id='$gid';");
	}

	function ic_GroupGetSubgroups($dbh, $gid, &$ret = null)
	{
		if (!$ret)
			$ret = array();

		$result = $dbh->Query("select id from ic_groups where parent_id='$gid'");
		for ($i = 0; $i < $dbh->GetNumberRows($result); $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$ret[] = $row['id'];
			ic_GroupGetSubgroups($dbh, $row['id'], $ret);
		}
		$dbh->FreeResults($result);

		return $ret;
	}
?>
