<?php
	$result = $dbh_acc->Query("select id, name, object_table from app_object_types");
	$num = $dbh_acc->GetNumberRows($result);
	// First lets index all non-deleted objects
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$otid = $row['id'];
		//$dbh_acc->Query("DROP TABLE ".$tblName." CASCADE");
		//dbh_acc->Query("DROP TABLE ".$tblName."_del CASCADE");

		$obj = new CAntObject($dbh_acc, $row['name']);
		$obj->createObjectTypeIndex();

		/*
		$tblName = "app_object_index_".$otid;
		if ($dbh_acc->TableExists($tblName))
		{
			$dbh_acc->Query("DROP INDEX ".$tblName."_oid_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_oid_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_oid_idx
							  ON $tblName
							  USING btree
							  (object_id);");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_oid_idx
							  ON ".$tblName."_del
							  USING btree
							  (object_id);");

			$dbh_acc->Query("DROP INDEX ".$tblName."_ofid_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_ofid_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_ofid_idx
							  ON $tblName
							  USING btree
							  (field_id);");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_ofid_idx
							  ON ".$tblName."_del
							  USING btree
							  (field_id);");

			$dbh_acc->Query("DROP INDEX ".$tblName."_vnum_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_vnum_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_vnum_idx
							  ON $tblName
							  USING btree (val_number)
							  where val_number is not null;");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_vnum_idx
							  ON ".$tblName."_del
							  USING btree (val_number)
							  where val_number is not null;");

			$dbh_acc->Query("DROP INDEX ".$tblName."_vtime_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_vtime_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_vtime_idx
							  ON $tblName
							  USING btree (val_timestamp)
							  where val_timestamp is not null;");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_vtime_idx
							  ON ".$tblName."_del
							  USING btree (val_timestamp)
							  where val_timestamp is not null;");

			$dbh_acc->Query("DROP INDEX ".$tblName."_tsv_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_tsv_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_tsv_idx
							  ON $tblName
							  USING gin (val_tsv)
							  with (FASTUPDATE=ON)
							  where val_tsv is not null;");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_vtime_idx
							  ON ".$tblName."_del
							  USING gin (val_tsv)
							  with (FASTUPDATE=ON)
							  where val_tsv is not null;");

			$dbh_acc->Query("DROP INDEX ".$tblName."_vtext_idx");
			$dbh_acc->Query("DROP INDEX ".$tblName."_del_vtext_idx");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_vtext_idx
							  ON $tblName
							  USING btree (lower(val_text))
							  where val_text is not null;");
			$dbh_acc->Query("CREATE INDEX ".$tblName."_del_vtext_idx
							  ON ".$tblName."_del
							  USING btree (lower(val_text))
							  where val_text is not null;");
		}
		*/
	}
?>
