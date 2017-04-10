<?php
	$result = $dbh_acc->Query("select id, name, object_table from app_object_types");
	$num = $dbh_acc->GetNumberRows($result);
	// First lets index all non-deleted objects
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$otid = $row['id'];

		$tblName = "app_object_index_".$otid;
		if ($dbh_acc->TableExists($tblName))
			$dbh_acc->Query("ALTER TABLE $tblName RENAME TO object_index_".$otid);
		if ($dbh_acc->TableExists($tblName."_del"))
			$dbh_acc->Query("ALTER TABLE ".$tblName."_del RENAME TO object_index_".$otid."_del");
	}
?>
