<?php
	/*
 		Move old custom fields for content_feeds to CAntObject custom fields with the use_with set to mask the field from all but specific feeds
	*/

	// now copy all activities
	$result = $dbh_acc->Query("select id, feed_id, col_name, col_title, col_type from xml_feed_fields order by feed_id");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);

		$obja = new CAntObject($dbh_acc, "content_feed_post");
		if ($row['col_type'] == "float")
			$row['col_type'] = "number";

		if ($row['col_type'] == "file")
		{
			$fdef = array('title'=>$row['col_title'], 'type'=>'fkey', 'subtype'=>'user_files', 'system'=>false, 'use_when'=>"feed_id:".$row['feed_id'],
						  'fkey_table'=>array("key"=>"id", "title"=>"file_title"));
		}
		else
		{
			$fdef = array('title'=>$row['col_title'], 'type'=>$row['col_type'], 'subtype'=>'', 'system'=>false, 'use_when'=>"feed_id:".$row['feed_id']);
		}
		$row['col_name'] = preg_replace("/[^a-zA-Z0-9_]/", "", $row['col_name']);
		$fname = $obja->addField(strtolower($row['col_name']), $fdef);
		echo "Added $fname to feed ".$row['field_id']."\n";

		$result2 = $dbh_acc->Query("select post_id, field_id, val_text, val_float, val_date from xml_feed_post_values where field_id='".$row['id']."'
									and (val_text is not null or val_float is not null or val_date is not null)");
		$num2 = $dbh_acc->GetNumberRows($result2);
		for ($j = 0; $j < $num2; $j++)
		{
			$row2 = $dbh_acc->GetRow($result2, $j);
			$val = ($row['col_type'] == "file") ? $row2["val_text"] : $row2["val_".$row['col_type']];

			if ($val)
			{
				$obj = new CAntObject($dbh_acc, "content_feed_post", $row2['post_id']);
				if (!$obj->getValue($fname))
				{
					$obj->setValue($fname, $val);
					$obj->save(false);
					echo "\tset $fname to $val for ".$row2['post_id']."\n";
				}
			}
		}
		echo "Copied $num2 values\n\n";
	}
?>
