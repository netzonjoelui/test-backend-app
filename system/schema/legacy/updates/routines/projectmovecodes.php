<?php
	// Copy all project messages to discussions
	$tbl = "project_bug_types";
	$result = $dbh_acc->Query("select id, name from $tbl where project_id is not null");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		// Check if code with same name already exists
		$res2 = $dbh_acc->Query("select id from $tbl where name='".$dbh_acc->Escape($row['name'])."' and project_id is null");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}
		else
		{
			echo "Adding code ".$row['name']."\n";
			$res2 = $dbh_acc->Query("insert into $tbl(name) values('".$dbh_acc->Escape($row['name'])."');
									 select currval('".$tbl."_id_seq') as id;");
			if ($dbh_acc->GetNumberRows($res2))
				$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}

		if ($new_id)
		{
			$res2 = $dbh_acc->Query("select id from project_bugs where type_id='".$row['id']."'");
			$num2 = $dbh_acc->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh_acc->GetRow($res2, $j);
				$obj = new CAntObject($dbh_acc, "case", $row2['id']);
				$obj->setValue("type_id", $new_id);
				$obj->save(false);

				echo "Updated case ".$row2['id']."\n";
			}

			$dbh_acc->Query("delete from $tbl where id='".$row['id']."'");
		}
	}

	// Copy all project messages to discussions
	$tbl = "project_bug_status";
	$result = $dbh_acc->Query("select id, name from $tbl where project_id is not null");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		// Check if code with same name already exists
		$res2 = $dbh_acc->Query("select id from $tbl where name='".$dbh_acc->Escape($row['name'])."' and project_id is null");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}
		else
		{
			echo "Adding code ".$row['name']."\n";
			$res2 = $dbh_acc->Query("insert into $tbl(name) values('".$dbh_acc->Escape($row['name'])."');
									 select currval('".$tbl."_id_seq') as id;");
			if ($dbh_acc->GetNumberRows($res2))
				$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}

		if ($new_id)
		{
			$res2 = $dbh_acc->Query("select id from project_bugs where status_id='".$row['id']."'");
			$num2 = $dbh_acc->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh_acc->GetRow($res2, $j);
				$obj = new CAntObject($dbh_acc, "case", $row2['id']);
				$obj->setValue("status_id", $new_id);
				$obj->save(false);

				echo "Updated case ".$row2['id']."\n";
			}

			$dbh_acc->Query("delete from $tbl where id='".$row['id']."'");
		}
	}

	// Copy all project messages to discussions
	$tbl = "project_bug_severity";
	$result = $dbh_acc->Query("select id, name from $tbl where project_id is not null");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		// Check if code with same name already exists
		$res2 = $dbh_acc->Query("select id from $tbl where name='".$dbh_acc->Escape($row['name'])."' and project_id is null");
		if ($dbh_acc->GetNumberRows($res2))
		{
			$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}
		else
		{
			echo "Adding code ".$row['name']."\n";
			$res2 = $dbh_acc->Query("insert into $tbl(name) values('".$dbh_acc->Escape($row['name'])."');
									 select currval('".$tbl."_id_seq') as id;");
			if ($dbh_acc->GetNumberRows($res2))
				$new_id = $dbh_acc->GetValue($res2, 0, "id");
		}

		if ($new_id)
		{
			$res2 = $dbh_acc->Query("select id from project_bugs where severity_id='".$row['id']."'");
			$num2 = $dbh_acc->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh_acc->GetRow($res2, $j);
				$obj = new CAntObject($dbh_acc, "case", $row2['id']);
				$obj->setValue("severity_id", $new_id);
				$obj->save(false);

				echo "Updated case ".$row2['id']."\n";
			}

			$dbh_acc->Query("delete from $tbl where id='".$row['id']."'");
		}
	}
?>
