<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../lib/CDatabase.awp");

	$dbh = new CDatabase();

	$widg_id = null;

	if ($widg_id)
	{
		$result = $dbh->Query("select id from users");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$uid = $row['id'];

			$query = "select position from user_home_layout where user_id='$uid' and col='1' and id not in 
						(select id from user_home_layout where user_id='$uid' and window_id='$widg_id')
						order by position DESC limit 1;";
			$result2 = $dbh->Query($query);
			if ($dbh->GetNumberRows($result2))
			{
				$row2 = $dbh->GetNextRow($result2, 0);
				$position = $row2['position'];
				$dbh->FreeResults($result2);

				$dbh->Query("insert into user_home_layout(user_id, col, position, window_id) values
							('$uid', '1', '".($position+1)."', '$widg_id')");
			}
		}
		$dbh->FreeResults($result);
	}
?>
