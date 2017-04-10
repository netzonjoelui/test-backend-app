<?php
	$dbh_acc = $ant->dbh;

	// Get the current version of the database
	$result = $dbh_acc->Query("select schema_revision from ant_system where id='1'");
	if ($dbh_acc->GetNumberRows($result))
	{
		$row = $dbh_acc->GetNextRow($result, 0);
		$revision = $row['schema_revision'];
	}
	else
	{
		$dbh_acc->Query("insert into ant_system(id, schema_revision) values('1', '1.0');");
		$revision = 0;
	}
	// Get account name
	$result = $dbh_acc->Query("select name from accounts");
	if ($dbh_acc->GetNumberRows($result))
	{
		$row = $dbh_acc->GetNextRow($result, 0);
		$settings_default_account = $row['name'];
	}
	
	$queries = array();
	$routines = array();
	
	include(AntConfig::getInstance()->application_path."/system/schema/legacy/updates/1.php");
	include(AntConfig::getInstance()->application_path."/system/schema/legacy/updates/2.php");
	
	// Set default objects
	$routines[] = "def_objects.php";

	// Process Queries
	// -------------------------------------------------------------------------------------------
	if (count($queries) || count($routines))
	{
		$i = 1;
		foreach ($queries as $query)
		{
			if (!$HIDE_MESSAGES)
				echo "\tRunning ".($i++)." of ".count($queries)." queries\n";

			$dbh_acc->Query($query);
		}

		$rcnt = 1;
		foreach ($routines as $page)
		{
			if (!$HIDE_MESSAGES)
				echo "\tRunning ".($rcnt++)." of ".count($routines)." routines = $page\n";

			include(AntConfig::getInstance()->application_path."/system/schema/legacy/updates/routines/".$page);
		}
	}

	// Last rev
	$dbh_acc->Query("update ant_system set schema_revision='$thisrev' where id='1'");
