<?php
	if (!class_exists("AntConfig" , false))
		die ("lib/AntConfig.php required before including this worker functions");

	require_once('lib/Worker.php'); 

	$g_workerFunctions = array(); // To be set by worker pages

	// Worker $worker must be defined by the script that includes this page
	$workerpages = array(
		"antfs.php",
		"cust_pdf_mailing_labels.php",
		"email_send.php",
		"email_send_bulk.php",
        "email_spamlearn.php",
		"email_account_sync.php",
		"lib_objects.php",
		"antsystem.php",
		"test.php"
	);

	foreach ($workerpages as $workerpage)
	{
		include("workers/".$workerpage);
	}
	$workerpage = ""; // Clean up just in case
?>
