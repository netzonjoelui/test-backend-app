<?php
	require_once("../lib/AntConfig.php");
	require_once("../settings/settings_functions.php");		
	require_once("../userfiles/file_functions.awp");
	// ANT LIB
	require_once("../lib/CDatabase.awp");
	require_once("../lib/aereus.lib.php/CAnsClient.php");

	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	ini_set("max_execution_time", "28800");	

	$DEBUG = TRUE;
	
	$dbh = new CDatabase();
	$ans = new CAnsCLient();

	$result = $dbh->Query("select id, file_name, file_title, file_type, category_id, user_id from user_files 
						   where remote_file is not null and remote_file!=''");
	$num = $dbh->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh->GetNextRow($result, $i);

		if ($DEBUG)
			echo "Returning ".($i + 1)." of $num:\t";

		$ans->deleteFile($row['remote_file'], $row['id'], "/userfiles");

		if ($DEBUG)
			echo "$ret\n";

		$settings_account_number = UserFilesGetCatAccount($dbh, $row['category_id']);
		$file_dir = $settings_data_path."/$settings_account_number/userfiles";
		if ($row['user_id'])
			$file_dir .= "/".$row['user_id'];
		@rename($file_dir."/backup/".$row['file_name'], $file_dir."/".$row['file_name']);

		$dbh->Query("update user_files set remote_file='' where id='".$row['id']."'");
	}
	$dbh->FreeResults($result);
?>
