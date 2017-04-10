<?php
/**
 * @depriacted We no longer use the email queue because we now use an imap server
 *
 * ANT Service that runs all the time and pulls raw email message from the queue and puts them into ANT
 *
 * @category	AntService
 * @package		EmailQueueProcess
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/Object/EmailMessage.php");
require_once("email/email_functions.awp");
require_once("lib/AntService.php");		

class EmailQueueProcess extends AntService
{
	public function main(&$dbh)
	{
		$result = $dbh->Query("select id, user_id, lo_message from email_message_queue order by ts_delivered DESC");
		$num = $dbh->GetNumberRows($result);
		echo "Found $num to process for ".$dbh->dbname."\n";
		// First lets index all non-deleted objects
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetRow($result, $i);

			// Download to temp file
			$filePath = $this->saveTmpFile($dbh, $row['lo_message']);
			echo "\tprocessing ".($i+1)." of $num\t";

			if ($filePath)
			{
				$user = new AntUser($dbh, $row['user_id']);

				// Use the new email message importer which in turn calls the AntMail_DeliveryAgent class
				$newEmail = new CAntObject_EmailMessage($dbh, null, $user);
				//$newEmail->debug = true;
				$mid = $newEmail->import($filePath);

				//$mid = EmailInsert($dbh, &$user, $filePath);
				echo "[success=$mid]\n";

				if ($mid)
				{
					// Archive and cleanup if success
					$dbh->Query("insert into email_message_original(message_id, lo_message, antmail_version) values('$mid', '".$row['lo_message']."', '4');");
					$dbh->Query("delete from email_message_queue where id='".$row['id']."'");
				}
			}
			else
			{
				echo "[failed]\n";
			}
			@unlink($filePath);
		}
		$dbh->FreeResults($result);
	}

	/**
	 * Save large object to a temporary file
	 * @param CDatabase $dbh reference to database
	 * @param int $oid = id of large object
	 */
	private function saveTmpFile(&$dbh, $oid)
	{
		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777, true);
		$tmpFile = tempnam($tmpFolder, "em");

		$ret = $dbh->loExport($oid, $tmpFile);

		// Normalize new lines to \r\n
		if ($ret)
		{
			$handle = @fopen($tmpFile, "r");
			$handleNew = @fopen($tmpFile."-pro", "w");
			$buffer = null;
			if ($handle) 
			{
				while (($buffer = fgets($handle, 4096)) !== false) 
				{

					fwrite($handleNew,  preg_replace('/\r?\n$/', '', $buffer)."\r\n");
				}
				fclose($handle);
				fclose($handleNew);
				unlink($tmpFile);
				$tmpFile = $tmpFile."-pro"; // update name to match processed file
			}
		}

		//echo file_get_contents($tmpFile);

		if ($ret)
			return $tmpFile;
		else
			return null;
	}
}
