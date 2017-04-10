<?php
/*======================================================================================
	
	class:		CPageCache

	Purpose:	Cache a page to plain text.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.

	Usage:		

	Variables:	1.	$ALIB_PAGECACHE_DIR (defaults to /tmp)

======================================================================================*/

class CPageCache
{
	var $page;
	var $fileHandle;
	var $iSeconds;
	var $sDir;
	var $fExpired;
	var $sBuf;
	var $fWritten;
	
	function CPageCache($seconds = 3600, $checkpage = NULL, $cachedir = NULL)
	{
		global $ALIB_PAGECACHE_DIR, $_SERVER;

        if(defined("ALIB_PAGECACHE_DIR"))
            $alibPageCacheDir = ALIB_PAGECACHE_DIR;
        else if(isset($ALIB_PAGECACHE_DIR))
            $alibPageCacheDir = $ALIB_PAGECACHE_DIR;
        
		if ($cachedir)
			$this->sDir = $cachedir;
		else if ($alibPageCacheDir)
			$this->sDir = $alibPageCacheDir;
		else
		{
			$this->sDir = "/tmp";

			if ($_SERVER['DOCUMENT_ROOT'])
			{
				$isthere = true;

				if (!file_exists($_SERVER['DOCUMENT_ROOT']."/tmp"))
					$isthere = mkdir($_SERVER['DOCUMENT_ROOT']."/tmp");

					$this->sDir = $_SERVER['DOCUMENT_ROOT']."/tmp";
			}
		}

		if ($checkpage) 
		{
			$this->page = $checkpage;
		}
		else
		{
			global $_SERVER;
			$parts = explode('/', $_SERVER['PHP_SELF']);
			$ftocheck = $parts[count($parts) - 1];
			$this->page = $ftocheck;
		}
		
		$this->iSeconds = $seconds;

		$this->fWritten = false;
		$this->sBuf = "";

		$this->CheckLastUpdate();
	}
	
	function __destruct() 
	{
		if (!$this->fWritten && $this->sBuf)
			$this->writeBuf();

		// Output page here
		@fclose($this->fileHandle);
	}

	function writeBuf()
	{
		if (!$this->fileHandle)
		{
			if (file_exists($this->sDir."/".$this->page))
				$wstr = "r";
			else
				$wstr = "x+";
			
			$this->fileHandle = fopen($this->sDir."/".$this->page, $wstr);
		}

		fwrite($this->fileHandle, $this->sBuf);

		$this->fWritten = true;
	}
	
	function CheckLastUpdate()
	{
		$test_seconds = $this->iSeconds;

		if ($test_seconds == -1)
			$test_seconds = 60*60*24; // 24 hours

		if ($test_seconds == -1) // -1 will never expire - must be manually updated
		{
			if (file_exists($this->sDir."/".$this->page))
			{
				$this->fExpired = false;
				$wstr = "r";
			}
			else
			{
				$this->fExpired = true;
				$wstr = "x+";
			}
			
			$this->fileHandle = fopen($this->sDir."/".$this->page, $wstr);
		}
		else
		{
			$ftime = @filemtime($this->sDir."/".$this->page);
			$dif =  time() - $ftime;
			if ($dif < $test_seconds)
			{
				$wstr = "r";
			}
			else
			{
				if (file_exists($this->sDir."/".$this->page))
					$wstr = "w+";
				else
					$wstr = "x+";
			}
			
			$this->fileHandle = fopen($this->sDir."/".$this->page, $wstr);
			
			if ($dif > $test_seconds)
			{
				$this->fExpired = true;
			}
			else
			{
				$this->fExpired = false;
			}
		}
	}
	
	function getPath()
	{
		return $this->sDir."/".$this->page;
	}

	function purge()
	{
		if (file_exists($this->sDir."/".$this->page))
		{
			@unlink($this->sDir."/".$this->page);
			@fclose($this->fileHandle);
			$this->fileHandle = null;
		}
	}

	function IsExpired()
	{
		return $this->fExpired;
	}
	
	function put($bwrite)
	{
		$this->sBuf .= $bwrite;
	}
	
	function PrintFile()
	{
		$this->printCache();
	}

	function printCache()
	{
		echo $this->getCache();
	}

	function getCache()
	{
		if (!$this->fWritten && $this->sBuf)
		{
			$this->writeBuf();
		}

		if ($this->sBuf)
		{
			return $this->sBuf;
		}
		else
		{
			if ($this->fileHandle)
			{
				fseek($this->fileHandle, 0);
				$size = filesize($this->sDir."/".$this->page);
				if ($size)
					$contents = fread($this->fileHandle, $size);
			}
			return $contents;
		}
	}
}
?>
