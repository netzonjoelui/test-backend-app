<?php
/**
 * Fix problem with files disappearing after being uploaded to ANS
 *
 * $ant is a global variable to this script and is already created by the calling class
 */
require_once("lib/CDatabase.awp");
require_once("lib/CAntObject.php");
require_once("lib/Ant.php");
require_once("lib/AntUser.php");
require_once("lib/Dacl.php");

if (!$ant)
	die("Update failed because $ ant is not defined");

$dbh = $ant->dbh;
$sl = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator();
$dm = $sl->get("Entity_DataMapper");

/**
 * Select undeleted files not yet uplaoded to ans
 */
$files = new CAntObjectList($dbh, "file");
$files->addCondition("and", "dat_local_path", "is_not_equal", "");
$files->addCondition("and", "dat_ans_key", "is_equal", "");
$files->getObjects();
for ($i = 0; $i < $files->getNumObjects(); $i++)
{
	$file = $files->getObject($i);

	if (!file_exists($file->getFullLocalPath()))
	{
		$ansKey = "";

		// Try and get they uploaded key from the latest revision
		$result = $this->dbh->Query("SELECT id FROM object_revisions WHERE object_type_id='" . $file->def->getId() . "'
									and object_id='" . $file->id . "' ORDER BY revision");
		for ($j = 0; $j < $dbh->GetNumberRows($result); $j++)
		{
			$revId = $this->dbh->GetValue($result, $j, "id");

			$res2 = $dbh->Query("SELECT field_value from object_revision_data WHERE 
								revision_id='$revId' AND field_name='dat_ans_key'");
			if ($dbh->GetNumberRows($res2))
			{
				$val = $dbh->GetValue($res2, 0, "field_value");

				if ($val)
					$ansKey = $val;
			}
		}

		// Set the last ans key uploaded to the current file name
		if ($ansKey)
		{
			echo "\tFixed " . $file->getValue("name") . " to $ansKey\n";

			$file->setValue("dat_ans_key", $ansKey);
			$file->setValue("dat_local_path", "");
			$file->save(false);
		}
	}
}
