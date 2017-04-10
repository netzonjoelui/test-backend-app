<?php
/**
 * Aereus Folder Object
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for approval processes
 *
 * @category CAntObject
 * @package Folder 
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Object extensions for managing folders in ANT
 */
class CAntObject_Folder extends CAntObject
{
	/**
	 * Items in this invoice
	 *
	 * @var array(stdCls(id, invoice_id, quantity, name, amount, product_id))
	 */
	private $itemsDetail = array();

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		parent::__construct($dbh, "folder", $eid, $user);
	}

	/**
	 * Open or create a new file in this folder
	 *
	 * If the file is marked as temp only one copy will be kept and it will be purged after 30 days
	 *
	 * @param string $fname The name of the file to create - must be unique
	 * @param bool $create Create the file if missing
	 * @return CAntObject_file on success, null on failure
	 */
	public function openFile($fname, $create=false)
	{
		
		$antFs = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->get("AntFs");
		
		$accPath = $antFs->getAccountDirectory($this->dbh);
		$file = null; 

		if ($this->id && $accPath && $this->user)
		{
			$fid = null;

			$olist = new CAntObjectList($this->dbh, "file", $this->user);
			$olist->addCondition("and", "folder_id", "is_equal", $this->id);
			$olist->addCondition("and", "name", "is_equal", $fname);
			$olist->getObjects(0, 1);
			if ($olist->getNumObjects())
			{
				$obj = $olist->getObjectMin(0);
				$fid = $obj['id'];
			}

			// If not found and we are not supposed to create the file
			if (!$fid && !$create)
				return null;

			$file = CAntObject::factory($this->dbh, "file", $fid, $this->user);
			$file->setValue("name", $fname);
			$file->setValue("folder_id", $this->id);
			$file->skipObjectSyncStatCol = $this->skipObjectSyncStatCol;
			/*
			$file = new AntFs_File($this->dbh, $fid, $this->user);
			$file->name = $fname;
			$file->folderId = $this->id;
			 */
			if ($file->save() === false)
				$file = null;
		}

		return $file;
	}

	/**
	 * Open or crate a new folder in this folder
	 *
	 * @param string $fname The name of the file to create - must be unique
	 * @param bool $create Create the file if missing
	 * @return CAntObject_file on success, null on failure
	 */
	public function openFolder($fname, $create=false)
	{
		$folder = null; 

		if ($this->id && $this->user)
		{
			$fid = null;

			$olist = new CAntObjectList($this->dbh, "folder", $this->user);
			$olist->addCondition("and", "parent_id", "is_equal", $this->id);
			$olist->addCondition("and", "name", "is_equal", $fname);
			$olist->getObjects(0, 1);
			if ($olist->getNumObjects())
			{
				$obj = $olist->getObjectMin(0);
				$fid = $obj['id'];
			}

			// If not found and we are not supposed to create the file
			if (!$fid && !$create)
				return null;

			$folder = CAntObject::factory($this->dbh, "folder", $fid, $this->user);
			$folder->setValue("name", $fname);
			$folder->setValue("parent_id", $this->id);
			if ($folder->save() === false)
				$folder = null;
		}

		return $folder;
	}

	/**
	 * Import a file into the AntFs from the local file system
	 *
	 * If the file is marked as temp only one copy will be kept and it will be purged after 30 days
	 *
	 * @param string $filePath The path of the local file to import
	 * @param string $fname The name of the file to create - must be unique
	 * @param bigint $fid Optional file id to update. If set this will update a file rather than create a new one.
	 * @return CAntObject_File on success, null on failure
	 */
	public function importFile($filePath, $fname, $fid=null)
	{
		$file = null; 

		if ($this->id && $this->user && file_exists($filePath))
		{
			if ($fid) {
				$antFs = ServiceLocatorLoader::getInstance($this->dbh)->getServiceLocator()->get("AntFs");
				
				$file = $antFs->openFileById($fid, $this->dbh, $this->user);
			}	
			else
				$file = $this->openFile($fname, true);	

			$file->skipObjectSyncStatCol = $this->skipObjectSyncStatCol;

			if (!$file->importFile($filePath, $fname))
			{
				$file->removeHard();
				$file = null;
			}
		}

		return $file;
	}

	/**
	 * Get object list to query filus
	 */
	public function getFoldersList()
	{
		$olist = new CAntObjectList($this->dbh, "folder", $this->user);
		$olist->addCondition("and", "parent_id", "is_equal", $this->id);
		return $olist;
	}

	/**
	 * Get the full path for this folder relative to the root
	 */
	public function getFullPath()
	{
		$path = $this->getValue("name");

		// We are at the real root
		if (!$this->getValue("parent_id") && $path == '/')
			return $path;

		// This condition should never happen, but just in case
		if (!$this->getValue("parent_id"))
			return false;

		$pfolder = CAntObject::factory($this->dbh, "folder", $this->getValue("parent_id"), $this->user);
		$pre = $pfolder->getFullPath();

		if ($pre == "/")
			return "/" . $path;
		else
			return $pre . "/" . $path;
	}

	/**
	 * Move a folder to a different folder
	 *
	 * @param AntFs_Folder $folder The folder to move this file to
	 * @return true on success, false on failure
	 */
	public function move($folder)
	{
		if (!$folder || !$folder->id && $this->getValue("name") != "/")
			return false;
		$this->setValue("parent_id", $folder->id);
		return $this->save();
	}
}
