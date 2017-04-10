<?php
	if (!isset($settings_server_root))
		require_once("../lib/AntConfig.php");
	require_once("settings/settings_functions.php");		
	require_once("lib/CDatabase.awp");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	function sysEntExists($dbh, $tbl, $col, $val, $additional_cond)
	{
		$ret = false;

		if ($tbl && $col)
		{
			$result = $dbh->Query("select $col from $tbl where $col='".$dbh->Escape($val)."' $additional_cond");
			if ($dbh->GetNumberRows($result))
				$ret = true;

			$dbh->FreeResults($result);
		}

		return $ret;
	}

	// Each record contains
	// 0 = table name
	// 1 = file name
	// 2 = check against column
	// 3 = optional additional condition for searching for this record
	// 4 = column to cvs match map (array)
	// 5 = Update if record already exists (true/false)
	// The first column in csv will always be the unique ident
	$maps = array();

	// ant_system
	// DEPRICATED: this has been moved to a function of schema_updates.php where value is initiaized
	//$fields = array("id"=>0,"schema_revision"=>1);
	//$maps[] = array("ant_system", "ant_system.csv", "id", "", $fields, false);

	// groups
	$fields = array("id"=>0,"name"=>1,"comments"=>2);
	$maps[] = array("user_groups", "groups.csv", "id", "", $fields, false);
	
	// DEPRICATED: blog_themes
	//$fields = array("id"=>0,"name"=>1);
	//$maps[] = array("blog_themes", "blog_themes.csv", "id", "", $fields, true);

	// DEPRICATED: calendar_events_sharing
	//$fields = array("id"=>0,"name"=>1);
	//$maps[] = array("calendar_events_sharing", "calendar_events_sharing.csv", "id", "", $fields, true);
	
	// DEPRICATED: calendar_sharing_types
	//$fields = array("id"=>0,"name"=>1,"share_name"=>2);
	//$maps[] = array("calendar_sharing_types", "calendar_sharing_types.csv", "id", "", $fields, true);

	// project_priorities
	$fields = array("id"=>0,"name"=>1);
	$maps[] = array("project_priorities", "project_priorities.csv", "id", "", $fields, true);

	// themes
	$fields = array("id"=>0,"css_file"=>1,"app_name"=>2,"title"=>3, "f_default"=>4);
	$maps[] = array("themes", "app_themes.csv", "id", "", $fields, true);

	// app_widgets
	$fields = array("id"=>0,"title"=>1,"file_name"=>2,"class_name"=>3);
	$maps[] = array("app_widgets", "app_widgets.csv", "id", "", $fields, true);

	// countries
	$fields = array("id"=>0,"name"=>1);
	$maps[] = array("countries", "countries.csv", "id", "", $fields, true);

	// user_timezones
	$fields = array("id"=>0,"name"=>1,"code"=>2, "loc_name"=>3, "offs"=>4);
	$maps[] = array("user_timezones", "user_timezones.csv", "id", "", $fields, true);

	// app_us_zipcodes
	$fields = array("zipcode"=>0, "city"=>1, "state"=>2, "latitude"=>3,
					"longitude"=>4, "timezone"=>5, "dst"=>6);
	$maps[] = array("app_us_zipcodes", "zipcodes.csv", "zipcode", "", $fields, true);

	// printing_paper_labels
	$fields = array("id"=>0, "name"=>1, "cols"=>2, "y_start_pos"=>3, "y_interval"=>4, "x_pos"=>5);
	$maps[] = array("printing_papers_labels", "printing_papers_labels.csv", "id", "", $fields, true);

	$dbh_sys = new CDatabase(AntConfig::getInstance()->db['syshost'], AntConfig::getInstance()->db['sysdb']);

	// Get database to use from account
	if ($ACCOUNT_DB)
		$res_sys = $dbh_sys->Query("select '$ACCOUNT_DB' as database");
	else
		$res_sys = $dbh_sys->Query("select distinct database from accounts");
	$num_sys = $dbh_sys->GetNumberRows($res_sys);
	for ($s = 0; $s < $num_sys; $s++)
	{
		$dbname = $dbh_sys->GetValue($res_sys, $s, 'database');
		if (!$HIDE_MESSAGES)
			echo "Updating $dbname\n";

		if ($dbname)
		{
			$dbh_acc = new CDatabase($settings_db_server, $dbname, $settings_db_user, $settings_db_password, $settings_db_type);

			foreach ($maps as $map)
			{
				$fh = fopen($settings_server_root."/system/datasets/".$map[1], 'r');
				if ($fh)
				{
					while (!feof($fh))
					{
						$query = "";
						$row_data = fgetcsv($fh, 10240, ",", "\"");
						
						if ($map[0] && $row_data[0])
						{
							$exists = sysEntExists(&$dbh_acc, $map[0], $map[2], $row_data[0], $map[3]);

							if ($exists && $map[5])
							{
								$query = "update ".$map[0]." set ";
								$updated = "";
								foreach ($map[4] as $tblcol=>$csvcol)
								{
									if ($updated)
										$updated .= ", ";

									$updated .= " $tblcol='".$dbh_acc->Escape($row_data[$csvcol])."'";
								}
								$query .= $updated." where ".$map[2]."='".$dbh_acc->Escape($row_data[0])."' ";
							}
							else if (!$exists)
							{
								$query = "insert into ".$map[0]."";
								$cols = "";
								$vals = "";
								foreach ($map[4] as $tblcol=>$csvcol)
								{
									if ($cols)
									{
										$cols .= ", ";
										$vals .= ", ";
									}

									$cols .= $tblcol;
									$vals .= "'".$dbh_acc->Escape($row_data[$csvcol])."'";
								}
								$query .= "($cols) values($vals)";
							}
						}
						
						if ($query)
						{
							$dbh_acc->Query($query);
							//echo $query."\n\n------------\n";
						}
					}
					fclose($fh);
				}
			}
		}
	}
?>
