<?php
	$CATID = $_REQUEST['cid'];

	echo "<files>";
	// Print any files that are not part of a category
	$query = "select id, file_name, file_title, file_type, keywords,
			  EXTRACT('year' from time_updated) as year, EXTRACT('month' from time_updated) as month,
			  EXTRACT('day' from time_updated) as day, EXTRACT('hour' from time_updated) as hour,
			  EXTRACT('minute' from time_updated) as minute, file_size, remote_file from
			  user_files where category_id='$CATID' and f_deleted is not true order by file_title";
	$file_result = $dbh->Query($query);
	$file_num = $dbh->GetNumberRows($file_result);
	for ($j = 0; $j < $file_num; $j++)
	{
		$row = $dbh->GetNextRow($file_result, $j);
		
	
		switch ($row['file_type'])
		{
		case "jpg":
		case "jpeg":
		case "png":
			echo "<file>";
			echo "<id>".$row['id']."</id>";
			echo "<url>".rawurlencode("http://$settings_localhost/userfiles/file_download.awp?view=1&fid=".$row['id'])."</url>";
			echo "<thumb>".rawurlencode("http://$settings_localhost/files/images/".$row['id'])."</thumb>"; // Client must append /width/height
			echo "<file_title>".rawurlencode($row['file_title'])."</file_title>";
			echo "</file>";
			break;
		}
	}
	$dbh->FreeResults($file_result);
	echo "</files>";
?>
