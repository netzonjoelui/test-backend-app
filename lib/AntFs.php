<?php
/**
 * Main core class for working with the Ant File System (AntFs)
 *
 * @category  Ant
 * @package   AntFs
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/aereus.lib.php/AnsClient.php");
require_once("lib/AntFsStreamWrapper.php");

/**
 * Main Ant Filesystem class
 */
class AntFs
{
	/**
	 * Handle to account database
	 *
	 * @var $dbh
	 */
	protected $dbh = null;

	/**
	 * Handle to current user
	 *
	 * If not set then system will be used
	 *
	 * @var AntUser $user
	 */
	protected $user = null;

	/**
	 * Handle to the account root folder
	 *
	 * @var AntFs_Folder
	 */
	protected $rootFolder = null;

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh active handle to account database
	 * @param Antuser $this Handle to current user. If not set then system user is used.
	 */
	public function __construct($dbh, $user=null)
	{
		$this->dbh = $dbh;

		if ($user)
			$this->user = $user;
		else
			$this->user = new AntUser($this->dbh, USER_SYSTEM);

		// Set the root folder for this account
		$this->rootFolder = $this->getRootFolder();
	}

	/**
	 * Get the root '/' folder for this account
	 *
	 * @return AntFs_Folder
	 */
	private function getRootFolder()
	{
		$olist = new CAntObjectList($this->dbh, "folder"); // Do not pass user because we need to get it no matter what
		$olist->addCondition("and", "parent_id", "is_equal", "");
		$olist->addCondition("and", "name", "is_equal", "/");
		$olist->addCondition('and', "f_system", "is_equal", "t");
		$olist->getObjects(0, 1);
		if ($olist->getNumObjects())
		{
			$obj = $olist->getObjectMin(0);
			$fldr = CAntObject::factory($this->dbh, "folder", $obj['id'], $this->user);
			return $fldr;
		}

		// If folder does not exist then create it
		$fldr = CAntObject::factory($this->dbh, "folder", null, $this->user);
		$fldr->setValue("name", "/");
		$fldr->setValue("f_system", 't');
		$fldr->save();
		// Should have returned above, root does not yet exist
		//$rootId = AntFs_Folder::create("/", null, $this->dbh);
		//$fldr = new AntFs_Folder($this->dbh, $rootId, $this->user);

		return $fldr;
	}

	/**
	 * Create folder or open it at the specified path
	 *
	 * @param string $path The path of the folder to open
	 * @param bool $createIfMissing If the folder does not exist, create it
	 * @return AntFs_Folder The folder that was opened or null on failure
	 */
	public function openFolder($path, $createIfMissing=false)
	{
		// Create system paths no matter what
		if (!$createIfMissing && ($path == "%tmp%" || $path == "%userdir%"))
			$createIfMissing = true;

		$folders = $this->splitPathToFolderArray($path, $createIfMissing);

		if ($folders)
		{
			//$fldr = new AntFs_Folder($this->dbh, $folders[count($folders)-1], $this->user);
			$fldr = CAntObject::factory($this->dbh, "folder", $folders[count($folders)-1], $this->user);
			return $fldr;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Open a folder by id
	 *
	 * @param integer $fid The unique id of the folder to upen
	 * @return AntFs_Folder The folder that was opened or false on failure
	 */
	public function openFolderById($fid)
	{
		$fldr = CAntObject::factory($this->dbh, "folder", $fid, $this->user);
		//$fldr = new AntFs_Folder($this->dbh, $fid, $this->user);
		return $fldr;
	}

	/**
	 * Open a folder by name
	 *
	 * @param integer $fid The unique id of the folder to upen
	 * @param number $parentFolderId Option parent folder id
	 * @param string $origPath Used to determine if we are workign with a system file
	 * @return AntFs_Folder The folder that was opened or null on failure
	 */
	public function openFolderByName($name, $parentFolderId, $create=false, $origPath="")
	{
		$fldr = null;

		$olist = new CAntObjectList($this->dbh, "folder");
		if ($parentFolderId)
			$olist->addCondition("and", "parent_id", "is_equal", $parentFolderId);
		else
			$olist->addCondition("and", "parent_id", "is_equal", "");
		$olist->addCondition("and", "name", "is_equal", $name);
		$olist->getObjects(0, 1);
		if ($olist->getNumObjects())
		{
			$fldr = $olist->getObject(0);
		}
		else if ($create)
		{
			$fldr = CAntObject::factory($this->dbh, "folder", null, $this->user);
			$fldr->setValue("name", $name);
			if ($parentFolderId)
				$fldr->setValue("parent_id", $parentFolderId);
			if ($origPath)
			{
				switch (strtolower($origPath))
				{
				case '/':
				case '/system':
				case '/system/temp':
				case '/system/users':
					$fldr->setValue("f_system", 't');
					break;
				default: // not a system directory
					break;
				}
			}
			$fldr->save();
		}

		return $fldr;
	}

	/**
	 * Find out if a folder exists
	 *
	 * May be called statically if $dbh and $user are passed as params
	 *
	 * @param string $path The path of the folder to look for
	 * @param number $parentFolderId Option parent folder id
	 * @param CDatabase $dbh Optional handle to database - required if called statically
	 * @param AntUser $user Optional handle to current user- required if called statically
	 * @return bool True if the folder exists, false if it is missing
	 */
	public function folderExists($path=null, $name=null, $parentFolderId=null, $dbh=null, $user=null)
	{
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		if (!$user && isset($this) && get_class($this) == __CLASS__)
			$user = $this->user;

		if (!$dbh || !$user)
			return false;

		if ($path)
		{
		}
		else if ($name)
		{
			$olist = new CAntObjectList($dbh, "folder");
			if ($parentFolderId)
				$olist->addCondition("and", "parent_id", "is_equal", $parentFolderId);
			else
				$olist->addCondition("and", "parent_id", "is_equal", "");
			$olist->addCondition("and", "name", "is_equal", $name);
			$olist->getObjects(0, 1);
			if ($olist->getNumObjects())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Open file by id
	 *
	 * May be called statically as long as $dbh, and $user are passed as params
	 *
	 * @param integer $fid The unique id of the file to open
	 * @param CDatabase $dbh Optional handle to database - required if called statically
	 * @param AntUser $user Optional handle to current user- required if called statically
	 * @return CAntObject_File The file that was opened or null on failure
	 */
	public function openFileById($fid, $dbh=null, $user=null)
	{
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		if (!$user && isset($this) && get_class($this) == __CLASS__)
			$user = $this->user;

		if (!$dbh || !$user)
			return false;

		$file = CAntObject::factory($dbh, "file", $fid, $user);
		if (!$file->id)
			return null;
		else
			return $file;
	}

	/**
	 * Create a temp file to work with
	 *
	 * Temp files are stored in special directories and purged after 30 days.
	 *
	 * @param integer $fid The unique id of the file to open
	 * @return CAntObject_File The file that was opened or false on failure
	 */
	public function createTempFile()
	{
		$file = null;

		$fldr = $this->openFolder("%tmp%", true);
		if ($fldr)
		{
			$file = $fldr->openFile("tempfilename", true);
		}

		return $file;
	}

	/**
	 * Delete a file by id
	 *
	 * May be called statically with the $dbh param
	 *
	 * @param integer $fid The unique id of the file to open
	 * @param CDatabase $dbh Optional handle to database - required if called statically
	 * @return bool True on success and false on error
	 */
	public function removeFileById($fid, $dbh=null)
	{
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		if (!$dbh)
			return false;

		$file = new CAntObject_File($dbh, $fid, $this->user);
		return $file->remove();
	}

	/**
	 * Delete a folder by id
	 *
	 * @param integer $fid The unique id of the folder to open
	 * @param CDatabase $dbh Optional handle to database - required if called statically
	 * @param AntUser $user Optional handle to current user- required if called statically
	 * @return bool True on success and false on error
	 */
	public function removeFolderById($fid, $dbh=null, $user=null)
	{
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		if (!$user && isset($this) && get_class($this) == __CLASS__)
			$user = $this->user;

		if (!$dbh || !$user)
			return false;

		$fldr = CAntObject::factory($dbh, "folder", $fid, $user);
		return $fldr->remove();
	}

	/**
	 * Purge all files older than 30 days in the system temp directory
	 */
	public function purgeTemp()
	{
		$fldr = $this->openFolder("%tmp%");
		$numPurged = 0;

		if ($fldr)
		{
			$olist = new CAntObjectList($this->dbh, "file", $this->user);
			$olist->addCondition("and", "ts_entered", "is_less", date("m/d/Y", strtotime("-1 month")));
			$olist->getObjects(0, 1000); // Delete up to 1000 at a time
			for ($i = 0; $i < $olist->getNumObjects(); $i++)
			{
				$file = $olist->getObject($i);
				$file->close();
				$file->removeHard();
				$numPurged++;
			}
		}

		return $numPurged;
	}

	/**
	 * Convert number of bytes into a human readable form
	 *
	 * @param integer $size The size in bytes
	 * @return string The human readable form of the size in bytes
	 */
	public function getHumanSize($size)
	{
		if ($size >= 1000000000000)
			return round($size/1000000000000, 1) . "TB";
		if ($size >= 1000000000)
			return round($size/1000000000, 1) . "G";
		if ($size >= 1000000)
			return round($size/1000000, 1) . "M";
		if ($size >= 1000)
			return round($size/1000, 0) . "K";
		if ($size < 1000)
			return $size + "B";
	}

	/**
	 * Split a path into an array of folders
	 *
	 * @param string $path The folder path to split into an array of folder ids
	 * @param bool $createifmissing If set to true the function will attempt to create any missing directories
	 */
	private function splitPathToFolderArray($path, $createifmissing=true)
	{
		// Translate any variables in path
		$path = $this->substituteVariables($path);

		// Get protocol
		if (strpos($path, "://"))
		{
			$parts = explode("://", $path);
			$this->protocol = $parts[0];
			$path = $parts[1];
		}
		else
		{
			$this->protocol = "local";
		}

		// Parse folder path
		$folder_names = explode("/", $path);
		$folder_ids = array();


		// Check for absolute path
		/*
		if ($folder_names[0] == "") // First char was '/'
		{
			$folder_ids[0] = $this->rootFolder->id;
		}
		 */

		$folder_ids[0] = $this->rootFolder->id;
		$last_folder = $folder_ids[0];
		foreach ($folder_names as $fname)
		{
			if ($fname)
			{
				$fldr = $this->openFolderByName($fname, $last_folder, $createifmissing, $path);

				/*
				$fid = AntFs_Folder::getId($fname, $last_folder, $this->dbh);

				// If the folder was not found and creatifmissing is true, then add dir
				if (!$fid && $createifmissing)
					$fid = AntFs_Folder::create($fname, $last_folder, $this->dbh, $this->user);
				 */

				if(isset($fldr->id) && $fldr->id)
				{
					$folder_ids[] = $fldr->id;
					$last_folder = $fldr->id;
				}
				else // Folder not found
				{
					return false;
				}
			}
		}

		return $folder_ids;
	}

	/**
	 * Handle variable substitution
	 *
	 * @param string $path The path to replace variables with
	 * @return string The path with variables substituted for real values
	 */
	private function substituteVariables($path)
	{
		$retval = $path;

		$retval = str_replace("%tmp%", "/System/Temp", $retval);
		$retval = str_replace("%userdir%", "/System/Users/".$this->user->id, $retval);
		$retval = str_replace("%emailattachments%", "/System/Users/".$this->user->id."/System/Email Attachments", $retval);

		// Now kill all unallowed chars
		/*
		$retval = str_replace("%", "", $retval);
		$retval = str_replace("?", "", $retval);
		$retval = str_replace(":", "", $retval);
		$retval = str_replace("\\", "", $retval);
		$retval = str_replace(">", "", $retval);
		$retval = str_replace("<", "", $retval);
		$retval = str_replace("|", "", $retval);
		 */

		return $retval;
	}

	/**
	 * Create directory for account if does not exist.
	 *
	 * May be called statically if dbh is passed as param
	 *
	 * @param CDatabase $dbh Optional handle to database to be used when called statically
	 * @return string The full path of the old account directory
	 * @throws \Exception if permission to directory is denied
	 */
	public function getAccountDirectory($dbh=null)
	{
		// Now we have update the path in the new netric FileSystem
		return $this->getAccountDirectoryNew($dbh);

		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		// Make sure dbh is set
		if (!$dbh)
			return false;

		$path = AntConfig::getInstance()->data_path . "/antfs";

		// Create antfs directory in the data if it does not yet exist
		if (!file_exists($path))
		{
			mkdir($path, 0777);

			if (!chmod($path, 0777))
				throw new \Exception("Permission denied chmod($path)");
		}

		// Now create namespace dir for dbname
		$path .=  "/" . $dbh->dbname;

		if (!file_exists($path))
		{
			mkdir($path, 0777);

			if (!chmod($path, 0777))
				throw new \Exception("Permission denied chmod($path)");

		}

		return $path;
	}

	/**
	 * This is the new version which stores files in a folder named after the account it
	 *
	 * @param CDatabase $dbh Optional handle to database to be used when called statically
	 * @return string The full path of the new account directory
	 * @throws \Exception if permission to directory is denied
	 */
	public function getAccountDirectoryNew($dbh=null)
	{
		if (!$dbh && isset($this) && get_class($this) == __CLASS__)
			$dbh = $this->dbh;

		// Make sure dbh is set
		if (!$dbh)
			return false;

		$path = AntConfig::getInstance()->data_path . "/files";

		// Create antfs directory in the data if it does not yet exist
		if (!file_exists($path))
		{
			mkdir($path, 0777);

			if (!chmod($path, 0777))
				throw new \Exception("Permission denied chmod($path)");
		}

		// Now create a namespace for the account id
		$path .=  "/" . $dbh->accountId;

		if (!file_exists($path))
		{
			mkdir($path, 0777);

			if (!chmod($path, 0777))
				throw new \Exception("Permission denied chmod($path)");

		}

		return $path;
	}
}
