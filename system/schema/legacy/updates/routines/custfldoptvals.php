<?php
	$obj_id = null;
	$res2 = $dbh_acc->query("select id from app_object_types where name='customer'");
	if ($dbh_acc->getnumberrows($res2))
	{
		$obj_id = $dbh_acc->getvalue($res2, 0, "id");
	}

	$result = $dbh_acc->Query("select id, col_name from customer_fields
								where id in (select field_id from customer_field_optioins);");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$col_name = $row['col_name'];
		echo "Checking $col_name\t";

		$res2 = $dbh_acc->Query("select id from app_object_type_fields where name='$col_name' and type_id='".$obj_id."'");
		if ($dbh_acc->GetNumberRows($res2))
			$new_fid = $dbh_acc->GetValue($res2, 0, "id");

		if ($new_fid)
		{
			$res3 = $dbh_acc->Query("select opt_val from customer_field_optioins where field_id='".$row['id']."'");
			$num3 = $dbh_acc->GetNumberRows($res3);
			for ($m = 0; $m < $num3; $m++)
			{
				$val = $dbh_acc->GetValue($res3, $m, "opt_val");
				if (!$dbh_acc->GetNumberRows($dbh_acc->Query("select id from app_object_field_options where field_id='$new_fid' and key='".$dbh_acc->Escape($val)."'")))
				{
					$dbh_acc->Query("insert into app_object_field_options(field_id, key, value) 
									values('$new_fid', '".$dbh_acc->Escape($val)."', '".$dbh_acc->Escape($val)."');");
				}
			}
			echo "copied $num3 values\n";
		}
		else
		{
			echo "field not found!\n";
		}
	}
?>
