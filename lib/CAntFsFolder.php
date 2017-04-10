<?php
/**
 * Ant File System Library
 *
 * CAntFsFolder class used to manage folders in ANT
 *
 * @category  CAntFs
 * @package   CAntFsFolder
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */


/**
 * CAntFsFolder represents an individual folders in AntFs
 */
class CAntFsFolder
{
	// Public Variables
	// ----------------------------------------------------------------
	var $dbh;			// Handle to database
	var $user;			// AntUser class
	var $dacl;			// Dacl class for security
	var $name;			// Name of folder
	var $id;			// Id of folder
	var $f_deleted;		// Deleted flag
	var $owner_id;		// Userid who owns the folder
	var $fSpecial;		// Used for special or virtual folders
	var $files; 		// Array of CAntFsFile(s) in this directory
	var $folders; 		// Array of CAntFsDir(s) in this directory
	var $parent;		// Reference to parent folder
	var $parent_id;		// Reference to parent folder id
	var $protocol;		// Protocol to use - defaults to 'local'
	var $filter_types;	// Only show certain types of files (array) ['doc', 'jpg']
	var $filter_search;	// Search string for files (will be used in loadSearch function later)
	var $conditions;	// Search string for files (will be used in loadSearch function later)
	var $fShowDeleted;	// Set flag to show deleted files (off by default)

	// Public Functions
	// ----------------------------------------------------------------
	function CAntFsFolder($dbh, $user, $fid, $fname=null, $protocol='local', $f_special='f', $conditions=null)
	{
		global $ANTFS_FOLDER_ACLS;

		$this->id = $fid;
		$this->dbh = $dbh;
		$this->user = $user;
		$this->fSpecial = $f_special;
		$this->fShowDeleted = false;
		$this->conditions = ($conditions) ? $conditions : array();
		$this->f_deleted = false;
		$this->dacl = null;
		$this->owner_id = null;

		// Get the name of this folder
		if ($fname)
		{
			$this->name = $fname;
		}
		else if ($fid)
		{
			$query = "select name, parent_id, user_id, f_deleted from user_file_categories where id='$fid'";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				$this->name = stripslashes($row['name']);
				$this->parent_id = stripslashes($row['parent_id']);
				$this->owner_id = $row['user_id'];
				$this->f_deleted = ($row['f_deleted']=='t')?true:false;
				$dbh->FreeResults($result);

				if ($this->user)
				{
					$this->dacl = new Dacl($dbh, "/antfs/folders/$fid", false); // check if exists but do not create
					if (!$this->dacl->id && $this->parent_id)
					{
						$this->dacl = new Dacl($dbh, "/antfs/folders/$fid", true, $ANTFS_FOLDER_ACLS);
						$parent_folder = new CAntFsFolder($dbh, $user, $this->parent_id);
						$this->dacl->setInheritFrom($parent_folder->dacl->id);
					}
				}
			}
		}

		$this->files = null;
		$this->folders = null;
		$this->filter_types = null;
		$this->filter_search = null;
	}

	function loadFiles()
	{
		$dbh = $this->dbh;
		$this->files = array();

		if ($this->filter_types == "folder")
			return; // Do not load any files

		// Get files
		$query = "select id, file_title, f_deleted from user_files this where category_id='".$this->id."' ";
		if ($this->conditions['updated_after'])
				$query .= " and time_updated>'".$this->conditions['updated_after']."' ";
		if ($this->filter_types)
		{
			$cond = "";
			foreach ($this->filter_types as $type)
			{

				if (substr($type, 0, 1)=="!")
				{
					if ($cond) $cond .= " and ";
					$cond .= "file_type!='".substr($type, 1)."'";
				}
				else
				{
					if ($cond) $cond .= " or ";
					$cond .= "file_type='$type'";
				}
			}
			if ($cond)
				$query .= " and ($cond) ";
		}

		if ($this->filter_search)
		{
			$parts = explode(' ', $this->filter_search);
			$cond = "";
			foreach ($parts as $str)
			{
				if ($str)
				{
					if ($cond) $cond .= " and ";
					$cond .= "lower(file_title) like lower('%$str%')";
				}
			}
			if ($cond)
				$query .= " and $cond ";
		}

		if ($this->fShowDeleted)
		{
			$query .= " and revision = (select max(revision) from user_files version where 
								version.file_title=this.file_title and version.category_id='".$this->id."') ";
			
		}
		else
		{
			$query .= " and f_deleted is not true";
		}

		$query .= " order by file_title";
		$result = $dbh->Query($query);
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$this->files[] = new CAntFsFile($dbh, $row['id'], stripslashes($row['file_title']), $protocol);
		}
		$dbh->FreeResults($result);
	}

	function loadFolders()
	{
		// Fix duplicates
		UserFilesFixDupCats($this->dbh, $this->id);

		$dbh = $this->dbh;
		// Get subfolders
		$this->folders = array();

		$query = "select id, name, f_special, f_deleted from user_file_categories where 
							   parent_id='".$this->id."' ";
		if (!$this->fShowDeleted)
		{
			$query .= " and f_deleted is not true";
		}
		$result = $dbh->Query("$query order by name limit 1000");
		$num = $dbh->GetNumberRows($result);
		for ($i = 0; $i < $num; $i++)
		{
			$row = $dbh->GetNextRow($result, $i);
			$row['name'] = stripslashes($row['name']);
			$row['name'] = str_replace("\n", '', $row['name']);
			$row['name'] = str_replace("\t", '', $row['name']);
			$row['name'] = str_replace("\r", '', $row['name']);
			$row['name'] = str_replace("\\", '_', $row['name']);
			$ind = count($this->folders);
			$this->folders[$ind] = new CAntFsFolder($dbh, $this->user, $row['id'], $row['name'], $protocol, $row['f_special'], $this->conditions);
			$this->folders[$ind]->f_deleted = ($row['f_deleted'] == 't') ? true : false;
		}
		$dbh->FreeResults($result);
	}

	function numFiles()
	{
		if (!is_array($this->files))
			$this->loadFiles();

		return count($this->files);
	}

	function openFile($fileName)
	{
		for ($i = 0; $i < $this->numFiles(); $i++)
		{
			if ($this->files[$i]->name == $fileName)
				return $this->files[$i];
		}

		return false;
	}

	function numFolders()
	{
		if (!is_array($this->files))
			$this->loadFolders();

		return count($this->folders);
	}

	// Get the path of the parent folder
	function getParentPath()
	{
	}

	function getPath()
	{
		/*
		global $USERID;
		$retval = NULL;
		if (is_numeric($CATID))
		{
			if (UserFilesIsOwnerDir($dbh, $USERID, $CATID))
				$result = $dbh->Query("select name, parent_id from user_file_categories where id='$CATID'");
			else
			{
				if ($dbh->GetNumberRows($dbh->Query("select id from user_file_categories where share_to='$CATID' and user_id='$USERID'")))
					$result = $dbh->Query("select name, parent_id from user_file_categories where share_to='$CATID' and user_id='$USERID'");
				else
					$result = $dbh->Query("select name, parent_id from user_file_categories where id='$CATID'");
			}
				
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);
				
				//$retval = "<span style='color:#FFFFFF;'>&nbsp;/&nbsp;</span>";
				//if ($row['name'] != '/')
				
				$retval = $row['name'];
				
				if ($row['parent_id'])
				{
					$pre = UserFilesGetCatPath($dbh, $row['parent_id']);
					if ($pre == '/')
						$retval = $pre."&nbsp;".$retval;
					else
						$retval = $pre."&nbsp;/&nbsp;".$retval;
				}
			}
			$dbh->FreeResults($result);
		}

		return $retval;
		 */

	}

	// Delete a directory
	function remove()
	{
	}

	// Create a local pathed file into the ANT File System
	function uploadFile($path, $istemp=false)
	{
		$fname = basename($path);
		$file = $this->createFile($fname, $istemp);
		$content = file_get_contents($path);
		$file->write($content);
		return $file;
	}

	// Create a new file in this folder
	// Temp files are not backed up and they are purged after 30 days
	function createTempFile($fname)
	{
		$this->createFile($fname, true);
	}

	// Create a new file in this folder
	// Temp files are not backed up and they are purged after 30 days
	function createFile($fname, $istemp=false)
	{
		$dbh = $this->dbh;
		$ret = null;

		$antsys = new AntSystem();
		$ainfo = $antsys->getAccountInfoByDb($this->dbh->dbname);
		$aid = $ainfo['id'];

		if ($aid['id'] == -1)
			return false;

		if ($this->id && $aid && AntConfig::getInstance()->data_path && $this->user)
		{
			$revision = 1;

			// Check if local dir exists for user
			UserFilesCheckDirectory($this->user->id);
			$fname_lcl = str_replace(' ', '_', $fname);
			$fname_lcl = str_replace("'", "", $fname_lcl);
			$fname_lcl = str_replace("\\", "", $fname_lcl);
			// Get temp file name to prevent douplicates
			$seed = substr(microtime(), 0, 8);
			$fname_lcl = $seed."-".$fname_lcl;

			// Get extension
			$pos = strrpos($fname, ".");
			if ($pos !== FALSE)
				$ext = substr($fname, $pos + 1);
			else
				$ext = $fname;

			$target_dir = AntConfig::getInstance()->data_path."/$aid/userfiles/".$this->user->id;

			$result = $dbh->Query("select id, revision from user_files where category_id='".$this->id."' 
									and file_title='".$dbh->Escape($fname)."' order by revision DESC
									limit 1");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$usefid = $row['id'];
				$revision = $row['revision'];
				UserFilesRemoveFile($dbh, $row['id'], $this->user->id, true);
			}
			$dbh->FreeResults($result);

			if ($usefid)
			{
				$query = "update user_files set 
							file_name='".$dbh->Escape($fname_lcl)."', 
							file_title='".$dbh->Escape($fname)."', 
							user_id=".db_CheckNumber($this->user->id).", 
							category_id='".$this->id."', 
							file_size='0', 
							time_updated='now', 
							file_type='".strtolower($ext)."', 
							f_deleted='f', 
							f_temp='".(($istemp)?'t':'f')."', 
							revision='".($revision+1)."'
						  where id='$usefid';";
			}
			else
			{
				$query = "insert into user_files(";
				$query .= "file_title, file_name, user_id, category_id, file_size, time_updated, file_type, revision, f_temp)
						   values(";
				$query .= "'".$dbh->Escape($fname)."', '".$dbh->Escape($fname_lcl)."', '".$this->user->id."', 
						  '".$this->id."',
						  '0', 'now', '".strtolower($ext)."', '".($revision+1)."', '".(($istemp)?'t':'f')."');";
			}

			if ($usefid)
				$query .= "select '$usefid' as fid;";
			else
				$query .= "select currval('user_files_id_seq') as fid;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$ret = new CAntFsFile($dbh, $row['fid'], $fname);
				$this->files[] =$ret;
			}
		}

		return $ret;
	}

	/**
	 * Import a local file into the AntFs 
	 *
	 * @param string $filepath Path to an existing local file
	 * @return CAntFsFile on success, null on failure
	 */
	function importFile($filepath, $istemp=false)
	{
		$dbh = $this->dbh;
		$ret = null;

		$antsys = new AntSystem();
		$ainfo = $antsys->getAccountInfoByDb($this->dbh->dbname);
		$aid = $ainfo['id'];

		if ($aid['id'] == -1)
			return false;

		if ($this->id && AntConfig::getInstance()->data_path && $this->user)
		{
			$revision = 1;

			// Check if local dir exists for user
			UserFilesCheckDirectory($this->user->id);
			$fname_lcl = str_replace(' ', '_', $fname);
			$fname_lcl = str_replace("'", "", $fname_lcl);
			$fname_lcl = str_replace("\\", "", $fname_lcl);
			// Get temp file name to prevent douplicates
			$seed = substr(microtime(), 0, 8);
			$fname_lcl = $seed."-".$fname_lcl;

			// Get extension
			$pos = strrpos($fname, ".");
			if ($pos !== FALSE)
				$ext = substr($fname, $pos + 1);
			else
				$ext = $fname;

			$target_dir = AntConfig::getInstance()->data_path."/$aid/userfiles/".$this->user->id;

			$result = $dbh->Query("select id, revision from user_files where category_id='".$this->id."' 
									and file_title='".$dbh->Escape($fname)."' order by revision DESC
									limit 1");
			$num = $dbh->GetNumberRows($result);
			for ($i = 0; $i < $num; $i++)
			{
				$row = $dbh->GetNextRow($result, $i);
				$usefid = $row['id'];
				$revision = $row['revision'];
				UserFilesRemoveFile($dbh, $row['id'], $this->user->id, true);
			}
			$dbh->FreeResults($result);

			if ($usefid)
			{
				$query = "update user_files set 
							file_name='".$dbh->Escape($fname_lcl)."', 
							file_title='".$dbh->Escape($fname)."', 
							user_id=".db_CheckNumber($this->user->id).", 
							category_id='".$this->id."', 
							file_size='0', 
							time_updated='now', 
							file_type='".strtolower($ext)."', 
							f_deleted='f', 
							f_temp='".(($istemp)?'t':'f')."', 
							revision='".($revision+1)."'
						  where id='$usefid';";
			}
			else
			{
				$query = "insert into user_files(";
				$query .= "file_title, file_name, user_id, category_id, file_size, time_updated, file_type, revision, f_temp)
						   values(";
				$query .= "'".$dbh->Escape($fname)."', '".$dbh->Escape($fname_lcl)."', '".$this->user->id."', 
						  '".$this->id."',
						  '0', 'now', '".strtolower($ext)."', '".($revision+1)."', '".(($istemp)?'t':'f')."');";
			}

			if ($usefid)
				$query .= "select '$usefid' as fid;";
			else
				$query .= "select currval('user_files_id_seq') as fid;";
			$result = $dbh->Query($query);
			if ($dbh->GetNumberRows($result))
			{
				$row = $dbh->GetNextRow($result, 0);

				$ret = new CAntFsFile($dbh, $row['fid'], $fname);
				$this->files[] =$ret;
			}
		}

		return $ret;
	}

	/**
	 * Import a local file into the AntFs 
	 *
	 * @param integer $fileid The unique id of the file to be stored
	 * @return string The local path where the file data should be saved
	 */
	function getLocalPath($fileid)
	{
		$perdir = 3;

		$len = strlen($id);
		$first = substr($id, 0, 1);

		$path = AntConfig::getInstance()->data_path."/antfs/".$this->dbh->dbname."/".$first."";

		for ($i = 1; $i < $len; $i++)
			$path .= "0";

		if ($len <= $perdir)
		{
			return $path;
		}
		else
		{
			return $path . "/" . $this->makeLocalUniquePath(substr($id, 1));
		}
	}
}	
