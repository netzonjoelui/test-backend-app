<?php
/**
 * ANT Service that handles background maintenance tasks for objects
 *
 * @category	AntService
 * @package		ObjectDynIdx
 * @copyright	Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntUser.php");		
require_once("lib/CAntObject.php");		
require_once("lib/AntRoutine.php");		
require_once("lib/AntFs.php");		

class AntFsMaint extends AntRoutine
{
	public function main(&$dbh)
	{
		$this->uploadLocalToAns($dbh);
	}

	/**
	 * Upload all local files to Ans
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	private function uploadLocalToAns(&$dbh)
	{
		$files = new CAntObjectList($dbh, "file");
		$files->addCondition("and", "dat_local_path", "is_not_equal", "");
		$files->addCondition("and", "dat_ans_key", "is_equal", "");
		$files->getObjects();
		for ($i = 0; $i < $files->getNumObjects(); $i++)
		{
			$file = $files->getObject($i);

			if (file_exists($file->getFullLocalPath()))
			{
				if ($file->uploadToAns())
					echo "\tQueued to upload: " . $file->getValue("name") . "\n";
			}
		}
	}
}
