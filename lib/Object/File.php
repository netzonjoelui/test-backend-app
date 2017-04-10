<?php
/**
 * Aereus File Object
 *
 * This main purpose of this class is to extend the standard ANT Object to include
 * functions for files in the AntFs
 *
 * @category CAntObject
 * @package File
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
//require_once("lib/aereus.lib.php/AnsClient.php");

/**
 * Object extensions for managing files in ANT
 */
class CAntObject_File extends CAntObject
{
	/**
	 * Handle to file for reading file from various sources
	 *
	 * @var file
	 */
	private $readFile = null;

	/**
	 * AnsClient instance
	 *
	 * This will be set if ANS is enabled for this account
	 *
	 * @var AnsClient
	 */
	private $ansClient = null;
	
	/**
	 * AntFs instance
	 *
	 * This will contain the AntFs instance
	 *
	 * @var antFs
	 */
	private $antFs = null;

	/**
	 * Initialize CAntObject with correct type
	 *
	 * @param CDatabase $dbh	An active handle to a database connection
	 * @param int $eid 			The event id we are editing - this is optional
	 * @param AntUser $user		Optional current user
	 */
	function __construct($dbh, $eid=null, $user=null)
	{
		global $ALIB_ANS_SERVER, $ALIB_ANS_ACCOUNT, $ALIB_ANS_PASS;

		if ($ALIB_ANS_SERVER && $ALIB_ANS_ACCOUNT && $ALIB_ANS_PASS)
			$this->ansClient = new AnsClient(); // settings are gathered automatically

		parent::__construct($dbh, "file", $eid, $user);
		
		$this->antFs = ServiceLocatorLoader::getInstance($dbh)->getServiceLocator()->get("AntFs");
	}

	/**
	 * Desctructor
	 */
	function __destruct()
	{
		if ($this->readFile)
			fclose($this->readFile);

		//parent::__destruct();
	}

	/**
	 * Load a file from the objects database by id
	 *
	 * @param int $fid The unique id of the file to load
	 */
	public function loaded()
	{
		$this->name = $this->getValue("name");
		$this->folderId = $this->getValue("folder_id");
		$this->size = $this->getValue("file_size");
	}

	/**
	 * Set the file type for this object before it is saved
	 */
	public function beforesaved()
	{
		$fname = $this->getValue("name");

		$ext = "";
		$pos = strrpos($fname, ".");
		if ($pos !== FALSE)
			$ext = substr($fname, $pos + 1);
		else
			$ext = (strlen($fname)>32) ?  substr($fname, 0, 32) : $fname;

		if ($this->getValue("filetype") != $ext)
			$this->setValue("filetype", strtolower($ext));
	}

	/**
	 * Read from a file
	 *
	 * @param int $numbytes The number of bytes to read. If null then read all.
	 * @param int $offset The offset to begin reading. Default is 0.
	 */
	public function read($numbytes=null, $offset=0)
	{
		if ($this->getValue("dat_ans_key"))
		{
			return $this->readAns($numbytes, $offset);
		}
		else
		{
		if (!$numbytes)
			$numbytes = $this->getValue("file_size");

			return $this->readLocal($numbytes, $offset);
		}

		return null;
	}

	/**
     * Stream a file to either $fileHandle or to stdout via echo if no $fileHandle is defined
	 *
	 * @param file $fileHandle Handle to a file resource to put contents of file into
	 * @return int|bool Number of bytes sent on success, false on failure
     */
	public function stream($fileHandle=null, $start=0, $end=0)
	{
		$bytesSent = 0;
		$readNumBytes = 8192;
		if ($end && ($end-$start) < $readNumBytes)
			$readNumBytes = $end-$start;

		while (($buf = $this->read($readNumBytes)))
		{
			if ($fileHandle)
			{
				fwrite($fileHandle, $buf);
			}
			else
			{
				echo $buf;
				flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
			}

			$bytesSent += mb_strlen($buf, '8bit');

			if ($end && ($end-$start) < $readNumBytes)
				$readNumBytes = $end-$start;

			// Check if we have reached the end of the requested range (if set)
			if ($end)
			{
				if (($start + $bytesSent) >= $end)
					return $bytesSent;
			}
		}

		return $bytesSent;
	}

	public function streamRange()
	{
		$fp = @fopen($file, 'rb');
	 
		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		// Now that we've gotten so far without errors we send the accept range header
		/* At the moment we only support single ranges.
		 * Multiple ranges requires some more work to ensure it works correctly
		 * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		 *
		 * Multirange support annouces itself with:
		 * header('Accept-Ranges: bytes');
		 *
		 * Multirange content must be sent with multipart/byteranges mediatype,
		 * (mediatype = mimetype)
		 * as well as a boundry header to indicate the various chunks of data.
		 */
		header("Accept-Ranges: 0-$length");
		// header('Accept-Ranges: bytes');
		// multipart/byteranges
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		if (isset($_SERVER['HTTP_RANGE'])) {
	 
			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {
	 
				// (?) Shoud this be issued here, or should the first
				// range be used? Or should the header be ignored and
				// we output the whole content?
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			// If the range starts with an '-' we start from the beginning
			// If not, we forward the file pointer
			// And make sure to get the end byte if spesified
			if ($range0 == '-') {
	 
				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			}
			else {
	 
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			/* Check the range and make sure it's treated according to the specs.
			 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			 */
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
	 
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: $length");
	 
		// Start buffered download
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
	 
			if ($p + $buffer > $end) {
	 
				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		}
	 
		fclose($fp);
		
	}


	/**
	 * Close open file handles
	 */
	public function close()
	{
		if ($this->readFile)
		{
			@fclose($this->readFile);
			$this->readFile = null;
		}
	}

	/**
	 * Write data to this file
	 *
	 * This function will write data locally and then upload to ANS if 
	 * a server has been setup.
	 *
	 * @param mixed $data Binary data to write
	 * @param bool $uploadToAns If set to true the file will be uploaded automatically
	 */
	public function write($data, $uploadToAns=true)
	{
		$ret = -1;

		// Must be an exisitng file to write
		if (!$this->id)
			return $ret;

		// Get the local account root directory
		$accPath = $this->antFs->getAccountDirectory($this->dbh);
		$localPath = $this->getLocalPath();
		$fullPath = $accPath . '/' . $localPath;

		// Write data to the new file
		$ret = file_put_contents($fullPath, $data);
		$size = @filesize($fullPath);
		chmod($fullPath, 0777);

		// Update object
		$this->setValue("file_size", $size);
		$this->setValue("dat_local_path", $localPath);
		$this->setValue("dat_ans_key", ""); // If set, then clear. Key will remain in saved revision.
		$this->save(false);
		$this->size = $size;

		// If ans is enabled, upload the new file in the background
		if ($ret != -1 && $uploadToAns)
		{
			$this->uploadToAns();
		}

		return $ret;
	}

	/**
	 * Import a local file into the AntFs
	 *
	 * @param string $filePath The path of the file to copy/import
	 * @param string $fname The name of the file to use
	 * @param bool $uploadToAns If set to true the file will be uploaded automatically
	 * @return bool true on success, false on failure
	 */
	public function importFile($filePath, $fname="", $uploadToAns=true)
	{
		$ret = false;

		// Must be an exisitng file to write
		if (!$this->id || !file_exists($filePath))
		{
			return $ret;
		}

		if (!$fname)
		{
			if (strrpos($filePath, "/") !== false) // unix
				$parts = explode("/", $filePath);
			else if (strrpos($filePath, "\\") !== false) // windows
				$parts = explode("\\", $filePath);
			else
				$parts = array($filePath);

			$fname = $parts[count($parts)-1]; // last entry is the file name
		}

		// Get the local account root directory
		$accPath = $this->antFs->getAccountDirectory($this->dbh);
		$localPath = $this->getLocalPath();
		$fullPath = $accPath . '/' . $localPath;

		// Write data to the new file
		$ret = copy($filePath, $fullPath);
		$size = @filesize($fullPath);
		chmod($fullPath, 0777);

		// Update object
		$this->setValue("name", $fname);
		$this->setValue("file_size", $size);
		$this->setValue("dat_local_path", $localPath);
		$this->setValue("dat_ans_key", ""); // If set, then clear. Key will remain in saved revision.		
		$this->save(false);

		// If ans is enabled, upload the new file in the background
		if ($ret && $uploadToAns)
		{
			$this->uploadToAns();
		}

		return $ret;
	}

	/**
	 * Upload this file to ANS
	 */
	public function uploadToAns()
	{
        $target_dir = null;
        
		if ($this->id && $this->getValue("name") && $this->ansClient != null && file_exists($this->getFullLocalPath()))
		{
			$wpdata = array(
				"full_local_path" => $this->getFullLocalPath(), 
				"local_path" => $this->getValue("dat_local_path"),
				"name" => $this->getValue("name"),
				"fname" => $this->id, // temp file name used to store unique files
				"fid" => $this->id, 
				"revision" => $this->revision,
				"process_function" => "",
			);

			$wm = new WorkerMan($this->dbh);
			$ret = $wm->runBackground("antfs/file_upload_ans", serialize($wpdata));
			return true;
		}

		return false;
	}

	/**
	 * Move a file to a different folder
	 *
	 * @param AntFs_Folder $folder The folder to move this file to
	 * @return true on success, false on failure
	 */
	public function move($folder)
	{
		if (!$folder || !$folder->id)
			return false;

		// First make sure that a file with this name does not already exist
		$existFile = $folder->openFile($this->getValue("name"));
		if ($existFile != null)
		{
			//$this->setValue("name", $this->getValue("name") . "(" . $this->id . ")");
			$existFile->remove(); // Overwrite
		}
		
		// Move this file
		$this->setValue("folder_id", $folder->id);
		return $this->save();
	}

	/**
	 * Purge this file
	 */
	public function removeHard()
	{
		$this->removePurgeFiles();

		return parent::removeHard();
	}

	/**
	 * Purge data files for all revisions of this file
	 *
	 * This is used just before the object is purged from the datastore to make sure all data files
	 * have been purged from both the local disk and from ANS (if applicable).
	 */
	public function removePurgeFiles()
	{
		if (!$this->id)
			return false;

		$antfsRoot = $this->antFs->getAccountDirectory($this->dbh);

		$this->close();

		// Delete current version
		if ($this->getValue("dat_ans_key"))
			$this->ansClient->delete($this->getValue("dat_ans_key"));
		if ($this->getValue("dat_local_path"))
		{
			if (file_exists($antfsRoot . "/" . $this->getValue("dat_local_path")))
				unlink($antfsRoot . "/" . $this->getValue("dat_local_path"));
		}

		// Delete revisions
		$revData = $this->getRevisionData();
		foreach ($revData as $rev)
		{
			// These files may not exist because if they were uplaoded to ANS
			// after being created then the system would have deleted them
			// so we try to delete ans first, then local
			if(isset($rev['dat_ans_key']) && $rev['dat_ans_key'] && $this->ansClient)
			{
				$this->ansClient->delete($rev['dat_ans_key']);
			}
			else if (isset($rev['dat_local_path']) && $rev['dat_local_path'])
			{
				if (file_exists($antfsRoot . "/" . $rev['dat_local_path']))
					unlink($antfsRoot . "/" . $rev['dat_local_path']);
			}
		}

	}

	/**
	 * Create local file path based on exploded id
	 *
	 * @return string The local folder path for this file or false if not set
	 */
	public function getFullLocalPath()
	{
		$ansRoot = $this->antFs->getAccountDirectory($this->dbh);

		// The new FileSystem\FileStore\LocalFileStore puts files in a different directory
		$newFileSystemRoot = $this->antFs->getAccountDirectoryNew($this->dbh);

		if (!$this->getValue("dat_local_path"))
			return false;


		// First look in new account directory otherwise fall back to old
		if (file_exists($newFileSystemRoot . "/" . $this->getValue("dat_local_path")))
			return $newFileSystemRoot . "/" . $this->getValue("dat_local_path");
		else
			return $ansRoot . "/" . $this->getValue("dat_local_path");
	}

	/**
	 * Create local file path based on exploded id
	 *
	 * @return string The local folder path for this file
	 */
	private function getLocalPath()
	{
		$localPath = $this->explodeIdToPath($this->id);
		$this->verifyPathTree($localPath);
		return $localPath . "/" . $this->id . "-" . $this->revision;
	}

	/**
	 * Explode id into directories
	 *
	 * @param bigint $id The id to explode
	 * @param bool $recur Are we in a recurring loop or is this the root
	 * @return string $path The full path of the file after exploding the id
	 */
	private function explodeIdToPath($id, $recur=false)
	{
		$perdir = 4;

		$len = strlen($id);

		if ($len < $perdir)
			return "0000";  // Should match the number of perdir above - all below 1k

		$first = substr($id, 0, 1);

		$path = $first."";

		for ($i = 1; $i < $len; $i++)
			$path .= "0";

		if ($len <= $perdir)
		{
			return $path;
		}
		else
		{
			return $path . "/" . $this->explodeIdToPath(substr($id, 1), true);
		}
	}

	/**
	 * Verify that a path exists
	 *
	 * @param string $path The path relative to the antfs root
	 */
	private function verifyPathTree($path)
	{
		$base = $this->antFs->getAccountDirectory($this->dbh);

		if (file_exists($base . "/" . $path))
			return true;

		$pathParts = explode("/", $path);
		$curr = $base;
		$allOk = true;
		foreach ($pathParts as $dirName)
		{
			// Skip over root
			if (!$dirName)
				continue;

			$curr .= "/" . $dirName;

			if (!file_exists($curr))
			{
				if (!@mkdir($curr, 0775))
					throw new \Exception("Permission denied mkdir($curr)");
			}

			// Error out
			if (!$allOk)
				return false;
		}

		return $allOk;
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
		if (!$this->getValue("dat_ans_key"))
			return false;

		// If file has not yet been opened, then open it
		if (!$this->readFile)
		{
			// load from v2 ans server
			$this->readFile = fopen($this->ansClient->getFileUrl($this->getValue("dat_ans_key")), 'rb');
		}

		if ($offset)
			fseek($this->readFile, $offset);

		if ($this->readFile)
		{
			// Had to use file_get_contents below because for some reason it would
			// not read the entire file with fread - always came back with only 5k of data?
			if ($bytes)
				return fread($this->readFile, $bytes);
			else
				return file_get_contents($this->ansClient->getFileUrl($this->getValue("dat_ans_key")));
		}
		else
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
			$path = $this->getFullLocalPath();

			if (!$path)
				return false;

			if (file_exists($path))
				$this->readFile = fopen($path, 'rb');
		}

		if ($offset)
			fseek($this->readFile, $offset);

		if ($this->readFile)
			return fread($this->readFile, $bytes);
		else
			return false;
	}

	/**
	 * Override the default because files can have different icons based on file type
	 *
	 * @return string The base name of the icon for this object if it exists
	 */
	public function getIconName()
	{
		$ftype = $this->getValue("filetype");
		if (!$ftype)
		{
			$fname = $this->getValue("name");
			$pos = strrpos($fname, ".");
			if ($pos !== FALSE)
				$ftype = strtolower(substr($fname, $pos + 1));
		}

		switch ($ftype)
		{
		case 'doc':
		case 'docx':
			return "files/doc";
		case 'pdf':
			return "files/pdf";
		case 'gif':
		case 'png':
		case 'jpg':
		case 'jpeg':
		case 'bmp':
		case 'tif':
			return "files/image";
		default:
			return parent::getIconName();
		}
	}

	/**
	 * Determine if this is an image or not
	 *
	 * @return bool true if file type is image, otherwise false
	 */
	public function isImage()
	{
		$ftype = $this->getValue("filetype");
		if (!$ftype)
		{
			$fname = $this->getValue("name");
			$pos = strrpos($fname, ".");
			if ($pos !== FALSE)
				$ftype = strtolower(substr($fname, $pos + 1));
		}

		switch ($ftype)
		{
		case 'gif':
		case 'png':
		case 'jpg':
		case 'jpeg':
		case 'bmp':
		case 'tif':
			return true;
		}

		return false;
	}

	/**
	 * Get extension of this file
	 *
	 * @return string The ext of the file. For example: file.jpg would return "jpg"
	 */
	public function getExt()
	{
		$ftype = $this->getValue("filetype");
		if (!$ftype)
		{
			$fname = $this->getValue("name");
			$pos = strrpos($fname, ".");
			if ($pos !== FALSE)
				$ftype = strtolower(substr($fname, $pos + 1));
		}

		return strtolower($ftype);
	}

	/**
	 * Get mime content type
	 *
	 * @return string The mime content type of this file
	 */
	public function getContentType()
	{
		$ftype = $this->getExt();

		$mimeType = "";

		switch ($ftype)
		{
		case 'gif':
		case 'png':
		case 'jpg':
		case 'jpeg':
		case 'bmp':
		case 'tif':
			$mimeType = "image/$ftype";
			break;
		case "exe":
			$mimeType = "application/octet-stream";
			break;
		case "zip":
			$mimeType = "application/octet-stream";
			break;
		case "mp3":
			$mimeType = "audio/mpeg";
			break;
		case "txt":
			$mimeType = "text/plain";
			break;
		case "html":
		case "htm":
			$mimeType = "text/html";
			break;
		default:
			$mimeType = "application/octet-stream";
			break;
		}

		return $mimeType;
	}
    
    public function resizeImageOld($filename, $maxWidth, $maxHeight, $type, $stretch = NULL)
    {
        if(empty($filename))
            $filename = $this->getValue("name");
        
        list($orig_width, $orig_height) = getimagesize($filename);
        
        $width = $orig_width;
        $height = $orig_height;
        
        if ($maxHeight)
        {
            // taller
            if (!$stretch)
            {
                if ($height > $maxHeight) 
                {
                   $width = ($maxHeight / $height) * $width;
                   $height = $maxHeight;
                }
            }
            else
            {
                 $width = ($maxHeight / $height) * $width;
                 $height = $maxHeight;
            }
        }
        if ($maxWidth)
        {    
            // wider
            if (!$stretch && $width)
            {
                if ($width > $maxWidth) 
                {
                   $height = ($maxWidth / $width) * $height;
                   $width = $maxWidth;
                }
            }
            else if ($width)
            {
                $height = ($maxWidth / $width) * $height;
                $width = $maxWidth;
            }
        }
                
        $processedImage = imagecreatetruecolor($width, $height);
        
        switch($type)
        {
        case "jpg":
        case "jpeg":
            $image = imagecreatefromjpeg($filename);
            break;
        case "gif":
            $image = imagecreatefromgif($filename);
            break;
        case "png":
            imageAntiAlias($processedImage,true);
            imagealphablending($processedImage, false);
            imagesavealpha($processedImage,true);
            $transparent = imagecolorallocatealpha($processedImage, 255, 255, 255, 0);
            for($x=0;$x<$width;$x++) 
            {
                for($y=0;$y<$height;$y++)
                     imageSetPixel($processedImage, $x, $y, $transparent);
            }
            $image = imagecreatefrompng($filename);
            break;
        case "bmp":
            $image = imagecreatefromwbmp($filename);
            break;
        default:
            break;
        }
        
        imagecopyresampled($processedImage, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
        
        return $processedImage;
    }

	/**
	 * Copy the data of this file to a temp file for local processing
	 *
	 * It is important that the calling process deletes the temp file to keep
	 * the temp directory from getting too full.
	 *
	 * @return string Path to a temp file with the contents of this file
	 */
	public function copyToTemp()
	{
		if (!$this->id)
			return null;

		$tmpFolder = (AntConfig::getInstance()->data_path) ? AntConfig::getInstance()->data_path."/tmp" : sys_get_temp_dir();
		if (!file_exists($tmpFolder))
			@mkdir($tmpFolder, 0777, true);

		// Create a unique name with the appropriate ext
		$tmpFile = $tmpFolder . "/" . $this->dbh->getAccountNamespace() . "-" . $this->id . "-" . $this->revision . "." . $this->getExt();

		// Check to see if the file has been cached already
		$cached = false;
		if (file_exists($tmpFile))
		{
			if (filesize($tmpFile) == $this->getValue("file_size"))
				$cached = true;
		}
		
		// Stream contenst of this file to the newly created temp file
		if (!$cached)
		{
			$file = fopen($tmpFile, "w+");
			$this->stream($file);
			fclose($file);
		}

		return $tmpFile;
	}

	/**
	 * Determine if file is a temp file
	 *
	 * @return bool true if a temp file, false if it is note in the temp directory
	 */
	public function isTemp()
	{
		if (!$this->id)
			return false;

		$antfs = new AntFs($this->dbh, $this->user);
		$fldr = $antfs->openFolder("%tmp%");

		if ($this->getValue("folder_id") == $fldr->id)
			return true;
		else
			return false;
	}

	/**
	 * Resize this file (presuming it is an image) to the maxWidth and maxHeaght and store in a temp file
	 *
	 * @param int $maxWidth The maximum width in px
	 * @param int $maxHeight The maximum height in px
	 * @param bool $streatch If true force image to grow until it reaches either max height or width
	 * @return string path to temp file, it should be deleted when finished
	 */
    public function resizeImage($maxWidth, $maxHeight, $stretch=false)
    {
		if (!$this->id || !$this->isImage())
			return null; // must be existing and must be an image file

		// Download the file to a temp file
		$tmpFile = $this->copyToTemp();

		// Check to see if file was moved or deleted
		if (!file_exists($tmpFile))
		{
			// Try to reopen this file to see if it was updated or moved
			$prev = $this->revision;
			$this->load(); // Reload with new values
			if ($prev != $this->revision)
				$tmpFile = $this->copyToTemp(); // try again
		}

        list($orig_width, $orig_height) = getimagesize($tmpFile);
        
        $width = $orig_width;
        $height = $orig_height;
        
        if ($maxHeight)
        {
            // taller
            if (!$stretch)
            {
                if ($height > $maxHeight) 
                {
                   $width = ($maxHeight / $height) * $width;
                   $height = $maxHeight;
                }
            }
            else
            {
                 $width = ($maxHeight / $height) * $width;
                 $height = $maxHeight;
            }
        }
        if ($maxWidth)
        {    
            // wider
            if (!$stretch && $width)
            {
                if ($width > $maxWidth) 
                {
                   $height = ($maxWidth / $width) * $height;
                   $width = $maxWidth;
                }
            }
            else if ($width)
            {
                $height = ($maxWidth / $width) * $height;
                $width = $maxWidth;
            }
        }

        $processedImage = imagecreatetruecolor($width, $height);
        
        switch($this->getExt())
        {
        case "jpg":
        case "jpeg":
            $image = imagecreatefromjpeg($tmpFile);
            break;
        case "gif":
            $image = imagecreatefromgif($tmpFile);
            break;
        case "png":
            imageAntiAlias($processedImage,true);
            imagealphablending($processedImage, false);
            imagesavealpha($processedImage,true);
            $transparent = imagecolorallocatealpha($processedImage, 255, 255, 255, 0);
            for($x=0;$x<$width;$x++) 
            {
                for($y=0;$y<$height;$y++)
                     imageSetPixel($processedImage, $x, $y, $transparent);
            }
            $image = imagecreatefrompng($tmpFile);
            break;
        case "bmp":
            $image = imagecreatefromwbmp($tmpFile);
            break;
        default:
            break;
        }
        
		// Resize image
        imagecopyresampled($processedImage, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
		$resizedFile = $tmpFile . "-res";

		// Create image
		switch ($this->getExt())
        {
            case "jpg":
            case "jpeg":
                imagejpeg($processedImage, $resizedFile);
                break;
            case "gif":
                imagegif($processedImage, $resizedFile);
                break;
            case "png":
                imagepng($processedImage, $resizedFile);
                break;
            case "bmp":
                imagewbmp($processedImage, $resizedFile);
                break;
            default:
                break;
        }

		// Clean up image resource
		imagedestroy($processedImage);
        
		// Clean up temp file
		unlink($tmpFile);

        return $resizedFile;
    }
}
