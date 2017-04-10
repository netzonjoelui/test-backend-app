<?php
/**
 * Ant File System Library
 *
 * CAntFsFile class used to for individual files
 *
 * @category  CAntFs
 * @package   CAntFsFile
 * @copyright Copyright (c) 2003-2011 Aereus Corporation (http://www.aereus.com)
 */


/**
 * CAntFsFile represents an individual file in AntFs
 */
class CAntFsFile
{
	public $dbh;				// Database handle
	public $name;				// Name of file
	public $name_lcl;			// Name of file on disk
	public $id;				// ID of file
	public $type;				// Type/ext of file
	public $contentId;			// Content-type of file
	public $contentType;		// Content-type of file
	public $contentDisposition;// Content-disposition of file
	public $size;				// Size (in bytes) of file
	public $f_deleted;			// Deleted flag
	public $time_modified;		// Time last modified
	public $url_stream;		// URL link to http download (stream)
	public $url_download;		// URL link to http download (download)
	public $owner_id;			// id of the owner user
	public $user = null;		// Current user (optional)
	public $read_offset;		// read file offset counter
	public $ansKey;				// file is stored on ANS via this key
	public $remoteFile;			// DEPRICATED - used for legacy ANS storage
	public $accountNumber;		// The account number of this file
	public $noAns = false;		// Set to true to restrict all file access to the local disk
	public $revision = 1;		// File revision number
	private $readFile = null;	// Handle to file for reading file from various sources

	/**
     * Class constructor
	 *
	 * @param CDatabase $dbh	Handle to account database
	 * @param int $id			Unique id of file
	 * @param string $name		Optional name to use to override the one stored
	 * @param string $protocol 	Local or remote files.
     */
	function __construct($dbh, $id, $name="", $protocol='local')
	{
		global $settings_localhost;

		$this->dbh = $dbh;
		$this->name = $name;
		$this->name_lcl = $name;
		$this->id = $id;
		$this->f_deleted = false;
		$this->read_offset = 0;

		$result = $dbh->Query("select *, to_char(time_updated, 'MM/DD/YYYY HH12:MI:SS AM') as ts_updated from user_files where id='$id'");	
		if ($dbh->GetNumberRows($result))
		{
			$row = $dbh->GetNextRow($result, 0);

			if (!$name)
				$this->name = $row['file_title'];
			$this->type = $row['file_type'];
			$this->size = $row['file_size'];
			$this->owner_id = $row['user_id'];
			$this->f_deleted = ($row['f_deleted']=='t') ? true : false;
			$this->name_lcl = $row['file_name'];
			$this->ansKey = $row['ans_key'];
			$this->remoteFile = $row['remote_file'];
			$this->folderId = $row['category_id'];
			$this->revision = ($row['revision']) ? $row['revision'] : 1;

			if ($row['ts_updated'])
			{
				$this->ts_updated = $row['ts_updated'];
			}
			else
			{
				$this->ts_updated = date("m/d/Y g:i:s a", strtotime($row['date_start']));
				$dbh->Query("update user_files set ts_updated='".$this->ts_updated."' where id='$id'");
			}

			// The below can be used to forward requests to a remote server/share
			switch ($this->type)
			{
			case 'adf':
				$this->url_stream = "http://".$settings_localhost."/document/editor.awp?fid=".$this->id;
				$this->url_download = "";
				break;
			case 'emt':
				$this->url_stream = "http://".$settings_localhost."/email/compose.awp?fid=".$this->id;
				$this->url_download = "";
				break;
			case 'apl':
				$this->url_stream = "http://".$settings_localhost."/amedia/player.awp?plist=".$this->id;
				$this->url_download = "";
				break;
			case 'jpg':
			case 'jpeg':
			case 'bmp':
			case 'png':
			case 'gif':
				$this->url_stream = "http://".$settings_localhost."/userfiles/file_download.awp?view=1&fid=".$this->id;
				$this->url_download = "http://".$settings_localhost."/userfiles/file_download.awp?fid=".$this->id;
				break;
			default:
				$this->url_stream = "http://".$settings_localhost."/userfiles/file_download.awp?fid=".$this->id;
				$this->url_download = "http://".$settings_localhost."/userfiles/file_download.awp?fid=".$this->id;
				break;
			}
		}
	}

	/**
	 * Class destructor. Try to cleanup
	 */
	function __destruct()
	{
		if ($this->readFile)
			@fclose($this->readFile);
	}

	function read($numbytes=null, $offset=0)
	{
		/*
		$dbh = $this->dbh;

		$offset = ($offset) ? $offset : $this->read_offset;

		if ($offset > $this->size)
		{
			return null;
		}
		else if ($numbytes && (($offset+$numbytes) > $this->size))
		{
			$numbytes = $this->size - $this->read_offset;
		}

		if ($numbytes)
			$this->read_offset += $numbytes;
		else
		{
			$this->read_offset = $this->size; // EOF
			$numbytes = -1;
		}

		return UserFilesGetFileContents($dbh, $this->id, $numbytes, $offset);
		*/

		if (!$numbytes)
			$numbytes = $this->size;

		if ($this->ansKey)
		{
			return $this->readAns($numbytes, $offset);
		}
		else if ($this->remoteFile) // Old ANS code
		{
			return $this->readOldAns($numbytes, $offset);
		}
		else
		{
			return $this->readLocal($numbytes, $offset);
		}

		return null;
	}

	function write($data)
	{
		global $ALIB_ANS_SERVER;
		$dbh = $this->dbh;
		$ret = -1;

		$antsys = new AntSystem();
		$ainfo = $antsys->getAccountInfoByDb($this->dbh->dbname);
		$aid = $ainfo['id'];

		if ($aid['id'] == -1)
			return false;

		if ($this->id && $aid && $this->name_lcl)
		{
			$target_dir = AntConfig::getInstance()->data_path."/$aid/userfiles/".$this->owner_id;
			$ret = file_put_contents($target_dir."/".$this->name_lcl, $data);
			$this->size = @filesize($target_dir."/".$this->name_lcl);

			if ($ret != -1 && $ALIB_ANS_SERVER && !$noAns)
			{
				$wpdata = array(
					"full_local_path" => $target_dir."/".$this->name_lcl, 
					"local_path"=>$target_dir, // dir without file name
					"name"=>$this->name,
					"fname"=>$this->name_lcl, // temp file name used to store unique files
					"fid" => $this->id, 
					"revision" => $this->revision,
					"process_function"=>"",
				);

				$wm = new WorkerMan($dbh);
				$ret = $wm->runBackground("afs/file_upload_ans", serialize($wpdata));
			}
		}

		return $ret;
	}

	function eof()
	{
		if ($this->read_offset >= $this->size)
			return true;
		else
			return false;
	}

	function move($newname)
	{
		$dbh = $this->dbh;
		$dbh->Query("update user_files set file_title='".$dbh->Escape($newname)."' where id='".$this->id."'");
		$this->name = $newname;
	}

	function remove()
	{
		$dbh = $this->dbh;

		$userid = ($this->user) ? $this->user->id : $this->owner_id;

		UserFilesRemoveFile($dbh, $this->id, $userid);
	}

	/**
	 * Physically delete the file
	 */
	function removeHard()
	{
		$dbh = $this->dbh;

		$userid = ($this->user) ? $this->user->id : $this->owner_id;

		UserFilesRemoveFile($dbh, $this->id, $userid, false, true);
	}

	/**
     * Stream a file to either $fileHandle or to stdout via echo if no $fileHandle is defined
	 *
	 * @param file $fileHandle Handle to a file resource to put contents of file into
	 * @return bool true on success, false on failure
     */
	public function stream($fileHandle=null)
	{
		/*
		if ($this->ansKey)
		{
			return $this->streamAns($fileHandle);
		}
		else if ($this->remoteFile) // Old ANS code
		{
			return $this->streamOldAns($fileHandle);
		}
		else
		{
			return $this->streamLocal($fileHandle);
		}
		*/

		while (($buf = $this->read(8192)))
		{
			if ($fileHandle)
			{
				fwrite($fileHandle, $buf);
			}
			else
			{
				echo $buf;
				flush();
			}
		}
	}

	/**
     * Stream a file from (new) Aereus Network Storage server
	 *
	 * @param file $fileHandle Handle to a file resource to put contents of file into
	 * @return bool true on success, false on failure
     */
	private function streamAns($fileHandle=null)
	{
		if (!$this->ansKey)
			return false;

		// load from v2 ans server
		$ansClient = new AnsClient("anstest.aereusdev.com"); // TODO: remove manual server
		$handle = fopen($ansClient->getFileUrl($this->ansKey), 'rb');
		if ($handle)
		{
			while (!feof($handle))
			{
				$buf = fread($handle, 8192);
				if ($fileHandle)
				{
					fwrite($fileHandle, $buf);
				}
				else
				{
					echo $buf;
					flush();
				}
			}
			
			fclose($handle);
		}

		return true;
	}

	/**
     * Stream a file from (old) Aereus Network Storage server
	 *
	 * @param file $fileHandle Handle to a file resource to put contents of file into
	 * @return bool true on success, false on failure
     */
	private function streamOldAns($fileHandle=null)
	{
		if (!$this->remoteFile)
			return false;

		$accountNumber = $this->getAccountNumber();

		// load from old ans server
		$ans = new CAnsCLient();

		if ($ans->fileVerify($this->remoteFile, $this->id, "/userfiles/".$accountNumber))
		{
			$remote_file_page = $ans->getFileUrl($this->remoteFile, $this->id, "/userfiles/".$accountNumber, 1);
		}
		// TODO: // comment the below out eventually - only used for cleanup
		else if ($ans->fileVerify($this->remoteFile, $accountNumber."/".$this->id, "/userfiles"))
		{
			$remote_file_page = $ans->getFileUrl($this->remoteFile, $accountNumber."/".$this->id, "/userfiles", 1);
		}
		else if ($ans->fileVerify($this->remoteFile, "/".$this->id, "/userfiles"))
		{
			$remote_file_page = $ans->getFileUrl($this->remoteFile, "/".$this->id, "/userfiles", 1);
		}
		else if ($ans->fileVerify($this->remoteFile, $this->id, "/userfiles"))
		{
			$remote_file_page = $ans->getFileUrl($this->remoteFile, $this->id, "/userfiles", 1);
		}

		$handle = fopen($remote_file_page, 'rb');
		if ($handle)
		{
			while (!feof($handle))
			{
				$buf = fread($handle, 8192);
				if ($fileHandle)
				{
					fwrite($fileHandle, $buf);
				}
				else
				{
					echo $buf;
					flush();
				}
			}
			
			fclose($handle);
		}

		return true;
	}

	/**
     * Stream a file from local file storage
	 *
	 * @param file $fileHandle Handle to a file resource to put contents of file into
	 * @return bool true on success, false on failure
     */
	private function streamLocal($fileHandle=null)
	{
		$path = $this->getLocalPath();
		$ret = true;

		if (!$path)
			return false;

		if (file_exists($path))
		{
			$handle = fopen($path, 'r');

			while (!feof($handle)) 
			{
				echo fread($handle, 8192);
			}
			
			flush();
		
			@fclose($handle);
		}
		else
		{
			$ret = false;
		}

		return $ret;
	}

	/**
     * Read a file from (new) Aereus Network Storage server
	 *
	 * @param int $bytes the number of bytes to read
	 * @param int $offset (optional) offset to begin reading
	 * @return mixed data on success, false of failure
     */
	private function readAns($bytes=null, $offset=0)
	{
		if (!$this->ansKey)
			return false;

		// If file has not yet been opened, then open it
		if (!$this->readFile)
		{
			// load from v2 ans server
			$ansClient = new AnsClient("anstest.aereusdev.com"); // TODO: remove manual server
			$this->readFile = fopen($ansClient->getFileUrl($this->ansKey), 'rb');
		}

		if ($this->readFile)
			return fread($this->readFile, $bytes);
		else
			return false;
	}

	/**
     * Read a file from (old) Aereus Network Storage server
	 *
	 * @param int $bytes the number of bytes to read
	 * @param int $offset (optional) offset to begin reading
	 * @return mixed data on success, false of failure
     */
	private function readOldAns($bytes=null, $offset=0)
	{
		if (!$this->remoteFile)
			return false;

		// If file has not yet been opened, then open it
		if (!$this->readFile)
		{
			$accountNumber = $this->getAccountNumber();

			// load from old ans server
			$ans = new CAnsCLient();

			if ($ans->fileVerify($this->remoteFile, $this->id, "/userfiles/".$accountNumber))
			{
				$remote_file_page = $ans->getFileUrl($this->remoteFile, $this->id, "/userfiles/".$accountNumber, 1);
			}
			else if ($ans->fileVerify($this->remoteFile, $accountNumber."/".$this->id, "/userfiles"))
			{
				$remote_file_page = $ans->getFileUrl($this->remoteFile, $accountNumber."/".$this->id, "/userfiles", 1);
			}
			else if ($ans->fileVerify($this->remoteFile, "/".$this->id, "/userfiles"))
			{
				$remote_file_page = $ans->getFileUrl($this->remoteFile, "/".$this->id, "/userfiles", 1);
			}
			else if ($ans->fileVerify($this->remoteFile, $this->id, "/userfiles"))
			{
				$remote_file_page = $ans->getFileUrl($this->remoteFile, $this->id, "/userfiles", 1);
			}

            if($remote_file_page)
			    $this->readFile = fopen($remote_file_page, 'rb');
		}

		if ($this->readFile)
		{
			return fread($this->readFile, $bytes);
		}

		return false;
	}

	/**
     * Read a file from local file system
	 *
	 * @param int $bytes the number of bytes to read
	 * @param int $offset (optional) offset to begin reading
	 * @return mixed data on success, false of failure
     */
	private function readLocal($bytes=null, $offset=0)
	{
		// If file has not yet been opened, then open it
		if (!$this->readFile)
		{
			$path = $this->getLocalPath();

			if (!$path)
				return false;

			if (file_exists($path))
				$this->readFile = fopen($path, 'r');
		}

		if ($this->readFile)
			return fread($this->readFile, $bytes);
		else
			return false;
	}

	// Humanize size
	function getSizeHuman()
	{
		$size = $this->size;

		if ($size >= 1000000000000)
			return number_format(round($size/1000000000000, 1), 1) . "TB";
		if ($size >= 1000000000)
			return number_format(round($size/1000000000, 1), 1) . "G";
		if ($size >= 1000000)
			return number_format(round($size/1000000, 1), 1) . "M";
		if ($size >= 1000)
			return number_format(round($size/1000, 0), 0) . "K";
		if ($size < 1000)
			return $size + "B";

		// Default to 0
		return "0B";
	}

	/**
     * Get the full local path of a file
	 *
	 * TODO: See CAntFsFolder::getLocalPath for example of new storage
     */
	public function getLocalPath()
	{
		$accountNumber = $this->getAccountNumber();

		$file_dir = AntConfig::getInstance()->data_path."/$accountNumber/userfiles";
		$file_dir2 = AntConfig::getInstance()->data_path."/$accountNumber/userfiles";
		
		if ($this->owner_id!=null)
		{
			$file_dir .= "/".$this->owner_id;
			$file_dir2 .= "/".$this->owner_id;
		}

		if (!file_exists($file_dir."/".$this->name_lcl))
		{
			if (file_exists($file_dir2."/".$this->name_lcl))
				$file_dir = $file_dir2;
		}

		$file_dir .= "/".$this->name_lcl;

		return $file_dir;
	}

	/**
     * Get the account number for this file
     */
	public function getAccountNumber()
	{
		if ($this->accountNumber)
			return $this->accountNumber;
		
		$aid = UserFilesGetCatAccount($this->dbh, $this->folderId);

		if (!$aid)
		{
			$asys = new AntSystem();
			$ainfo = $asys->getAccountInfoByDb($this->dbh->dbname);
			$aid = $ainfo['id'];
		}

		if ($aid)
			$this->accountNumber = $aid;
		
		return $aid;
	}
}
