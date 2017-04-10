<?php
	$result = $dbh_acc->Query("select app_object_type_fields.name, app_object_types.object_table from app_object_types, app_object_type_fields
								where app_object_type_fields.type_id = app_object_types.id
								and app_object_type_fields.type='real';");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		if ($row['name'] && $row['object_table'])
		{
			$dbh_acc->Query("ALTER TABLE ".$row['object_table']." ALTER ".$row['name']." TYPE double precision;");
		}
	}
?>
