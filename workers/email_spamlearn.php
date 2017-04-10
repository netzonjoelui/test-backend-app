<?php
/**
 * Train the spam engine
 */
require_once('lib/Worker.php'); 
require_once('lib/pdf/class.ezpdf.php'); 

if (is_array($g_workerFunctions))
{
    $g_workerFunctions["email/spamlearn"] = "email_spamlearn";
}

function email_spamlearn($job)
{
    $data = unserialize($job->workload());
	$dbh = $job->dbh;

	// Verify that the required data is set
	if (!is_numeric($data['user_id']) && !is_numeric($data['message_id']))
		return false;

	// Create user
	$user = new AntUser($dbh, $data['user_id']);
    
    // Instantiate Email Object
    $email = CAntObject::factory($dbh, "email_message", $data['message_id'], $user);

    // Check for correct folder
    $tmpFolder = AntConfig::getInstance()->data_path . "/email_" . (($email->getValue("flag_spam")=="t") ? "spam" : "ham");
    if (!file_exists($tmpFolder))
        @mkdir($tmpFolder, 0777, true);

    $filePath = $tmpFolder . "/" . $dbh->accountId . "-" . $email->id . ".eml";
    $original = $email->getOriginal();
    $bwritten = file_put_contents($filePath, $original);
    if (!$bwritten)
        unlink($filePath);
            
    return true;
}