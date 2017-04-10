<?php
	require_once("../../lib/AntConfig.php");
	require_once("../../settings/settings_functions.php");
	require_once("../../lib/CDatabase.awp");
	
	require_once("../../customer/customer_functions.awp");
	
	ini_set("max_execution_time", "28800");	
	ini_set('default_socket_timeout', "28800"); 

	$DEBUG = TRUE;
	
	$ans = new CAnsCLient();

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts where f_use_ans is not false");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);
			
			$obj_id = null;
			$res2 = $dbh->query("select id from app_object_types where name='customer'");
			if ($dbh->getnumberrows($res2))
			{
				$obj_id = $dbh->getvalue($res2, 0, "id");
			}

			/*
			if (!$dbh->ColumnExists("customer_fields", "f_processed"))
				$dbh->Query("alter table customer_fields add column f_processed boolean default false;");
			 */

			$result = $dbh->Query("select id, col_name, col_type, col_title from customer_fields");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetRow($result, $i);

				$col_name = str_replace("-", "_", $row['col_name']);
				$col_name = str_replace("#", "no", $col_name);
				$col_name = preg_replace("/[^a-zA-Z0-9_]/", "", strtolower($col_name));

				$type = "";
				switch ($row['col_type'])
				{
				case 'bool':
					$type = "bool";
					break;
				case 'date':
					$type = "date";
					break;
				case 'float':
					$type = "real";
					break;
				case 'text':
					$type = "text";
					break;
				}

				if (!$dbh->ColumnExists("customers", $col_name) && $type && $obj_id)
				{
					echo "Create column: ".$col_name."\n";

					$dbh->Query("alter table customers add column ".$col_name." ".$type.";");
					$res2 = $dbh->Query("insert into app_object_type_fields(type_id, name, title, type, subtype) values('".$obj_id."', '".$col_name."', 
										'".$dbh->Escape($row['col_title'])."', '".$dbh->Escape($type)."', '');
												select currval('app_object_type_fields_id_seq') as id;");
					if ($dbh->GetNumberRows($res2))
						$new_fid = $dbh->GetValue($res2, 0, "id");
				}
				else
				{
					$res2 = $dbh->Query("select id from app_object_type_fields where name='$col_name' and type_id='".$obj_id."'");
					if ($dbh->GetNumberRows($res2))
						$new_fid = $dbh->GetValue($res2, 0, "id");
				}
						

				$res2 = $dbh->Query("select val_".$row['col_type']." as val, customer_id from customer_field_values where field_id='".$row['id']."'");
				$num2 = $dbh->GetNumberRows($res2);
				for ($j = 0; $j < $num2; $j++)
				{
					$row2 = $dbh->GetRow($res2, $j);
					switch ($row['col_type'])
					{
					case 'bool':
						$val = "'".(($row2['val'] == 't')?'t':'f')."'";
						break;
					case 'date':
						$val = $dbh->EscapeDate($row2['val']);
						break;
					case 'float':
						$val = $dbh->EscapeNumber($row2['val']);
						break;
					case 'text':
						$val = "'".$dbh->Escape($row2['val'])."'";
						break;
					}
					if ($val && $new_fid)
					{
						$dbh->Query("update customers set $col_name=$val where id='".$row2['customer_id']."'");
						echo "\tSet $col_name=$val\n";

						$res3 = $dbh->Query("select opt_val from customer_field_optioins where field_id='".$row['id']."'");
						$num3 = $dbh->GetNumberRows($res3);
						for ($m = 0; $m < $num3; $m++)
						{
							$val = $dbh->GetValue($res3, $m, "opt_val");
							if ($dbh->GetNumberRows($dbh->Query("select id from app_object_field_options where field_id='$new_fid' and key='".$dbh->Escape($val)."'")))
							{
								$dbh->Query("insert into app_object_field_options(field_id, key, value) 
												values('$new_fid', '".$dbh->Escape($val)."', '".$dbh->Escape($val)."');");
							}
						}
					}
				}
			}
		}
	}
?>
